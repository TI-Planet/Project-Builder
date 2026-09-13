#!/usr/bin/env node
/* Compiler artifacts, target ABI, errors and one-shot allocation regressions. */
'use strict';
const assert = require('node:assert/strict');
const crypto = require('node:crypto');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawnSync} = require('node:child_process');

const root = path.resolve(__dirname, '..');
const manifest = JSON.parse(fs.readFileSync(path.join(root, 'versions.json'), 'utf8'));
const scratch = fs.mkdtempSync(path.join(os.tmpdir(), 'pb-mpy-check-'));
const nativeDirectory = process.argv[2];
let comparisons = 0;
let compilations = 0;

async function compile(target, source, sourceName, extraArguments = []) {
    const config = manifest.targets[target];
    const factory = require(path.join(root, config.files.js.name));
    const diagnostics = [];
    const module = await factory({
        noInitialRun: true,
        print: line => diagnostics.push(line),
        printErr: line => diagnostics.push(line),
    });
    module.FS.writeFile('/' + sourceName, source);
    const result = module.callMain([
        ...config.arguments, ...extraArguments,
        '-s', sourceName, '-o', '/output.mpy', '/' + sourceName,
    ]);
    // Emscripten's Node adapter exposes CLI exit codes even when called as a library.
    process.exitCode = 0;
    let output = null;
    if (module.FS.analyzePath('/output.mpy').exists) {
        output = Buffer.from(module.FS.readFile('/output.mpy'));
    }
    compilations++;
    return {result, output, diagnostics: diagnostics.join('\n')};
}

function nativeCompile(target, source, sourceName) {
    const filename = path.join(scratch, sourceName);
    const output = path.join(scratch, target + '-' + sourceName + '.mpy');
    fs.writeFileSync(filename, source);
    const child = spawnSync(path.join(nativeDirectory, target + '-mpy-cross-native'), [
        ...manifest.targets[target].arguments,
        '-X', 'heapsize=32M', '-s', sourceName, '-o', output, filename,
    ], {encoding: 'utf8', timeout: 30000});
    assert.equal(child.status, 0, child.stderr || String(child.error));
    return fs.readFileSync(output);
}

async function verify() {
    for (const [relative, expected] of Object.entries(manifest.buildInputs)) {
        assert.equal(crypto.createHash('sha256').update(fs.readFileSync(path.join(root, relative))).digest('hex'),
            expected, 'Stale build input: ' + relative);
    }
    // A 200+ KiB module with many independently allocated parse/compile objects.
    const stress = Array.from({length: 2400}, (_, i) =>
        `def function_${i}(value):\n    text = "function_${i}: été / 日本語"\n    return text, value + ${i}\n\n`
    ).join('');
    assert(Buffer.byteLength(stress) > 200 * 1024);
    assert(Buffer.byteLength(stress) <= 256 * 1024);
    const sources = fs.readdirSync(path.join(__dirname, 'fixtures')).sort().map(name => ({
        name, source: fs.readFileSync(path.join(__dirname, 'fixtures', name)),
    }));
    sources.push({name: 'large.py', source: stress});
    sources.push({name: 'empty.py', source: ''});
    for (const target of Object.keys(manifest.targets)) {
        for (const file of Object.values(manifest.targets[target].files)) {
            const payload = fs.readFileSync(path.join(root, file.name));
            assert.equal(payload.length, file.bytes);
            assert.equal(crypto.createHash('sha256').update(payload).digest('hex'), file.sha256);
        }
        for (const {name, source} of sources) {
            const result = await compile(target, source, name);
            assert.equal(result.result, 0, `${target}/${name}: ${result.diagnostics}`);
            assert.equal(result.output.subarray(0, 4).toString('hex'), manifest.targets[target].mpyHeader);
            if (nativeDirectory) {
                assert.deepEqual(result.output, nativeCompile(target, source, name), `${target}/${name}`);
                comparisons++;
            }
            console.log(`${target}/${name}: ${result.output.length} bytes`);
        }
        // Upstream folds this edge differently on 32/64-bit compiler hosts:
        // WASM stores an integer object, while a 64-bit host uses a small-int opcode.
        const boundarySource = 'value = -1073741824\n';
        const boundary = await compile(target, boundarySource, 'boundary.py');
        assert.equal(boundary.result, 0, boundary.diagnostics);
        assert(boundary.output.includes(Buffer.from('-1073741824')));
        if (nativeDirectory) {
            const wasmPath = path.join(scratch, target + '-boundary.wasm.mpy');
            const nativePath = path.join(scratch, target + '-boundary.native.mpy');
            fs.writeFileSync(wasmPath, boundary.output);
            fs.writeFileSync(nativePath, nativeCompile(target, boundarySource, 'boundary.py'));
            const decoded = spawnSync('python3', [path.join(__dirname, 'verify-boundary.py'),
                nativeDirectory, target, nativePath, wasmPath], {encoding: 'utf8'});
            assert.equal(decoded.status, 0, decoded.stderr || String(decoded.error));
            console.log(decoded.stdout.trim());
        }
        const invalid = await compile(target, 'def broken(:\n    pass\n', 'invalid.py');
        assert.equal(invalid.result, 1);
        assert.equal(invalid.output, null);
        assert.match(invalid.diagnostics, /line 1/);
        assert.match(invalid.diagnostics, /SyntaxError/);
        const nativeDecorator = await compile(target, '@micropython.native\ndef foo():\n    pass\n', 'native.py');
        assert.equal(nativeDecorator.result, 1);
        assert.equal(nativeDecorator.output, null);
        // Exercise a real allocation failure: no collection, trap or corrupt bytecode.
        const exhausted = await compile(target, stress, 'large.py', ['-X', 'heapsize=32K']);
        assert.equal(exhausted.result, 1);
        assert.equal(exhausted.output, null);
        assert.match(exhausted.diagnostics, /MemoryError/);
        assert.doesNotMatch(exhausted.diagnostics, /Unexpected garbage collection/);
        const excessiveHeap = await compile(target, 'pass\n', 'limit.py', ['-X', 'heapsize=17M']);
        assert.equal(excessiveHeap.result, 1);
        assert.equal(excessiveHeap.output, null);
        assert.match(excessiveHeap.diagnostics, /between 16 KiB and 16 MiB/);
        // Errors and large requests must not poison subsequent fresh instances.
        for (let i = 0; i < 3; i++) {
            const fresh = await compile(target, 'print("fresh")\n', 'fresh.py');
            assert.equal(fresh.result, 0, fresh.diagnostics);
        }
    }
    console.log(`PASS: ${compilations} WASM compilations, ${comparisons} native byte comparisons.`);
    if (!nativeDirectory) console.log('Supply the build --native scratch directory to compare native output.');
}

verify().then(() => fs.rmSync(scratch, {recursive: true})).catch(error => {
    console.error(error);
    process.exitCode = 1;
});
