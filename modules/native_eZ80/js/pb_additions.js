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

var build_output_raw = [];
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
    var name = prompt("Enter the new program name (8 letters max, A-Z 0-9, starts by a letter)", "");
    if (name != null)
    {
        name = name.toUpperCase();
        if (!name.match(/^[A-Z][A-Z0-9]{0,7}$/))
        {
            alert("Invalid name.");
        } else {
            removeClass(document.querySelector("#prgmNameContainer span.loadingicon"), "hidden");
            ajax("ActionHandler.php", "id=" + proj.pid + "&action=setInternalName&internalName="+name, function() {
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
    var err = false;
    var newName = prompt("Enter the new file name (Chars: a-z,A-Z,0-9,_ Extension: c,h,asm)", oldName);
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
        saveFile(function (){
            ajax("ActionHandler.php", "id=" + proj.pid + "&action=renameFile&oldName="+oldName+"&newName="+newName, function() {
                proj.currFile = newName;
                saveProjConfig();
                goToFile(newName);
            });
        });
    }
}

function saveFile(callback)
{
    var saveButton = document.getElementById('saveButton');

    var currSource = editor.getValue();
    if (currSource.length > 0 && currSource != lastSavedSource)
    {
        removeClass(saveButton.children[1], "hidden");
        saveButton.disabled = true;

        ajax("ActionHandler.php", "id=" + proj.pid + "&file="+proj.currFile + "&action=save&source="+encodeURIComponent(currSource), function() {
            savedSinceLastChange = true;
            lastSavedSource = currSource;
            if (typeof callback === "function") callback();
        }, function() {
            saveButton.disabled = false;
        }, function() {
            addClass(saveButton.children[1], "hidden");
        });

    } else {
        saveButton.disabled = true;
        savedSinceLastChange = true;
        if (typeof callback === "function") callback();
    }
    saveProjConfig();
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
            ajax("ActionHandler.php", "id=" + proj.pid + "&file="+proj.currFile + "&action=deleteCurrentFile", function() {
                var idx = proj.files.indexOf(proj.currFile);
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
    var err = false;
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
        saveFile(function() {
            ajax("ActionHandler.php", "id=" + proj.pid + "&action=addFile&fileName="+name, function() {
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
    buildAndGetLog(function() {
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
        buildAndGetLog(function ()
        {
            ajaxGetArrayBuffer("ActionHandler.php", $("#postForm").serialize(), function (file)
            {
                pauseEmul(false);
                fileLoad(new Blob([file], {type: "application/octet-stream"}), proj.prgmName + ".8xp", false);
                setTimeout(function ()
                {
                    setTimeout(function () { sendKey(0x9CFC); }, 0); // Asm(
                    setTimeout(function () { sendKey(0xDA); }, 250); // prgm
                    setTimeout(function () { sendStringKeyPress(proj.prgmName); }, 500);
                    setTimeout(function () { sendKey(0x05); }, 500 + 150 + 250 * proj.prgmName.length); // Enter
                }, 500);
            });
        });
    } else {
        alert("The emulator isn't ready yet - Did you load a ROM?");
    }
}

function getBuildLogAndUpdateHints()
{
    ajax("ActionHandler.php", "id=" + proj.pid + "&action=getBuildLog", function(result) {
        build_output_raw = JSON.parse(result);
        build_output = parseBuildLog(build_output_raw);
        updateHints(true);
    });
}

function getCheckLogAndUpdateHints()
{
    ajax("ActionHandler.php", "id=" + proj.pid + "&action=getCheckLog", function(result) {
        build_check = parseCheckLog(JSON.parse(result));
        updateHints(true);
    });
}

function cleanProj(callback)
{
    var cleanButton = document.getElementById('cleanButton');
    cleanButton.disabled = true;

    var params = "id="+proj.pid + "&action=clean";
    ajax("ActionHandler.php", params, function(result) {
        // Clear build timestamp
        var buildTimestampElement = document.getElementById('buildTimestamp');
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
    var buildButton = document.getElementById('buildButton');
    var cleanButton = document.getElementById('cleanButton');
    var builddlButton = document.getElementById('builddlButton');
    var buildRunButton = document.getElementById('buildRunButton');
    cleanButton.disabled = buildButton.disabled = builddlButton.disabled = buildRunButton.disabled = true;
    removeClass(buildButton.children[1], "hidden");

    saveFile(function() {
        // build output
        var params = "id="+proj.pid + "&prgmName="+proj.prgmName + "&action=build";
        ajax("ActionHandler.php", params, function(result)
        {
            build_output_raw = JSON.parse(result);
            build_output = parseBuildLog(build_output_raw);

            var buildStatusClass = "buildOK";
            if (result.indexOf("ERROR: Object file(s) deleted because of option unresolved=fatal") !== -1)
            {
                alert("Fatal build error (undefined/unresolved calls).\nCheck the build log");
                buildStatusClass = "buildError"
            } else if (result.indexOf("\\tERROR") !== -1 || result.indexOf("Internal Error") !== -1) {
                alert("Fatal build error.\nCheck the build log");
                buildStatusClass = "buildError"
            } else {
                if (typeof callback === "function") {
                    callback();
                }
            }

            // Update build timestamp
            var buildTimestamp = build_output_raw.shift(); // Remove the timestamp
            var buildTimestampElement = document.getElementById('buildTimestamp');
            buildTimestampElement.parentNode.className = "";
            buildTimestampElement.className = buildStatusClass;
            buildTimestampElement.innerText = (build_output_raw === null) ? '(Error)' : (new Date(+(buildTimestamp+"000")).toLocaleTimeString());

            // Update console
            var consoletextarea = document.getElementById('consoletextarea');
            consoletextarea.value = build_output_raw.join("\n");
            consoletextarea.scrollTop = consoletextarea.scrollHeight;

            savedSinceLastChange = true;

            if (asmBeingShown) {
                var oldCursor = editor.getCursor();
                dispSrc();
                dispSrc(function() {
                    editor.setCursor(oldCursor);
                    var cursor_line_div = document.querySelector("div.CodeMirror-activeline");
                    if (cursor_line_div) {
                        cursor_line_div.scrollIntoView();
                    }
                });
            }

            // Call cppcheck
            ajax("ActionHandler.php", "id="+proj.pid + "&action=getCheckLog", function(result) {
                build_check = parseCheckLog(JSON.parse(result));
                updateHints(false);
                addClass(buildButton.children[1], "hidden");
                cleanButton.disabled = buildButton.disabled = builddlButton.disabled = buildRunButton.disabled = false;
            });
        });
    });
}

function parseBuildLog(log)
{
    var arr = [];
    if (log !== null && log.constructor === Array)
    {
        for (var i = 0; i < log.length; i++)
        {
            var regex = /(\w+\.(?:c|h|asm))\s+\((\d+),(\d+)\)\s+:\s+(.*?)\s+\((\d+)\)\s(.*?)$/gmi;
            var matches = regex.exec(log[i]);
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
    var arr = [];
    if (log !== null && log.constructor === Array)
    {
        for (var i = 0; i < log.length; i++)
        {
            var regex = /^\[(\w+\.[ch]):(\d+)\]: \((.*?)\) (.*)$/gmi;
            var matches = regex.exec(log[i]);
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

window.addEventListener('resize', function(event) {
    $(".CodeMirror-merge, .CodeMirror-merge .CodeMirror").css("height", (.75*($(document).height()))+'px');
});

window.addEventListener('keydown', function(event) {
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
var hidden, visibilityChange;
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