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

/* Additions to the common PB JS things
 * This concerns users with enough rights to edit etc.
 * Common things (not much) can go directly into js_pre.php
 */

let build_output_raw = [];
var build_output = [];
var build_check  = [];
var lastSavedSource = '';

function applyPrgmNameChange(name)
{
    proj.prgmName = name;
    document.getElementById("prgmNameSpanInList").innerHTML = name;
    document.getElementById("prgmNameSpan").innerHTML = name;
    document.getElementById("prgmNameInput").value = name;
    saveProjConfig();
}

function changePrgmName()
{
    let name = prompt("Enter the new program name (8 letters max, A-Z 0-9, starts by a letter)", "");
    if (name != null)
    {
        name = name.toUpperCase();
        if (!name.match(/^[A-Z][A-Z0-9]{0,7}$/))
        {
            alert("Invalid name.");
        } else {
            removeClass(document.querySelector("#prgmNameContainer span.loadingicon"), "hidden");
            ajax("ActionHandler.php", `id=${proj.pid}&action=setInternalName&internalName=${name}`, () => {
                addClass(document.querySelector("#prgmNameContainer span.loadingicon"), "hidden");
                applyPrgmNameChange(name);
            });
        }
    }
}

function downloadZipExport()
{
    // nothing else to do?
    document.getElementById('zipDlForm').submit();
}

function renameFile(oldName)
{
    $('.tooltip').hide();
    let err = false;
    const newName = prompt("Enter the new file name (Chars: a-z,A-Z,0-9,_ Extension: c,h,asm)", oldName);
    if (newName === null || !isValidFileName(newName))
    {
        err = true;
        if (newName) alert("Invalid name.");
    }
    if (newName === oldName) {
        return;
    }
    if (!err)
    {
        saveFile(() => {
            ajax("ActionHandler.php", `id=${proj.pid}&action=renameFile&oldName=${oldName}&newName=${newName}`, () => {
                proj.currFile = newName;
                saveProjConfig();
                goToFile(newName);
            });
        });
    }
}

const _saveFile_impl = (callback) =>
{
    const saveButton = document.getElementById('saveButton');

    const currSource = editor.getValue();
    if (currSource.length > 0 && currSource != lastSavedSource)
    {
        removeClass(saveButton.children[1], "hidden");
        saveButton.disabled = true;

        ajax("ActionHandler.php", `id=${proj.pid}&file=${proj.currFile}&action=save&source=${encodeURIComponent(currSource)}`, () => {
            savedSinceLastChange = true; lastChangeTS = (new Date).getTime();
            lastSavedSource = currSource;
            if (typeof callback === "function") callback();
        }, () => {
            saveButton.disabled = false;
        }, () => {
            addClass(saveButton.children[1], "hidden");
        });

    } else {
        saveButton.disabled = true;
        savedSinceLastChange = true; lastChangeTS = (new Date).getTime();
        if (typeof callback === "function") callback();
    }
    saveProjConfig();
};

globalSaveFileRetryCount = 0;
function saveFile(callback)
{
    if (proj.is_multi)
    {
        if (!globalSyncOK || globalSaveFileRetryCount >= 3)
        {
            alert("Syncing failed, saving now may overwrite changes and data may be lost. Please try again later (and make a local backup)");
            globalSaveFileRetryCount = 0;
            return;
        }
        if ((new Date).getTime() - lastChangeTS > 30000)
        {
            firepad.client_.updateCursor();
            firepad.client_.sendCursor(firepad.client_.cursor);
            lastChangeTS = (new Date).getTime();
            console.log("Current session is old, trying to sync with Firepad... Retry count == " + globalSaveFileRetryCount);
            globalSaveFileRetryCount++;
            setTimeout(function(){ saveFile(callback); }, 200);
            return;
        }
    }
    globalSaveFileRetryCount = 0;
    _saveFile_impl(callback);
}

function isValidFileName(name)
{
    return /^[a-zA-Z0-9_]+\.(c|h|asm)$/i.test(name);
}

function deleteCurrentFile()
{
    if (window.confirm("Do you really want to delete this file?"))
    {
        if (proj.currFile && isValidFileName(proj.currFile))
        {
            ajax("ActionHandler.php", `id=${proj.pid}&file=${proj.currFile}&action=deleteCurrentFile`, () => {
                const idx = proj.files.indexOf(proj.currFile);
                if (idx > -1)
                {
                    proj.files.splice(idx, 1);
                    saveProjConfig();
                }
                proj.currFile = "main.c";
                saveProjConfig();
                goToFile(proj.currFile);
            });
        }
    }
}

function addFile(name)
{
    let err = false;
    if (!name || !isValidFileName(name))
    {
        name = prompt("Enter the new file name (letters, numbers, underscores, and with .c, .h, or .asm extension)");
        if (name === null || !isValidFileName(name))
        {
            err = true;
            if (name) alert("Invalid name.");
        }
    }
    if (!err)
    {
        saveFile(() => {
            ajax("ActionHandler.php", `id=${proj.pid}&action=addFile&fileName=${name}`, () => {
                proj.files = proj.files.concat([ name ]);
                proj.currFile = name;
                saveProjConfig();
                goToFile(name);
            });
        });
    }
}

function buildAndDownload()
{
    buildAndGetLog(() => {
        document.getElementById('builddlButton').removeAttribute("onclick");
        document.getElementById('postForm').submit();
        document.getElementById('builddlButton').setAttribute("onclick", 'buildAndDownload(); return false;');
    });
}

function buildAndRunInEmu()
{
    // TODO: use a flag
    if ($("#rightSidebarContent").css("right")[0] === "-")
    {
        toggleRightSidebar();
    }
    if (emul_is_inited)
    {
        buildAndGetLog(() => {
            ajaxGetArrayBuffer("ActionHandler.php", $("#postForm").serialize(), file => {
                pauseEmul(false);
                fileLoad(new Blob([file], {type: "application/octet-stream"}), `${proj.prgmName}.8xp`, false);
                setTimeout(() => {
                    setTimeout(() => { sendKey(0x9CFC); }, 0); // Asm(
                    setTimeout(() => { sendKey(0xDA); }, 250); // prgm
                    setTimeout(() => { sendStringKeyPress(proj.prgmName); }, 500);
                    setTimeout(() => { sendKey(0x05); }, 500 + 150 + 250 * proj.prgmName.length); // Enter
                }, 500);
            });
        });
    } else {
        alert("The emulator isn't ready yet - Did you load a ROM?");
    }
}

function getBuildLogAndUpdateHints()
{
    ajax("ActionHandler.php", `id=${proj.pid}&action=getBuildLog`, result => {
        build_output_raw = JSON.parse(result);
        build_output = parseBuildLog(build_output_raw);
        updateHints(true);
    });
}

function getCheckLogAndUpdateHints()
{
    ajax("ActionHandler.php", `id=${proj.pid}&action=getCheckLog`, result => {
        build_check = parseCheckLog(JSON.parse(result));
        updateHints(true);
    });
}

function cleanProj(callback)
{
    const cleanButton = document.getElementById('cleanButton');
    cleanButton.disabled = true;

    const params = `id=${proj.pid}&action=clean`;
    ajax("ActionHandler.php", params, result => {
        // Clear build timestamp
        const buildTimestampElement = document.getElementById('buildTimestamp');
        buildTimestampElement.parentNode.className = buildTimestampElement.className = "";
        buildTimestampElement.innerText = "(none)";

        // Clear console
        document.getElementById('consoletextarea').value = "";

        cleanButton.disabled = false;

        if (typeof callback === "function") {
            callback();
        }
    });
}

function buildAndGetLog(callback)
{
    const buildButton = document.getElementById('buildButton');
    const cleanButton = document.getElementById('cleanButton');
    const builddlButton = document.getElementById('builddlButton');
    const buildRunButton = document.getElementById('buildRunButton');
    const zipDlCaretButton = document.getElementById('zipDlCaretButton');
    cleanButton.disabled = buildButton.disabled = builddlButton.disabled = buildRunButton.disabled = zipDlCaretButton.disabled = true;
    removeClass(buildButton.children[1], "hidden");

    saveFile(() => {
        // build output
        const params = `id=${proj.pid}&prgmName=${proj.prgmName}&action=build`;
        ajax("ActionHandler.php", params, result => {
            build_output_raw = JSON.parse(result);
            build_output = parseBuildLog(build_output_raw);

            let buildStatusClass = "buildOK";
            if (result.includes("ERROR: Object file(s) deleted because of option unresolved=fatal"))
            {
                alert("Fatal build error (undefined/unresolved calls).\nCheck the build log");
                buildStatusClass = "buildError"
            } else if (result.includes("\\tERROR") || result.includes("Internal Error")) {
                alert("Fatal build error.\nCheck the build log");
                buildStatusClass = "buildError"
            } else {
                if (typeof callback === "function") {
                    callback();
                }
            }

            // Update build timestamp
            const buildTimestamp = build_output_raw.shift(); // Remove the timestamp
            const buildTimestampElement = document.getElementById('buildTimestamp');
            buildTimestampElement.parentNode.className = "";
            buildTimestampElement.className = buildStatusClass;
            buildTimestampElement.innerText = (build_output_raw === null) ? '(Error)' : (new Date(+(`${buildTimestamp}000`)).toLocaleTimeString());

            // Update console
            const consoletextarea = document.getElementById('consoletextarea');
            consoletextarea.value = build_output_raw.join("\n");
            consoletextarea.scrollTop = consoletextarea.scrollHeight;

            savedSinceLastChange = true; lastChangeTS = (new Date).getTime();

            if (asmBeingShown) {
                const oldCursor = editor.getCursor();
                dispSrc();
                dispSrc(() => {
                    editor.setCursor(oldCursor);
                    const cursor_line_div = document.querySelector("div.CodeMirror-activeline");
                    if (cursor_line_div) {
                        cursor_line_div.scrollIntoView();
                    }
                });
            }

            // Call cppcheck
            ajax("ActionHandler.php", `id=${proj.pid}&action=getCheckLog`, result => {
                build_check = parseCheckLog(JSON.parse(result));
                updateHints(false);
                addClass(buildButton.children[1], "hidden");
                cleanButton.disabled = buildButton.disabled = builddlButton.disabled = buildRunButton.disabled = zipDlCaretButton.disabled = false;
            });
        });
    });
}

function parseBuildLog(log)
{
    const arr = [];
    if (log !== null && log.constructor === Array)
    {
        for (let i = 0; i < log.length; i++)
        {
            const regex = /(\w+\.(?:c|h|asm))\s+\((\d+),(\d+)\)\s+:\s+(.*?)\s+\((\d+)\)\s(.*?)$/gmi;
            const matches = regex.exec(log[i]);
            if (matches !== null)
            {
                arr.push({file: matches[1], line: parseInt(matches[2]), col: parseInt(matches[3]), type: matches[4].toLowerCase(), code: matches[5], text: matches[6]})
            }
        }
    } else {
        console.log("Error parseLog: log wasn't an array... ?");
    }
    return arr;
}

function parseCheckLog(log)
{
    const arr = [];
    if (log !== null && log.constructor === Array)
    {
        for (let i = 0; i < log.length; i++)
        {
            const regex = /^\[(\w+\.[ch]):(\d+)\]: \((.*?)\) (.*)$/gmi;
            const matches = regex.exec(log[i]);
            if (matches !== null)
            {
                arr.push({file: matches[1], line: parseInt(matches[2]), col: 0, type: matches[3], code: "", text: matches[4]})
            }
        }
    } else {
        console.log("Error parseCheck: log wasn't an array... ?");
    }
    return arr;
}

function rightSidebar_toggle_callback(willBeHidden)
{
    if (typeof emul_is_inited !== "undefined" && emul_is_inited)
    {
        pauseEmul(willBeHidden);
    }
}

window.addEventListener('resize', event => {
    $(".CodeMirror-merge, .CodeMirror-merge .CodeMirror").css("height", `${.75*($(document).height())}px`);
});

window.addEventListener('keydown', event => {
    if (event.ctrlKey || event.metaKey) {
        switch (String.fromCharCode(event.which).toLowerCase()) {
            case 's':
                event.preventDefault();
                saveFile();
                break;
        }
    }
});

/***** Pause emulation when the page isn't visible *****/

    // Set the name of the hidden property and the change event for visibility
let hidden;

let visibilityChange;
if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support
    hidden = "hidden";
    visibilityChange = "visibilitychange";
} else if (typeof document.mozHidden !== "undefined") {
    hidden = "mozHidden";
    visibilityChange = "mozvisibilitychange";
} else if (typeof document.msHidden !== "undefined") {
    hidden = "msHidden";
    visibilityChange = "msvisibilitychange";
} else if (typeof document.webkitHidden !== "undefined") {
    hidden = "webkitHidden";
    visibilityChange = "webkitvisibilitychange";
}

function handleVisibilityChange()
{
    if (document.hidden && typeof emul_is_inited !== "undefined" && emul_is_inited)
    {
        pauseEmul(true);
    }
}

// Warn if the browser doesn't support addEventListener or the Page Visibility API
if (typeof document.addEventListener === "undefined" || typeof document[hidden] === "undefined") {
    console.log("Your browser is old, some things won't be working as expected :(");
} else {
    document.addEventListener(visibilityChange, handleVisibilityChange, false);
}