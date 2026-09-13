# CE / Evo Python Project Builder

The normal Python AppVar, Python source and project ZIP downloads retain their
existing behavior. The download menu additionally offers **Bytecode Module
.8xv** (CE Python Edition) and **Bytecode Module .8mp2** (Evo OS 7.1+).

Bytecode export takes a snapshot of the active Python buffer, compiles it in a
fresh Web Worker and packages the resulting MPY stream through TIVarsLib. No
compiler runs on the web server. Compiler assets are loaded only when requested;
the export notification supports cancellation. The source filename's basename
is the import name; its uppercase form is the TI variable name. Bytecode exports
require 1–8 letters/digits/underscores, starting with a letter, without silently
truncating names. Existing source exports keep their own naming behavior.

See [mpy-cross/README.md](mpy-cross/README.md) for compiler sources, reproducible
builds, versions, licenses, resource limits and regression checks.

## Shared packaging assets

The bundled `../_shared/TIVarsLib.js` and `.wasm` were rebuilt from
`tivars_lib_cpp` commit `a3672710cdb36fac32e4c685887590cfad78eded` using its
`Makefile.emscripten` defaults and Emscripten 6.0.9. Both files must be deployed
together. The Python page supplies versioned URLs for the JS and its WASM
companion, as well as both compiler asset pairs and the compiler Worker.

Deploy `js/.htaccess` with `js/mpy_worker.js`: the Worker response needs
`Cross-Origin-Embedder-Policy: require-corp` to load inside PB's isolated page.
The transfer asset rule in `../_shared/.htaccess` does not cover this directory.

CE bytecode uses `PythonModuleAppVar` (`PYMP`, MPY v3). Evo uses a subtype-2
`PythonAppVar` followed by `convertToEvoPythonFormat('8mp2')` (MPY v5, type 18).
Both modules are archived. No padding is appended to Evo objects. The existing
calculator transfer bridge can adapt Evo module wrappers to the connected OS;
the ordinary Send to calculator action continues to send source programs.

Local validation includes real browser Worker compilation under PB's document
COOP/COEP headers (including a missing-Worker-header failure check), download actions,
native compiler comparisons, TI container parsing/roundtrips, existing source
export regression checks, syntax errors, cancellation and resource limits.
It does not establish deployment or execution on physical calculators.
