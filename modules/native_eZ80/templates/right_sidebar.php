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
if (!isset($pm))
{
    die('Ahem ahem');
}
/** @var \ProjectBuilder\native_eZ80Project $currProject */

?>
    <link rel="stylesheet" href="<?= $modulePath ?>css/right_sidebar.css">

    <div id="emu_container" class="unselectable">
        <div id="emu_intro"><br><b>WebCEmu</b>: load your CE ROM to emulate it:<br><br></div>

        <div id="emu_canvas_container">
            <canvas id="emu_canvas" class="emscripten" style="display:none;" oncontextmenu="event.preventDefault()" width="320" height="240"></canvas>
            <div id="screenshot_btn_container">
                <a target="_blank" download="<?php echo 'screenshot_' . $currProject->getInternalName() . '.png' ?>" class="btn btn-default btn-sm" onclick="emu_screenshot(this)">
                    <i class="glyphicon glyphicon-camera"></i> Screenshot
                </a>
                <button id="record_btn_start" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-record"></i> Record webm</button>
                <button id="record_btn_stop" class="btn btn-default btn-sm" style="display: none"><i class="glyphicon glyphicon-stop"></i> Stop recording</button>
            </div>
        </div>

        <div id="emu_keypad_buttons" style="display:none;">
            <script>
                const keypad = [
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
                    const btn = keypad[i];
                    if (i>0) {
                        document.write((i%5 === 0) ? '<br>' : '&nbsp;&nbsp;');
                    }
                    const specialClass = i < 5 ? 'topRowButton' : '';
                    document.write(`<button id="cemu_btn_${i}" class="btn btn-default btn-sm ${specialClass}" onmousedown="pressKey(${btn[1][1]}, ${btn[1][0]}, 1);"
                                           onmouseup="setTimeout(function() \{ pressKey(${btn[1][1]}, ${btn[1][0]}, 0); }, 50);">${btn[0]}</button>`);
                }
                // Move arrows where they should be (left, up, down, right)
                document.getElementById("cemu_btn_8" ).style.cssText = "width: 28px;height: 35px;top: 21px;left: 16px;margin: 0;";
                document.getElementById("cemu_btn_9" ).style.cssText = "width: 45px;height: 19px;line-height: 14px;right: 15px;bottom: 6px;margin: 0 12px;";
                document.getElementById("cemu_btn_13").style.cssText = "width: 45px;height: 18px;line-height: 14px;left: 22px;top: 2px;margin: 0 11px;";
                document.getElementById("cemu_btn_14").style.cssText = "width: 26px;height: 35px;bottom: 25px;right: 6px;padding-left: 4px;margin: 0 2px;";
                // Move [on] and [enter] where they should be
                document.getElementById("cemu_btn_45").style.cssText = "bottom: 5px;";
                document.getElementById("cemu_btn_49").style.cssText = "bottom: 5px;";
                // Assign "numeric" class to some of the appropriate buttons
                for (var i=0; i<3; i++) {
                    document.getElementById('cemu_btn_' + (31 + i)).className += ' numeric1';
                    document.getElementById('cemu_btn_' + (36 + i)).className += ' numeric2';
                    document.getElementById('cemu_btn_' + (41 + i)).className += ' numeric3';
                    document.getElementById('cemu_btn_' + (46 + i)).className += ' numeric4';
                }
            </script>
        </div>
        <hr id="emu_divider" style="margin:4px;display:none;"/>
        <div id="emu_control_buttons">
            <button id="emu_playpause_btn" class="btn btn-default btn-sm" style="display:none;" onclick="pauseEmul(!emul_is_paused)"><span id="pauseButtonIcon" class="glyphicon glyphicon-pause"></span> <span id="pauseButtonLabel">Pause</span> </button>
            <button id="emu_reset_btn" class="btn btn-default btn-sm" style="display:none;" onclick="resetEmul()"><span class="glyphicon glyphicon-asterisk"></span> Reset </button>
            <div id="varTransferDiv" style="display:none;">
                <input type="file" id="VarInputFile" name="file" class="inputfile inputfile-1" onChange="fileLoadFromInput(event)" multiple>
                <label for="VarInputFile" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-file"></span> <span class="fileInputLabel">TI file(s)</span></label>
            </div>
            <div id="ROMTransferDiv" style="display:inline-block;">
                <input type="file" id="ROMinputFile" name="file" class="inputfile inputfile-1" accept=".rom" onChange="fileLoadFromInput(event)">
                <label for="ROMinputFile" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-file"></span> <span class="fileInputLabel">ROM</span></label>
            </div>
        </div>

        <p style="margin-top: 4px; display: none">
            <label for="emuTransferProgress">Current transfer: </label>
            <progress id="emuTransferProgress" value="0" max="100"></progress>
        </p>

        <script>
            // Chrome bugfix ?!
            $("#VarInputFile, #ROMinputFile").on("click", () => {
                $("#emu_control_buttons").toggle(); setTimeout(() => { $("#emu_control_buttons").toggle(); }, 10);
            });
        </script>

        <script src="<?= $modulePath ?>js/emu/jquery.custom-file-input.js"></script>

        <script src="<?= cacheBusterPath("{$modulePath}js/emu/WebCEmu_utils.js") ?>"></script>
        <script type="module">
            import WebCEmu from './<?= cacheBusterPath("{$modulePath}js/emu/WebCEmu.js") ?>';
            window.CEmu = await WebCEmu();

            initWebCEmuUtils();

            localforage.getItem('ce_rom').then(function(ce_rom) {
                if (ce_rom !== null) {
                    const tryLoad = function() {
                        if (typeof(fileLoad) !== "undefined")
                        {
                            fileLoad(new Blob([ce_rom], {type: "application/octet-stream"}), 'CE.rom', true);
                            setTimeout(() => { $("#buildRunButton").removeClass("disabled").attr("disabled", false); }, 4000);
                        } else {
                            setTimeout(tryLoad, 250);
                        }
                    };
                    tryLoad();
                }
            }).catch(function(err) {
                console.log("Error while getting ROM from LF", err);
            });

            document.getElementById('emu_canvas_container').addEventListener('mouseenter', function() { document.getElementById('screenshot_btn_container').style.display = 'block'; });
            document.getElementById('emu_canvas_container').addEventListener('mouseleave', function() { document.getElementById('screenshot_btn_container').style.display = 'none'; });

            window.emu_screenshot = function(btn)
            {
                btn.href = document.getElementById('emu_canvas').toDataURL('image/png');
                return true;
            }
        </script>
        <script type='text/javascript'>
            const debounced_resize = function(){
                const wh = window.innerHeight;
                let el = document.getElementById('emu_keypad_buttons');
                let el2 = document.getElementById('emu_control_buttons');
                if (wh < 800)
                {
                    if (typeof window.InstallTrigger !== 'undefined') { // isFirefox
                        el.style.transformOrigin = 'center 0';
                        el.style.transform = 'scale(0.7)';
                        el2.style.position = 'absolute';
                        el2.style.bottom = '27px';
                        el2.style.left = '22px';
                        el2.style.transform = 'scale(.75)';
                        el2.style.transformOrigin = 'center 0';
                    } else {
                        el.style.zoom = '70%';
                        el.style.backgroundSize = '70%';
                    }
                } else {
                    if (typeof window.InstallTrigger !== 'undefined') { // isFirefox
                        el.style.transformOrigin = 'unset';
                        el.style.transform = 'unset';
                        el2.style.position = 'unset';
                        el2.style.bottom = 'unset';
                        el2.style.left = 'unset';
                        el2.style.transform = 'unset';
                        el2.style.transformOrigin = 'unset';
                    } else {
                        el.style.zoom = 'unset';
                        el.style.backgroundSize = '100%';
                    }
                }
            };
            let debounced_resize_timeout;
            window.addEventListener('resize', () => {
                clearTimeout(debounced_resize_timeout);
                debounced_resize_timeout = setTimeout(debounced_resize, 200);
            });
            debounced_resize();
        </script>

    </div>

    <div id="cemu_notice">
        <span class="copyright">Emulation powered by CEmu (see <a href="https://github.com/CE-Programming/CEmu" target="_blank">on GitHub</a>)</span>
    </div>

    <script type="text/javascript"> /* Very ugly workaround for the WebRTC no-local restriction */ cerror = console.error; console.error = console.warn;</script>
    <script type="text/javascript" src="https://cdn.webrtc-experiment.com/screenshot.js"></script>
    <script type="text/javascript" src="https://cdn.webrtc-experiment.com/RecordRTC.js"></script>
    <script type="text/javascript"> /* Very ugly workaround for the WebRTC no-local restriction */ console.error = cerror;</script>
    <script type="text/javascript">
        const recorder = RecordRTC(document.getElementById('emu_canvas'), { type: 'canvas' });
        const btn_start = document.getElementById('record_btn_start');
        const btn_stop = document.getElementById('record_btn_stop');
        btn_start.onclick = function () {
            recorder.startRecording();
            btn_start.style.display = 'none';
            btn_stop.style.display = 'inline';
        };
        btn_stop.onclick = function () {
            btn_start.style.display = 'inline';
            btn_stop.style.display = 'none';
            recorder.stopRecording(url => {
                const a = document.createElement("a");
                document.body.appendChild(a);
                a.style.display = "none";
                a.href = url;
                a.download = "recording_" + "<?= $currProject->getInternalName() ?>_" + (+ new Date()) + ".webm";
                a.click();
                window.URL.revokeObjectURL(url);
            });
        };
    </script>
