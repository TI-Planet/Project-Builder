/* Client-side MicroPython compilation and TI module packaging. */
(() => {
    'use strict';

    function moduleName(filename) {
        if (!/^[a-zA-Z][a-zA-Z0-9_]{0,7}\.py$/i.test(filename)) {
            throw new Error('For bytecode export, use a Python filename with 1–8 letters, digits or underscores, starting with a letter.');
        }
        return filename.slice(0, -3);
    }

    function compile({target, source, filename, signal}) {
        moduleName(filename);
        if (!['ce', 'evo'].includes(target)) throw new Error('Unknown bytecode target.');
        if (typeof source !== 'string' || new TextEncoder().encode(source).length > 256 * 1024) {
            throw new Error('The Python source exceeds the 256 KiB compiler limit.');
        }
        if (!window.Worker || !window.WebAssembly) {
            throw new Error('Bytecode export requires a browser with WebAssembly and Web Workers.');
        }
        const config = window.pbPythonCompilerConfig;
        if (!config?.targets?.[target]) throw new Error('Python compiler assets are not configured.');
        return new Promise((resolve, reject) => {
            if (signal?.aborted) return reject(new DOMException('Compilation cancelled.', 'AbortError'));
            const worker = new Worker(config.workerUrl);
            let settled = false;
            const finish = (error, result) => {
                if (settled) return;
                settled = true;
                clearTimeout(timeout);
                signal?.removeEventListener('abort', abort);
                worker.terminate();
                error ? reject(error) : resolve(result);
            };
            const abort = () => finish(new DOMException('Compilation cancelled.', 'AbortError'));
            const timeout = setTimeout(() => finish(new Error('Compilation timed out. Try a smaller module.')), 30000);
            signal?.addEventListener('abort', abort, {once: true});
            worker.onerror = event => {
                event.preventDefault();
                finish(new Error(event.message || 'Unable to load the Python compiler. Please reload and retry.'));
            };
            worker.onmessageerror = () => finish(new Error('Unable to read the compiler result.'));
            worker.onmessage = ({data}) => finish(data.error ? new Error(data.error) : null, data);
            const assets = config.targets[target];
            worker.postMessage({target, source, filename, assets: {
                js: new URL(assets.js, document.baseURI).href,
                wasm: new URL(assets.wasm, document.baseURI).href
            }});
        });
    }

    function hex(bytes) {
        return Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
    }

    function packageModule(lib, {target, filename, bytes, menuText = null}) {
        const name = moduleName(filename);
        const expected = target === 'ce' ? [0x4d, 3, 2, 31] : target === 'evo' ? [0x4d, 5, 3, 31] : [];
        if (expected.length !== 4 || !(bytes instanceof Uint8Array)
            || expected.some((byte, index) => bytes[index] !== byte)) {
            throw new Error('The compiler produced incompatible MicroPython bytecode.');
        }
        const isEvo = target === 'evo';
        if (menuText !== null && (typeof menuText !== 'string' || menuText.includes('\0'))) {
            throw new Error('Menu files must contain text without NUL characters.');
        }
        const variableName = name.toUpperCase();
        const file = lib.TIVarFile.createNew(isEvo ? 'PythonAppVar' : 'PythonModuleAppVar', variableName, isEvo ? '84Evo' : '83PCEEP');
        let path;
        try {
            const content = isEvo ? {
                python: {compiledModule: true, name, bodyHex: hex(bytes)}
            } : {
                typeName: 'PythonModuleAppVar', filename: name, compiledDataHex: hex(bytes)
            };
            // Preserve menu text and placeholders.
            // The packer supplies CE metadata / Evo section terminators itself.
            if (menuText !== null) {
                if (isEvo) content.python.menuDefinitionHex = hex(new TextEncoder().encode(menuText));
                else content.menuDefinitions = menuText;
            }
            file.setContentFromString(JSON.stringify(content));
            if (isEvo) file.convertToEvoPythonFormat('8mp2');
            // The CE file's 16-bit data-section size includes a 17-byte entry
            // header in addition to the complete (size-prefixed) AppVar data.
            if (!isEvo && file.getRawContentHexStr().length / 2 > 65535 - 17) {
                throw new Error('The bytecode module is too large for a CE AppVar.');
            }
            file.setArchived(true);
            path = file.saveVarToFile('', variableName);
            return {file: lib.FS.readFile(path), filename: `${variableName}.${isEvo ? '8mp2' : '8xv'}`};
        } finally {
            if (path) lib.FS.unlink(path);
            file.delete();
        }
    }

    window.pbPythonBytecode = {compile, packageModule, moduleName};
})();
