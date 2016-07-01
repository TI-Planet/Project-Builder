<?php
/*
 * Part of TI-Planet's Project Builder
 * (C) Adrien "Adriweb" Bertrand
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/* This content will be included and displayed.
   This page should not be called directly. */
if (!isset($pb))
{
    die("Ahem ahem");
}
/** @var \ProjectBuilder\native_eZ80Project $currProject */

?>
    <link rel="stylesheet" href="<?= $modulePath ?>css/right_sidebar.css">

    <script src="<?= $modulePath ?>js/emu/cemu_web_utils.js"></script>

    <div id="emu_container" class="unselectable">
        <br>
        <div id="emu_canvas_container">
            <canvas id="emu_canvas" class="emscripten" style="display:none;" oncontextmenu="event.preventDefault()" width="320" height="240"></canvas>
            <div id="screenshot_btn_container">
                <a target="_blank" download="<?php echo 'screenshot_' . $currProject->getInternalName() . '.png' ?>" class="btn btn-default btn-sm" onclick="emu_screenshot(this)">
                    <i class="glyphicon glyphicon-camera"></i> Screenshot</button>
                </a>
            </div>
        </div>

        <br>
        <div id="emu_keypad_buttons" style="display:none;">
            <script>
                var keypad = [
                    ["y=", [4,1]], ["wind", [3,1]], ["zoom", [2,1]], ["trace", [1,1]], ["graph", [0,1]],
                    ["2nd", [5,1]], ["mode", [6,1]], ["del", [7,1]], ["◀", [1,7]], ["<big>▲</big>", [3,7]],
                    ["alpha", [7,2]], ["XTθ<i>n</i>", [7,3]], ["stat", [7,4]], ["<big>▼</big>", [0,7]], ["▶", [2,7]],
                    ["math", [6,2]], ["apps", [6,3]], ["prgm", [6,4]], ["vars", [6,5]], ["clear", [6,6]],
                    ["x<sup>-1</sup>", [5,2]], ["sin", [5,3]], ["cos", [5,4]], ["tan", [5,5]], ["ᐱ", [5,6]],
                    ["x<sup>2</sup>", [4,2]], [",", [4,3]], ["(", [4,4]], [")", [4,5]], ["/", [4,6]],
                    ["log", [3,2]], ["<b>7</b>", [3,3]], ["<b>8</b>", [3,4]], ["<b>9</b>", [3,5]], ["*", [3,6]],
                    ["ln", [2,2]], ["<b>4</b>", [2,3]], ["<b>5</b>", [2,4]], ["<b>6</b>", [2,5]], ["-", [2,6]],
                    ["sto→", [1,2]], ["<b>1</b>", [1,3]], ["<b>2</b>", [1,4]], ["<b>3</b>", [1,5]], ["+", [1,6]],
                    ["on", [0,2]], ["<b>0</b>", [0,3]], [".", [0,4]], ["(-)", [0,5]], ["enter", [0,6]]
                ];
                for (var i = 0; i < keypad.length; i++) {
                    var btn = keypad[i];
                    if (i>0) {
                        document.write((i%5 == 0) ? '<br>' : '&nbsp;&nbsp;');
                    }
                    var specialClass = i<5 ? 'topRowButton' : '';
                    document.write('<button class="btn btn-default btn-sm '+ specialClass +'" onmousedown="pressKey('+btn[1][1]+', '+btn[1][0]+', 1);" '
                                        + 'onmouseup="setTimeout(function() { pressKey('+btn[1][1]+', '+btn[1][0]+', 0); }, 100);">'+btn[0]+'</button>');
                }
            </script>
        </div>
        <hr id="emu_divider" style="margin:12px;display:none;"/>
        <div>
            <button id="emu_playpause_btn" class="btn btn-default btn-sm" style="display:none;" onclick="pauseEmul(!emul_is_paused)"><span id="pauseButtonIcon" class="glyphicon glyphicon-pause"></span> <span id="pauseButtonLabel">Pause</span> emulation </button>
            <button id="emu_reset_btn" class="btn btn-default btn-sm" style="display:none;" onclick="resetEmul()"><span class="glyphicon glyphicon-asterisk"></span> Reset calculator </button>
            <br><br>
            <div id="varTransferDiv" style="display:none;">
                Variable transfer:
                <input type="file" id="VarInputFile" name="file" class="inputfile inputfile-1" onChange="fileLoadFromInput(event)" multiple>
                <label for="VarInputFile" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-file"></span> <span class="fileInputLabel">TI file(s)</span></label>
            </div>
            <div style="height:4px;"></div>
            <div id="ROMTransferDiv">
                Emulator ROM:
                <input type="file" id="ROMinputFile" name="file" class="inputfile inputfile-1" accept=".rom" onChange="fileLoadFromInput(event)">
                <label for="ROMinputFile" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-file"></span> <span class="fileInputLabel">ROM image...</span></label>
            </div>
        </div>

        <script src="<?= $modulePath ?>js/emu/jquery.custom-file-input.js"></script>

        <script type='text/javascript'>
            var Module = {
                memoryInitializerPrefixURL:'<?= $modulePath ?>js/emu/',
                preRun: [],
                postRun: [],
                print: function() { },
                printErr: function(text) {
                    if (arguments.length > 1) text = Array.prototype.slice.call(arguments).join(' ');
                    alert(text);
                },
                canvas: (function() { return document.getElementById('emu_canvas'); })(),
                setStatus: function(text) { console.log(text); },
                totalDependencies: 0,
                monitorRunDependencies: function(left) { }
            };

            window.onerror = function(event) {
                Module.setStatus = function(text) { if (text) alert('[post-exception status] ' + text); };
            };

            var script = document.createElement('script');
            script.src = "<?= $modulePath ?>js/emu/cemu_web.js";
            document.body.appendChild(script);

            document.getElementById('emu_canvas_container').addEventListener('mouseenter', function() { document.getElementById('screenshot_btn_container').style.display = 'block'; });
            document.getElementById('emu_canvas_container').addEventListener('mouseleave', function() { document.getElementById('screenshot_btn_container').style.display = 'none'; });

            function emu_screenshot(btn)
            {
                btn.href = document.getElementById('emu_canvas').toDataURL('image/png');
                return true;
            }
        </script>

    </div>

    <div id="cemu_notice">
        <span class="copyright">Emulation powered by CEmu (see <a href="https://github.com/CE-Programming/CEmu" target="_blank">on GitHub</a>)</span>
    </div>
