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
var code_analysis = [];
var ctags = [];
var sdk_ctags = [];
var enable_sdk_ctags = true; // true when not asm
var ti84pceInc_ctags = [];
var enable_ti84pceInc_ctags = false; // true when asm
var lastSavedSource = '';
var isLastBuildLLVM = false;

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
            showNotification("danger", "Invalid name", "8 letters max, A-Z 0-9, starts by a letter");
        } else {
            removeClass(document.querySelector("#prgmNameContainer span.loadingicon"), "hidden");
            ajaxAction("setInternalName", `internalName=${name}`, () => {
                addClass(document.querySelector("#prgmNameContainer span.loadingicon"), "hidden");
                applyPrgmNameChange(name);
            });
        }
    }
}

function renameFile(oldName)
{
    $('.tooltip').hide();
    let err = false;
    const newName = prompt("Enter the new file name (Chars: a-z,A-Z,0-9,_ Extension: c,cpp,h,hpp,asm,inc)", oldName);
    if (newName === null || !isValidFileName(newName))
    {
        err = true;
        if (newName) {
            showNotification("danger", "Invalid name", "Chars: a-z,A-Z,0-9,_ Extension: c,cpp,h,hpp,asm,inc");
        }
    }
    if (newName === oldName) {
        return;
    }
    if (!err)
    {
        saveFile(() => {
            ajaxAction("renameFile", `oldName=${oldName}&newName=${newName}`, () => {
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

        ajaxAction("save", `file=${proj.currFile}&source=${encodeURIComponent(currSource)}`, () => {
            savedSinceLastChange = true; lastChangeTS = (new Date).getTime();
            lastSavedSource = currSource;
            if (editor.getMode().name === "clike") { getAnalysisLogAndUpdateHintsMaybe(true); }
            getCtags(proj.currFile, () => { filterOutline($("#codeOutlineFilter").val()); });
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
    proj.cursors[proj.currFile] = JSON.stringify(editor.getCursor());
    saveProjConfig();
    if (proj.is_multi)
    {
        if (!globalSyncOK || globalSaveFileRetryCount >= 3)
        {
            showNotification("warning", "Syncing failed", "Saving now may overwrite changes and data may be lost. Please try again later (and make a local backup)", null, 999999);
            globalSaveFileRetryCount = 0;
            return;
        }
        if ((new Date).getTime() - lastChangeTS > 30000)
        {
            typeof(tryFirepadSync) !== "undefined" && tryFirepadSync();
            lastChangeTS = (new Date).getTime();
            console.log("Current session is old, trying to sync with Firepad... Retry count == " + globalSaveFileRetryCount);
            globalSaveFileRetryCount++;
            setTimeout(function(){ saveFile(callback); }, 200);
            return;
        }
    }
    globalSaveFileRetryCount = 0;
    stripTrailingSpaces();
    _saveFile_impl(callback);
}

function isValidFileName(name)
{
    return /^[a-zA-Z0-9_]+\.(c|cpp|h|hpp|asm|inc)$/i.test(name);
}

function createFileWithContent(name, content, cb)
{
    const escapedName = $('<div/>').text(name).html();
    if (isValidFileName(name))
    {
        if (proj.files.indexOf(name) === -1)
        {
            ajaxAction("addFile", `fileName=${name}`, () =>
            {
                proj.files = proj.files.concat([name]);
                saveProjConfig();
                ajaxAction("save", `file=${name}&source=${encodeURIComponent(content)}`, null, null, () => { cb(name) });
            }, () => { cb(name) });
        } else {
            showNotification("warning", "A file was not created", `'${escapedName}' already exists in the project`, null, 10000);
            if (typeof(cb) === "function") { cb(name); }
        }
    } else {
        showNotification("warning", "A file was not created", `'${escapedName}' is not a valid name (Chars: a-z,A-Z,0-9,_ Extension: c,cpp,h,hpp,asm,inc)`, null, 10000);
        if (typeof(cb) === "function") { cb(name); }
    }
}

function deleteCurrentFile()
{
    if (window.confirm("Do you really want to delete this file?"))
    {
        if (proj.currFile && isValidFileName(proj.currFile))
        {
            ajaxAction("deleteCurrentFile", `file=${proj.currFile}`, () => {
                const idx = proj.files.indexOf(proj.currFile);
                if (idx > -1)
                {
                    proj.files.splice(idx, 1);
                }
                proj.currFile = proj.files[0];
                saveProjConfig();
                goToFile(proj.currFile);
            });
        }
    } else {
        showNotification("info", "File not deleted", "", null, 1);
    }
}

function addFile(name)
{
    let err = false;
    if (!name || !isValidFileName(name))
    {
        name = prompt("Enter the new file name (Chars: a-z,A-Z,0-9,_ Extension: c,cpp,h,hpp,asm,inc)");
        if (name === null || !isValidFileName(name))
        {
            err = true;
            if (name) {
                showNotification("danger", "Invalid name", "Chars: a-z,A-Z,0-9,_ Extension: c,cpp,h,hpp,asm,inc");
            }
        }
    }
    if (!err)
    {
        saveFile(() => {
            ajaxAction("addFile", `fileName=${name}`, () => {
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
    buildAndGetLog(isLastBuildLLVM, () => {
        document.getElementById('builddlButton').removeAttribute("onclick");
        document.getElementById('postForm').submit();
        document.getElementById('builddlButton').setAttribute("onclick", 'buildAndDownload(); return false;');
    });
}

function buildAndRunInEmu()
{
    // TODO: use a flag
    if ($("#rightSidebar").css("right")[0] === "-")
    {
        toggleRightSidebar();
    }
    if (emul_is_inited)
    {
        buildAndGetLog(isLastBuildLLVM, () => {
            ajaxGetArrayBuffer("ActionHandler.php", $("#postForm").serialize(), (file) => {
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
        showNotification("danger", "The emulator isn't ready yet", "Did you load a ROM?", null, 10000);
    }
}

function getBuildLogAndUpdateHintsMaybe(doUpdateHints)
{
    ajaxAction("getBuildLog", "", (text) => {
        build_output_raw = text;
        build_output = parseBuildLog(build_output_raw);
        doUpdateHints && updateHints(true);
    });
}

function getCheckLogAndUpdateHints(doUpdateHints)
{
    ajaxAction("getCheckLog", "", (lines) => {
        build_check = parseCheckLog(lines);
        doUpdateHints && updateHints(true);
    });
}

function getAnalysisLogAndUpdateHintsMaybe(doUpdateHints)
{
    // Call llvm syntax only
    ajaxAction("analysis", `file=${proj.currFile}`, (lines) => {
        code_analysis = parseAnalysisLog(lines);
        doUpdateHints && updateHints(true);
    });
}

function getCtags(scope, cb)
{
    if (scope === undefined) { scope = proj.currFile; }
    ajaxAction("getCtags", `scope=${scope}`, (allCtags) => {
        const list = [];
        Object.keys(allCtags).map( (tagFile) =>
        {
            allCtags[tagFile].forEach( (tag) =>
            {
                tag.file = tagFile;
                list.push(tag);
            });
        });
        ctags = list;
        dispCodeOutline(list);
        if (typeof(cb) === "function") {
            cb();
        }
    });
}

function getSDKCtags()
{
    ajaxAction("getSDKCtags", "", (allCtags) => {
        const list = [];
        Object.keys(allCtags).map( (tagFile) =>
        {
            allCtags[tagFile].forEach( (tag) =>
            {
                tag.file = tagFile;
                list.push(tag);
            });
        });
        sdk_ctags = list;
    });
}

function getInc84PCECtags()
{
    // todo later
}

function downloadCurrentFile(name)
{
    name = (typeof(name) === 'undefined') ? (prompt('Name of the file') || proj.currFile) : proj.currFile;
    const dlLink = document.createElement('a');
    dlLink.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(editor.getValue()));
    dlLink.setAttribute('download', name);

    if (document.createEvent) {
        const event = document.createEvent('MouseEvents');
        event.initEvent('click', true, true);
        dlLink.dispatchEvent(event);
    } else {
        dlLink.click();
    }
}

function cleanProj(callback)
{
    const cleanButton = document.getElementById('cleanButton');
    cleanButton.disabled = true;

    ajaxAction("clean", "", () => {
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

function buildAndGetLog(llvm, callback)
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
        isLastBuildLLVM = (typeof llvm === 'boolean' && llvm);
        const buildName = isLastBuildLLVM ? 'llvmbuild' : 'build';
        ajaxAction(buildName, `prgmName=${proj.prgmName}`, (result) => {
            build_output_raw = result;

            build_output = parseBuildLog(build_output_raw);

            let buildStatusClass = "buildOK";
            if (result.includes("ERROR: Object file(s) deleted because of option unresolved=fatal."))
            {
                showNotification("warning", "Fatal build error (undefined/unresolved calls)", "Check the build log", null, 10000);
                buildStatusClass = "buildError"
            } else if (result.includes("\\tERROR") || result.includes("Internal Error")) {
                showNotification("warning", "Fatal build error", "Check the build log", null, 10000);
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
            buildTimestampElement.innerText = (build_output_raw === null) ? '(Error)' : (new Date(buildTimestamp * 1000).toLocaleTimeString());

            // Update console
            const consoletextarea = document.getElementById('consoletextarea');
            consoletextarea.value = build_output_raw.join("\n");
            consoletextarea.scrollTop = consoletextarea.scrollHeight;

            savedSinceLastChange = true; lastChangeTS = (new Date).getTime();

            if (asmBeingShown) {
                const oldCursor = editor.getCursor();
                dispSrc(editor);
                dispSrc(editor, () => {
                    editor.setCursor(oldCursor);
                    const cursor_line_div = document.querySelector("div.CodeMirror-activeline");
                    if (cursor_line_div) {
                        cursor_line_div.scrollIntoView();
                    }
                });
            }

            // Call cppcheck
            ajaxAction("getCheckLog", "", (text) => {
                build_check = parseCheckLog(text);
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
            const regex = /(\w+\.(?:c|h|asm|inc))\s+\((\d+),(\d+)\)\s+:\s+(.*?)\s+\((\d+)\)\s(.*?)$/gmi;
            const matches = regex.exec(log[i]);
            if (matches !== null)
            {
                // not doing anything with matches[5] ? (code)
                arr.push({file: matches[1], line: parseInt(matches[2]), col: parseInt(matches[3]), type: matches[4].toLowerCase(), category: "", text: matches[6], from: 'ZDS'})
            }
        }
    } else {
        console.log("Error parseLog: log wasn't an array... ?", log);
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
            const regex = /^\[(\w+\.(?:c|cpp|h|hpp)):(\d+)\]: \((.*?)\) (.*)$/gmi;
            const matches = regex.exec(log[i]);
            if (matches !== null)
            {
                arr.push({file: matches[1], line: parseInt(matches[2]), col: 0, type: matches[3], category: "", text: matches[4], from: 'cppcheck'})
            }
        }
    } else {
        console.log("Error parseCheck: log wasn't an array... ?");
    }
    return arr;
}

function parseAnalysisLog(log)
{
    const arr = [];
    if (log !== null && log.constructor === Array)
    {
        for (let i = 0; i < log.length; i++)
        {
            const regex = /^\/tmp\/\w+\/(\w+.(?:[chp]+)):(\d+):(\d+): (\w+)(.*?)(?: \[(-.*?)\])?$/gmi;
            const matches = regex.exec(log[i]);
            if (matches !== null)
            {
                const category = (matches.length >= 7 && matches[6]) ? matches[6] : "";
                const text = matches[4] + " " + matches[5];
                arr.push({file: matches[1], line: parseInt(matches[2]), col: parseInt(matches[3]), type: matches[4], category: category, text: text, from: 'clang'});
            }
        }
    } else {
        console.log("Error parseAnalysisLog: log wasn't an array... ?");
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

function showOnDiff(asm1, asm2)
{
    const target = document.getElementById('modalDiffSourceBody');
    target.innerHTML = "";
    const mergeView = CodeMirror.MergeView(target, {
        value: asm1,
        readOnly: true,
        origRight: asm2,
        lineNumbers: true,
        mode: "text/x-ez80",
        theme: "xq-light",
        highlightDifferences: true,
        connect: null,
        ignoreWhitespace: true,
        collapseIdentical: false,
        indentUnit: 8,
        tabSize: 8
    });

    mergeView.editor().refresh();
    $('#diffModal').modal();
    $('#diffModal').find('div.modal-dialog').css("width", "80%");
    setTimeout(function ()
    {
        $(".CodeMirror-merge, .CodeMirror-merge .CodeMirror").css("height", (.5 * ($(document).height())) + 'px');
        mergeView.editor().scrollTo(null, 1);
        mergeView.editor().scrollTo(null, 0);
        $(".CodeMirror-merge, .CodeMirror-merge .CodeMirror").css("height", (.75 * ($(document).height())) + 'px');
        mergeView.editor().refresh();
    }, 500);
}

function llvmCompileAndDiff()
{
    saveFile(() =>
    {
        $("#myDiffModalLabel").text('Diff ZDS - LLVM');
        showOnDiff("(loading ZDS ASM...)", "(loading LLVM ASM...)");
        ajaxAction("llvm", `file=${proj.currFile}`, (result) =>
        {
            if (!result) {
                result = [ "Hmm, empty output, or something went wrong." ];
            }

            let llvmASM = result.join("\n").replace(/^;.*/gm, "").replace(/^\s*$/gm, "").toLowerCase();
            llvmASM = llvmASM.replace(/ \+ /gm, "+").replace(/\s*.global.*/gm, "");
            showOnDiff("(loading ZDS...)", llvmASM);

            ajaxAction("getCurrentSrc", `file=${proj.currFile}`, (zdsASM) =>
            {
                const showFinalDiff = (asm) =>
                {
                    if (!asm || asm === "null") {
                        showOnDiff("(not built yet?)", llvmASM);
                    } else {
                        asm = asm.replace("\\r", "").replace(/^;.*/gm, "").replace(/^\s*$/gm, "").replace(/ \+ /gm, "+").toLowerCase();
                        showOnDiff(asm, llvmASM);
                    }
                };
                if (zdsASM === null)
                {
                    ajaxAction("build", `prgmName=${proj.prgmName}`, () =>
                    {
                        ajaxAction("getCurrentSrc", `file=${proj.currFile}`, (finalZDSASM) =>
                        {
                            showFinalDiff(finalZDSASM);
                        });
                    });
                } else {
                    showFinalDiff(zdsASM);
                }
            });
        });
    });
}

function llvmCompile()
{
    saveFile(() =>
    {
        $("#myDiffModalLabel").text('Output of LLVM compilation (-O3)');
        ajaxAction("llvm", `file=${proj.currFile}`, (result) =>
        {
            if (!result) {
                result = [ "Hmm, empty output, or something went wrong." ];
            }

            $('#modalDiffSourceBody').html("<textarea id='llvmModalTextarea'></textarea>");
            const $ta = $("#llvmModalTextarea");
            $ta.val(result.join("\n"));

            const cm = CodeMirror.fromTextArea($ta[0], {
                readOnly: true,
                lineNumbers: true,
                mode: "text/x-ez80",
                theme: "xq-light",
                indentUnit: 8,
                tabSize: 8
            });

            cm.refresh();
            $('#diffModal').modal();
            $('#diffModal').find('div.modal-dialog').css("width", "80%");
            setTimeout(function ()
            {
                $("#diffModal .CodeMirror").css("height", (.5 * ($(document).height())) + 'px');
                cm.scrollTo(null, 1);
                cm.scrollTo(null, 0);
                $("#diffModal .CodeMirror").css("height", (.75 * ($(document).height())) + 'px');
                cm.refresh();
            }, 500);
        });
    });
}

/*  :/  */
window.addEventListener('resize', () => {
    $(".CodeMirror-merge, .CodeMirror-merge .CodeMirror").css("height", (.75*($(document).height()))+'px');
    refreshOutlineSize();
});

window.addEventListener('keydown', (event) => {
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