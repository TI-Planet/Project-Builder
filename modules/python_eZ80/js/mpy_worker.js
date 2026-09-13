/* One compiler invocation per Worker: terminating it releases the compiler arena.
 * Serve this entry point with COEP: require-corp (see the adjacent .htaccess).
 */
'use strict';

self.onmessage = async ({data}) => {
    const diagnostics = [];
    try {
        const {target, source, filename, assets} = data;
        if (!['ce', 'evo'].includes(target) || typeof source !== 'string'
            || new TextEncoder().encode(source).length > 256 * 1024
            || !/^[a-zA-Z][a-zA-Z0-9_]{0,7}\.py$/i.test(filename)) {
            throw new Error('Invalid compiler input (maximum source size: 256 KiB).');
        }
        importScripts(assets.js);
        const compiler = await createMpyCross({
            noInitialRun: true,
            locateFile: path => path.endsWith('.wasm') ? assets.wasm : path,
            print: text => diagnostics.push(String(text)),
            printErr: text => diagnostics.push(String(text))
        });
        compiler.FS.writeFile(filename, source);
        const args = ['-msmall-int-bits=31', '-s', filename, '-o', 'output.mpy'];
        if (target === 'evo') args.push('-mcache-lookup-bc');
        args.push(filename);
        const status = compiler.callMain(args);
        if (status !== 0) {
            throw new Error(diagnostics.join('\n') || `Compilation failed (${status}).`);
        }
        const bytes = compiler.FS.readFile('output.mpy');
        self.postMessage({bytes, diagnostics}, [bytes.buffer]);
    } catch (error) {
        self.postMessage({error: diagnostics.join('\n') || error.message || String(error)});
    }
};
