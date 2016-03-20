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

var build_output_raw = [];
var build_output = [];
var build_check  = [];
var lastSavedSource = '';

function applyPrgmNameChange(name)
{
    proj.prgmName = name;
    document.getElementById("prgmNameSpan").innerHTML = name;
    document.getElementById("prgmNameInput").value = name;
    saveProjConfig();
}

function changePrgmName()
{
    var name = prompt("Enter the new program name (8 letters max, A-Z 0-9, starts by a letter)", "CPRGMCE");
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

function saveFile(callback)
{
    var saveButton = document.getElementById('saveButton');
    removeClass(saveButton.children[1], "hidden");
    saveButton.disabled = true;

    var currSource = editor.getValue();
    if (currSource.length > 0 && currSource != lastSavedSource)
    {
        ajax("ActionHandler.php", "id=" + proj.pid + "&file="+proj.currFile + "&action=save&source="+encodeURIComponent(currSource), function() {
            savedSinceLastChange = true;
            addClass(saveButton.children[1], "hidden");
            lastSavedSource = currSource;
            if (typeof callback === "function") callback();
        });
    } else {
        savedSinceLastChange = true;
        addClass(saveButton.children[1], "hidden");
        if (typeof callback === "function") callback();
    }
    saveProjConfig();
}

function goToFile(newfile)
{
    $.get('?id=' + proj.pid + '&file=' + newfile, function(data, textStatus, jqXHR) {
        $('#editorContainer').empty().append($(data).find('#editorContainer').children());
        updateCSRFTokenFromHeaders(jqXHR.getAllResponseHeaders());
        localStorage.setItem("invalidateFirebaseContent", "true");
        $(".firepad-userlist").remove();
        proj.currFile = newfile;
        init_post_js_1();
        do_cm_custom();
        init_post_js_2();
    });
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
                var newURL = updateQueryStringParameter(window.location.href, "file", "main.c");
                window.history.pushState && window.history.pushState(null, null, newURL) || window.location.replace(newURL);
            });
        }
    }
}

function addFile(name)
{
    var err = false;
    if (!name || !isValidFileName(name))
    {
        var name = prompt("Enter the new file name (letters, numbers, underscores, and with .c, .h, or .asm extension)");
        if (name === null || !isValidFileName(name))
        {
            err = true;
            if (name) alert("Invalid name.");
        }
    }
    if (!err)
    {
        ajax("ActionHandler.php", "id=" + proj.pid + "&action=addFile&fileName="+name, function() {
            proj.files = proj.files.concat([ name ]);
            saveProjConfig();
            var newURL = updateQueryStringParameter(window.location.href, "file", name);
            saveFile(function() { window.location.replace(newURL); });
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

function buildAndGetLog(callback)
{
    var buildButton = document.getElementById('buildButton');
    var builddlButton = document.getElementById('builddlButton');
    buildButton.disabled = builddlButton.disabled = true;
    removeClass(callback && builddlButton.children[1] || buildButton.children[1], "hidden");

    saveFile(function() {
        // build output
        var params = "id="+proj.pid + "&prgmName="+proj.prgmName + "&action=build";
        ajax("ActionHandler.php", params, function(result) {
            build_output_raw = JSON.parse(result);
            build_output = parseBuildLog(build_output_raw);

            var buildStatusClass = "buildOK";
            if (result.indexOf("ERROR: Object file(s) deleted because of option unresolved=fatal") !== -1) {
                alert("Fatal build error (undefined/unresolved calls).\nCheck the build log");
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

            // Call cppcheck
            ajax("ActionHandler.php", "id="+proj.pid + "&action=getCheckLog", function(result) {
                build_check = parseCheckLog(JSON.parse(result));
                updateHints(false);
                addClass(callback && builddlButton.children[1] || buildButton.children[1], "hidden");
                document.getElementById('buildButton').disabled = document.getElementById('builddlButton').disabled = false;
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
