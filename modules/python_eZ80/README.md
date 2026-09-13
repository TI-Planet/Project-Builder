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

## TI Python analysis and completion

The backend adds analysis-only SDK import paths to Pylint for `python_eZ80`.
Project modules take precedence, followed by the calculator SDK and then host Python (important for `turtle`). Unknown modules/members and ordinary Python
errors remain diagnostics; no import or name warnings are globally suppressed.
Other project types retain their existing Pylint configuration.

`internal/python/` defines the documented TI APIs: `ti_system`, `ti_draw`,
`ti_image`, `ti_plotlib`, `ti_hub`, `ti_rover`, `brightns`, `sound`, and `color`.
Sources are TI's **TI-PyAppPrgG_v570_EN.pdf**, printed pages 23–35, 52–53,
the alphabetical reference on pages 69–136, and the module tables on page 159.
Individual definitions include page references. Undocumented argument lists
remain permissive. These are API definitions, not calculator emulation.

`internal/menus/` contains the supplied `tipycomp/ports/evo` menu files, including
its generated English/French micro:bit variants. The manifest records every
original relative path; 77 source files are stored as 62 byte-identical-deduplicated
files, with original notices retained. Two additional menus are extracted from the
supplied modern and legacy turtle AppVars. Dash, Tello, micro:bit and related names
are extracted from insertion text into `internal/menu_python/`. Duplicate symbols
are merged across variants. Labels provide hints; menu-only function arguments
remain permissive because insertion templates do not specify all valid calls.
The bundled SDK is a union of these APIs; availability still depends on installed
calculator modules and hardware. Merely mentioned add-ons without API definitions
in these sources are not invented.

Generate the checked-in menu stubs and completion metadata with Python 3.9+:

```sh
python3 internal/build_python_sdk.py
PYLINT=pylint python3 tests/python_sdk_test.py
```

The SDK also covers `turtle` (TI Turtle 2.0.0), `ce_turtl`, `ce_chart`, `ce_quivr`,
and legacy `ti_graphics`. See [internal/SOURCES.md](internal/SOURCES.md) for the
AppVar extraction, turtle guide references, and full-size TI-Planet capture links.

`internal/python_sdk.json` combines the guide definitions with menu symbols,
preferring the guide's signatures where both exist. CodeMirror offers module
names in imports, members after module/object qualifiers, imported names and
aliases, wildcard imports, and members of simple constructor assignments such as
`t = Turtle()`. Re-exported SDK namespaces such as `ce_chart.plt` are recognized. Comments and strings are excluded from import
scanning. Ctrl/Cmd-hover hints include the source documentation and menu labels.

Deploy the backend changes, `internal/python/`, `internal/menu_python/`,
`internal/python_sdk.json`, the updated templates, and JavaScript together.
Generation does not run on the server. SDK files stay outside users' project
sources and are not included in source, ZIP, bytecode, or transfer exports.

Local validation used Pylint 4.0.8/Astroid 4.0.4, the real PHP backend actions,
and Chromium with actual PB templates, CodeMirror and AJAX responses. It covers
valid TI imports, retained typo/signature diagnostics, project-module precedence,
and SDK completion across source/menu navigation. Deployment and calculator
execution are separate from these checks.

## Evo 7.1 frozen modules

The SDK additionally includes `ti_rover_bt` version **1.0.0.65** and 28 neighboring
Hub sensor/control modules from the supplied **TI84Evo_Package.84pk2**, OS
**7.1.0.4421**. The package SHA-256 is
`c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df`;
its extracted OS payload SHA-256 is
`83d5fb93f5896a5722d7bc4e17e5a6906aa90b33ca68d5b59bd047786d90922d`.

Definitions come from the frozen bytecode's actual module/class bindings,
argument names, default values, and inheritance. Each definition records its
raw-code descriptor address in a source comment. Public functions and classes
are included; internal Bluetooth protocol classes are omitted from completion.
The analysis stubs perform no device discovery or hardware I/O.

Rover BT coverage includes mathematical paths, motion, pen control, motor/RGB
controls, battery/status, and `ranger`, `digital`, and `led` objects. For example:

```python
import ti_rover_bt as rv
rv.forward()                 # dist=1, speed=None, acc=None
rv.drive_line(1, 0)
light = rv.led(1)            # inherits digital controls
light.on()
```

The neighboring modules are `analgout`, `analogin`, `bbport`, `collect`,
`colorinp`, `conservo`, `dht`, `digital`, `led`, `light`, `lightlvl`, `loudness`,
`magnetic`, `moisture`, `potentio`, `power`, `ranger`, `relay`, `rgb`, `rgb_arr`,
`servo`, `speaker`, `squarewv`, `temperat`, `thermist`, `timer`, `vernier`, and
`vibmotor`. Class names retain the firmware spelling, such as
`analogin.analog_in`, `conservo.continuous_servo`, and `timer.hub_time`.
`color.off` and the Evo `ti_system.get_key` alias are also recognized.

The firmware catalog contains inconsistencies: `ti_rover_bt` does not export
`servo` or `module_version`, and `digital.pwm` takes `(freq, duty=None, time=None)`.
The SDK follows the actual definitions. The separate `servo` module is available.
This validates API names/signatures for editing and linting; hardware execution
and module availability on other OS versions are not established by these checks.
