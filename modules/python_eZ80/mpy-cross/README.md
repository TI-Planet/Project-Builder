# Browser bytecode compilers

These are two **compiler-only** builds of MicroPython `mpy-cross`. They compile
Python source into calculator bytecode without executing it. TI container
packaging belongs to the Project Builder caller.

| Target | Upstream | MPY header | Required compiler arguments |
| --- | --- | --- | --- |
| CE Python Edition | MicroPython 1.9.3 | `4d03021f` | `-msmall-int-bits=31` |
| Evo | MicroPython 1.13 | `4d05031f` | `-msmall-int-bits=31 -mcache-lookup-bc` |

Unicode is enabled on both targets. The CE compiler must not enable lookup
caching. Native, Viper and inline assembly emitters are disabled. A modern
MicroPython compiler is not interchangeable with either pinned version.

`sources.json` locks upstream tags, commits and source archive SHA-256 hashes.
`versions.json` records the build toolchain, all build input hashes and the
exact sizes/checksums of the generated JavaScript and WebAssembly assets.
Its `buildId` identifies the source locks, patches, settings and build recipe.
There are no server-side compilers or npm runtime dependencies.

## Browser contract

Load `ce-mpy-cross.js` or `evo-mpy-cross.js` with `importScripts()` in a classic
Web Worker. Each defines `createMpyCross(options)`, which resolves to a fresh
module exposing `FS` and synchronous `callMain(arguments)`:

```js
importScripts(compilerJavaScriptUrl);
const diagnostics = [];
const compiler = await createMpyCross({
    noInitialRun: true,
    locateFile: () => compilerWasmUrl,
    print: line => diagnostics.push(line),
    printErr: line => diagnostics.push(line)
});
compiler.FS.writeFile('/example.py', sourceBytes);
const status = compiler.callMain([
    '-msmall-int-bits=31', // Also '-mcache-lookup-bc' for Evo.
    '-s', 'example.py', '-o', '/output.mpy', '/example.py'
]);
if (status !== 0) throw new Error(diagnostics.join('\n'));
const bytecode = compiler.FS.readFile('/output.mpy');
```

Always supply the correct WASM URL through `locateFile`; the caller can append
its cache version there. The embedded `-s` filename must match the intended
Python filename. Use a fresh instance for every compilation and terminate the
Worker afterward, including on errors or cancellation. The Project Builder
caller caps UTF-8 source input at 256 KiB and times out a Worker after 30 seconds.
Serve the WASM asset as `application/wasm`; normal HTTP compression and immutable
cache versioning can be applied to these static assets.

## Bounded allocation and garbage collection

The default and maximum compiler arena are **16 MiB**. WebAssembly memory starts
at **32 MiB** and has a hard **64 MiB** maximum; the WASM stack is **1 MiB**, with
the upstream compiler's additional 40 KiB stack-depth check. Allocation failures
report an error instead of dereferencing a failed initial allocation.

The old conservative collector cannot discover references held in WebAssembly
locals using `setjmp`. Automatic collection is therefore explicitly disabled
for this one-shot compiler. The arena still supports the compiler's explicit
frees; exhausting it raises `MemoryError`. `wasm-gccollect.c` aborts any unexpected
collection attempt instead of scanning incomplete roots. Releasing the module
and terminating the Worker reclaims the entire arena. This avoids requiring
Asyncify or changing `callMain` into an asynchronous operation.

The maintained patches also fix two `size_t` mismatches in 1.9.3, signed iterator
index arithmetic in both versions, and a 1.13 conditional that assumed native
emitters existed whenever dynamic compiler options were enabled. All unrelated
`-Werror` checks remain active. Only the modern `unused-but-set-variable`
diagnostic is disabled for old upstream code. The version-header generator uses
locked metadata and a fixed build date, so checkout paths, git state and wall
clock time do not change the assets.

## Rebuild

Requirements: Python **3.12+**, GNU make, `patch`, `curl`, and Emscripten **6.0.9**
on `PATH` (activate that exact emsdk release and source its `emsdk_env.sh`).
Use a writable Emscripten cache (`EM_CACHE`) if the SDK installation is read-only.
All source extraction, patches, object files, logs and optional native reference
executables are placed in a new scratch directory. This directory must be empty
when supplied explicitly.

```sh
python3 build.py --cache-dir /tmp/pb-mpy-downloads \
    --build-dir /tmp/pb-mpy-build --native
node tests/verify.cjs /tmp/pb-mpy-build
```

Omit `--native` to build only the checked-in `.js`, `.wasm` and `versions.json`
assets. Source archives are downloaded only when missing and are verified before
extraction. To compare a rebuild without overwriting the checked-in assets:

```sh
python3 build.py --cache-dir /tmp/pb-mpy-downloads \
    --build-dir /tmp/pb-mpy-rebuild --output-dir /tmp/pb-mpy-output
```

The generated manifest has no timestamps or machine paths. Keep it together
with both asset pairs in a release. Re-run the native byte comparisons whenever
source pins, patches or the Emscripten version change.

## Verification and scope

`tests/verify.cjs` checks source/asset hashes, bytecode headers, ordinary language
constructs, Unicode, integer boundaries, complex constants, empty modules and a
large module within the 256 KiB input limit. With native references it compares
the full output bytes for that corpus. At the exact `-2**30` boundary, upstream
constant folding on a 32-bit compiler host retains an integer object while a
64-bit host emits a small-integer instruction. `tests/verify-boundary.py` uses
the pinned upstream MPY reader to verify that both encodings assign the same
integer and have the same remaining instructions; the compiler is not patched
to force byte identity across host widths. It also tests syntax errors, rejected native decorators,
real arena exhaustion, the hard arena limit and fresh instances after failures.
These exercise the compiler and its allocation policy under Node's WebAssembly
engine. Browser integration, TI container packaging and calculator runtime
acceptance require their own checks.

For byte comparisons, use the same relationship between the input path and the
`-s` filename on each host. In MPY3, an absolute input path plus a relative `-s`
filename interns one more string than using the same relative filename for both;
this shifts temporary qstr numbers in the raw bytecode. The serialized strings
are unchanged and the upstream loader relocates those operands on load.

The upstream MicroPython MIT licenses, Emscripten license and musl copyright
notices are included alongside the artifacts. The Project Builder additions in
this directory are provided under the MIT license.
