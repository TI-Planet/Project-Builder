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

var build_check  = [];
var code_analysis = [];
var ctags = [];
var sdk_ctags = [];
var lastSavedSource = '';

function applyPrgmNameChange(name)
{
    proj.prgmName = name;
    document.getElementById("prgmNameSpanInList").innerHTML = name;
    document.getElementById("prgmNameSpan").innerHTML = name;
    document.getElementById("prgmNameInput").value = name;
    saveProjConfig();
}

function applyProjectNameChange(name)
{
    proj.name = name;
    document.getElementById("projectNameSpan").innerHTML = name;
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

function changeProjectName()
{
    let name = prompt("Enter the new description (25 characters max, alphanumerical and common symbols)", "");
    if (name != null)
    {
        if (!name.match(/^[\w ._+\-*/<>,:()]{0,25}$/))
        {
            showNotification("danger", "Invalid name", "25 characters max, alphanumerical and common symbols");
        } else {
            removeClass(document.querySelector("#projectNameContainer span.loadingicon"), "hidden");
            ajaxAction("setName", `name=${name}`, () => {
                addClass(document.querySelector("#projectNameContainer span.loadingicon"), "hidden");
                applyProjectNameChange(name);
            });
        }
    }
}

function renameFile(oldName)
{
    $('.tooltip').hide();
    let err = false;
    const newName = prompt("Enter the new file name (Chars: a-z,A-Z,0-9,_ Extension: py)", oldName);
    if (newName === null || !isValidFileName(newName))
    {
        err = true;
        if (newName) {
            showNotification("danger", "Invalid name", "Chars: a-z,A-Z,0-9,_ Extension: py");
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
            getAnalysisLogAndUpdateHintsMaybe(true);
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
    return name === 'icon.png' || /^[a-zA-Z0-9_]+\.py$/i.test(name);
}

function isValidGfxImageFileName(name)
{
    return /^(gfx\/)?[a-zA-Z0-9_]+\.(png|bmp)$/i.test(name);
}

function createFileWithContent(name, content, cb, isLast, numFiles)
{
    const escapedName = $('<div/>').text(name).html();
    if (isValidFileName(name) || isValidGfxImageFileName(name))
    {
        if (proj.files.indexOf(name) === -1)
        {
            if (name === "icon.png") {
                const iconCheck = new Image();
                iconCheck.src = content;
                iconCheck.onload = () => {
                    if (iconCheck.width !== 16 && iconCheck.height !== 16) {
                        showNotification("danger", 'Invalid icon dimenstions', 'Make sure the icon PNG file is 16x16 px and try again');
                        cb(name);
                        return;
                    }
                    content = content.replace('data:image/png;base64,', '');
                    ajaxAction("addIconFile", `icon=${encodeURIComponent(content)}`, () =>
                    {
                        document.getElementById('prgmIconImg').src = `/pb/projects/${proj.pid}/icon.png`;
                        showNotification("success", 'Icon added', 'The project icon has been set successfully');
                        cb(name, isLast && numFiles > 1);
                    }, () => { showNotification("danger", 'Oops?', 'An error happened - make sure the icon PNG file is 16x16 px, and retry?'); cb(name); });
                };
            } else if (isValidGfxImageFileName(name)) {
                content = content.replace(/^data:image\/(png|bmp);base64,/, '');
                ajaxAction("addGfxImage", `fileName=${name}&content=${encodeURIComponent(content)}`, () =>
                {
                    proj.files = proj.files.concat([name]);
                    saveProjConfig();
                    showNotification("success", 'Image added', 'The file is available in the gfx folder');
                    cb(name, isLast && numFiles > 1);
                }, () => { showNotification("danger", 'Oops?', 'An error happened, retry?'); cb(name) });
            } else {
                ajaxAction("addFile", `fileName=${name}`, () =>
                {
                    proj.files = proj.files.concat([name]);
                    saveProjConfig();
                    ajaxAction("save", `file=${name}&source=${encodeURIComponent(content)}`, null, null, () => { cb(name, true) });
                }, () => { showNotification("danger", 'Oops?', 'An error happened, retry?'); cb(name) });
            }
        } else {
            showNotification("warning", "File not imported", `'${escapedName}' already exists in the project`, null, 10000);
            if (typeof(cb) === "function") { cb(name); }
        }
    } else {
        showNotification("warning", "File not imported", `'${escapedName}' is not a valid name (Could be icon.png, or: Chars: a-z,A-Z,0-9,_ Extension: py)`, null, 10000);
        if (typeof(cb) === "function") { cb(name); }
    }
}

function deleteCurrentFile()
{
    if (window.confirm("Do you really want to delete this file?"))
    {
        if (proj.currFile && (isValidFileName(proj.currFile) || isValidGfxImageFileName(proj.currFile)))
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
        name = prompt("Enter the new file name (Chars: a-z,A-Z,0-9,_ Extension: py)");
        if (name === null || !isValidFileName(name))
        {
            err = true;
            if (name) {
                showNotification("danger", "Invalid name", "Chars: a-z,A-Z,0-9,_ Extension: py");
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

function getAnalysisLogAndUpdateHintsMaybe(doUpdateHints)
{
    // Call pylint
    ajaxAction("getAnalysis", `file=${proj.currFile}`, (pylintOutput) => {
        code_analysis = parseAnalysisLog(pylintOutput);
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

function downloadCurrentFile(name)
{
    name = (typeof(name) === 'undefined') ? prompt('Name of the file') : proj.currFile;
    if (name === null) { return false; }
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

function makePythonAppVar()
{
    if (!TIVarsLib) {
        alert('tivars_lib not ready?!');
        return;
    }
    const pyAppVar = TIVarsLib.TIVarFile.createNew(TIVarsLib.TIVarType.createFromName("PythonAppVar"),
        proj.prgmName,
        TIVarsLib.TIModel.createFromName('83PCEEP'));

    pyAppVar.setContentFromString(editor.getValue());
    const filePath = pyAppVar.saveVarToFile("", proj.prgmName);
    const file = TIVarsLib.FS.readFile(filePath, {encoding: 'binary'});
    if (!file) {
        alert('Unable to convert the script to a Python AppVar - Try a smaller one?');
        return;
    }
    if (file.byteLength > 65525) {
        alert('File too big !?');
        return;
    }
    return file;
}

function downloadPythonAppVar()
{
    const file = makePythonAppVar();
    const blob = new Blob([file], {type: 'application/octet-stream'});
    window['saveAs'](blob, `${proj.prgmName}.8xv`);
}

function transferToEmu()
{
    // TODO: use a flag
    if ($("#rightSidebar").css("right")[0] === "-")
    {
        toggleRightSidebar();
    }
    if (emul_is_inited)
    {
        pauseEmul(false);
        const file = makePythonAppVar();
        fileLoad(new Blob([file], {type: "application/octet-stream"}), `${proj.prgmName}.8xv`, false);
    } else {
        showNotification("danger", "The emulator isn't ready yet", "Did you load a ROM?", null, 10000);
    }
}

function makeGfx(callback)
{
    // todo: make image appvars from images
    if (typeof callback === "function") {
        callback();
    }
}

function parseAnalysisLog(log)
{
    const arr = [];
    if (log !== null && log.constructor === Array)
    {
        for (let i = 0; i < log.length; i++)
        {
            const el = log[i];
            arr.push({file: el.path, line: el.line, col: el.column, type: el.type, category: el.symbol, text: el.message, from: `(pylint ${el['message-id']}) ${el.symbol} ${el.type}`, fixit: null});
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

function sendSettings()
{
    const formData = $("#settingsForm").serialize();
    ajaxAction("setSettings", formData, () =>
    {
        $("#settingsModal").modal('hide');
        showNotification("success", "OK", "Settings saved successfully. You can reload the page if needed", null, 5000);
    }, (err) =>
    {
        $("#settingsModal").modal('hide');
        showNotification("danger", "Error", err, null, 50000);
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