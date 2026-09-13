# CE / Evo Python Project Builder

The normal Python AppVar, Python source and project ZIP downloads retain their
existing behavior. The download menu additionally offers **Bytecode Module
.8xv** (CE Python Edition) and **Bytecode Module .8mp2** (Evo OS 7.1+).

Bytecode export takes a snapshot of the selected Python source, compiles it in a
fresh Web Worker and packages the resulting MPY stream through TIVarsLib. No
compiler runs on the web server. Compiler assets are loaded only when requested;
the export notification supports cancellation. The source filename's basename
is the import name; its uppercase form is the TI variable name. Bytecode exports
require 1–8 letters/digits/underscores, starting with a letter, without silently
truncating names. Existing source exports keep their own naming behavior.

See [mpy-cross/README.md](mpy-cross/README.md) for compiler sources, reproducible
builds, versions, licenses, resource limits and regression checks.

## Menu files

Use matching, case-sensitive basenames: `FOOBAR.py` and `FOOBAR.menu`. The file
tabs group these pairs using the same layout as native C/header files. Create,
rename, import, save, delete and ZIP-export `.menu` files through the usual PB
controls. New menu files start empty; use **Add group** to begin.

Opening a `.menu` file displays a graphical menu editor, a raw directive editor,
live calculator preview and diagnostics.
The raw editor highlights directives and tags, with Ctrl/Cmd-Space completion.
Groups, pages, items, import entries, insertion offsets, symbols and annotations
can be edited in place. The widget uses the ordinary saved/Firepad document, with
the same read-only state and shared undo/redo shortcuts. Python lint and outline
helpers are disabled for menu files.

Either file in a pair can initiate a bytecode download. The active editor buffer
is snapshotted immediately (including unsaved edits); companion files are read
from the saved project through its authenticated source endpoint. Normal file
navigation saves the current buffer before changing tabs. Missing Python files,
duplicate matching extensions and unreadable companions stop the export.
An unrelated `.menu` is never included. Without a matching menu, `.py` bytecode
export still works normally.

Menu text and placeholder tags are passed unexpanded to the packer: a `PYMP`
metadata record for CE (as in `tipycomp`) or a subtype-2 menu section for Evo.
The packer adds record/section terminators. Menu saves do not strip trailing
whitespace; the text editor uses its normal newline handling. Graphical edits
serialize a canonical menu; unknown/invalid raw directives remain editable and
are not silently rewritten. Preview diagnostics follow Evo's reference parser;
CE firmware limits may differ. Menus are included only in bytecode exports.
Source Python export and ordinary calculator transfers keep their behavior;
while editing a menu, the primary download saves the `.menu` itself.

Run `node tests/menu_editor_test.cjs` for parser/serialization regressions.

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
