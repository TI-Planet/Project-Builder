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
var enable_sdk_ctags = true;
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
    const newName = prompt("Enter the new file name (Chars: a-z,A-Z,0-9,_ Extensions: py, menu)", oldName);
    if (newName === null || !isValidFileName(newName))
    {
        err = true;
        if (newName) {
            showNotification("danger", "Invalid name", "Chars: a-z,A-Z,0-9,_ Extensions: py, menu");
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
    if ((currSource.length > 0 || isPythonMenuFile()) && currSource != lastSavedSource)
    {
        removeClass(saveButton.children[1], "hidden");
        saveButton.disabled = true;

        ajaxAction("save", `file=${proj.currFile}&source=${encodeURIComponent(currSource)}&baseSourceHash=${encodeURIComponent(fakeContainer?.dataset?.sourceHash || '')}`, (saveResp) => {
            savedSinceLastChange = true; lastChangeTS = (new Date).getTime();
            lastSavedSource = currSource;
            updateLoadedFileSnapshot(currSource, saveResp && saveResp.source_hash, saveResp && saveResp.mtime);
            typeof(publishRealtimeServerSnapshot) === "function" && publishRealtimeServerSnapshot(saveResp && saveResp.source_hash, saveResp && saveResp.mtime);
            closeCollaborativeSaveNotifications();
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
    if (!hasUnsavedEditorSource()) {
        _saveFile_impl(callback);
        return;
    }
    if (!canProceedWithCollaborativeSave(() => { saveFile(callback); })) {
        return;
    }
    // Menu insertion strings and raw catalogs may contain significant whitespace.
    if (!isPythonMenuFile()) stripTrailingSpaces();
    _saveFile_impl(callback);
}

function isValidFileName(name)
{
    return /^[a-zA-Z0-9_]+\.(py|menu)$/i.test(name);
}

function createFileWithContent(name, content, cb, isLast, numFiles)
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
                ajaxAction("save", `file=${name}&source=${encodeURIComponent(content)}`, null, null, () => { cb(name, true) });
            }, () => { showNotification("danger", 'Oops?', 'An error happened, retry?'); cb(name) });
        } else {
            showNotification("warning", "File not imported", `'${escapedName}' already exists in the project`, null, 10000);
            if (typeof(cb) === "function") { cb(name); }
        }
    } else {
        showNotification("warning", "File not imported", `'${escapedName}' is not a valid name (Chars: a-z,A-Z,0-9,_ Extensions: py, menu)`, null, 10000);
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
        name = prompt("Enter the new file name (Chars: a-z,A-Z,0-9,_ Extensions: py, menu)");
        if (name === null || !isValidFileName(name))
        {
            err = true;
            if (name) {
                showNotification("danger", "Invalid name", "Chars: a-z,A-Z,0-9,_ Extensions: py, menu");
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
    if (isPythonMenuFile()) { code_analysis = []; return; }
    const filename = proj.currFile;
    // Call pylint
    ajaxAction("getAnalysis", `file=${filename}`, (pylintOutput) => {
        if (proj.currFile !== filename) return;
        code_analysis = parseAnalysisLog(pylintOutput);
        doUpdateHints && updateHints(true);
    });
}

function getCtags(scope, cb)
{
    if (isPythonMenuFile()) { ctags = []; if (typeof cb === 'function') cb(); return; }
    const filename = proj.currFile;
    if (scope === undefined) { scope = proj.currFile; }
    ajaxAction("getCtags", `scope=${scope}`, (allCtags) => {
        if (proj.currFile !== filename) return;
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
    const output = makePythonTransferFile('ce');
    return output ? output.file : undefined;
}

function getEvoPythonProgramName()
{
    let name = String(proj.prgmName || '').toUpperCase().replace(/[^A-Z0-9_]/g, '');
    if (!/^[A-Z]/.test(name)) {
        name = `P${name}`;
    }
    name = name.slice(0, 7);
    return /^[A-Z][A-Z0-9_]{0,6}$/.test(name) ? name : 'PYTHON';
}

function makePythonTransferFile(target)
{
    if (!TIVarsLib) {
        alert('tivars_lib not ready?!');
        return;
    }
    const isEvo = target === 'evo';
    const programName = isEvo ? getEvoPythonProgramName() : proj.prgmName;
    const pyAppVar = TIVarsLib.TIVarFile.createNew("PythonAppVar", programName, isEvo ? '84Evo' : '83PCEEP');
    let filePath = '';
    let file;
    try {
        pyAppVar.setContentFromString(editor.getValue());
        filePath = pyAppVar.saveVarToFile("", programName);
        file = TIVarsLib.FS.readFile(filePath, {encoding: 'binary'});
    } finally {
        if (filePath) {
            try {
                TIVarsLib.FS.unlink(filePath);
            } catch (e) {
                console.warn('[Project Builder] Unable to remove Python conversion file', e);
            }
        }
        if (typeof pyAppVar.delete === 'function') {
            pyAppVar.delete();
        }
    }
    if (!file) {
        alert('Unable to convert the script to a Python program - Try a smaller one?');
        return;
    }
    if (!isEvo && file.byteLength > 65525) {
        alert('File too big !?');
        return;
    }
    return {
        file,
        filename: `${programName}.${isEvo ? '8xpy2' : '8xv'}`
    };
}

function downloadPythonAppVar()
{
    const file = makePythonAppVar();
    const blob = new Blob([file], {type: 'application/octet-stream'});
    window['saveAs'](blob, `${proj.prgmName}.8xv`);
}

let pythonBytecodeExport = null;

async function downloadPythonBytecode(target)
{
    if (pythonBytecodeExport) return;
    const controller = new AbortController();
    pythonBytecodeExport = controller;
    const buttons = $('.python-bytecode-download');
    buttons.attr('aria-disabled', 'true').parent().addClass('disabled');
    const status = showNotification('info', 'Compiling bytecode module…',
        'Compiling in your browser. <a href="#" class="cancelPythonBytecode">Cancel</a>', null, 999999);
    status?.$ele?.[0]?.querySelector('.cancelPythonBytecode')?.addEventListener('click', event => {
        event.preventDefault();
        controller.abort();
    });
    try {
        const input = await getPythonBytecodeSources(controller.signal);
        const result = await window.pbPythonBytecode.compile({...input, target, signal: controller.signal});
        const lib = await window.pbTIVarsLibReady;
        if (!lib) throw new Error('TI file converter is still loading. Please retry.');
        if (controller.signal.aborted) return;
        const output = window.pbPythonBytecode.packageModule(lib, {...input, ...result, target});
        window.saveAs(new Blob([output.file], {type: 'application/octet-stream'}), output.filename);
    } catch (error) {
        if (error.name !== 'AbortError') {
            // Compiler diagnostics contain source text: notifications interpret HTML.
            showNotification('danger', 'Bytecode export failed', $('<div>').text(error.message || String(error)).html(), null, 15000);
        }
    } finally {
        status?.close();
        buttons.removeAttr('aria-disabled').parent().removeClass('disabled');
        pythonBytecodeExport = null;
    }
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

async function transferToCalc()
{
    const button = $("#buildUsbButton");
    if (button.hasClass("disabled")) {
        return;
    }
    button.addClass("disabled").attr("disabled", true).find("span.loadingicon").removeClass("hidden");
    try {
        if (!window.pbCalculatorTransfer) {
            throw new Error("Calculator transfer helper not loaded");
        }
        const {target, model, modelName} = await window.pbCalculatorTransfer.prepareTransfer();
        const pythonDirectLinkModels = new Set([19, 20, 36]);
        if (target !== window.pbCalculatorTransfer.targets.evo && !pythonDirectLinkModels.has(model)) {
            throw new Error(`The connected calculator (${modelName || `model ${model}`}) does not support Python AppVars.`);
        }
        const output = makePythonTransferFile(target);
        if (!output) {
            return;
        }
        const transfer = await window.pbCalculatorTransfer.sendFileBytes(output.file, output.filename);
        if (transfer.result === 0) {
            showNotification("success", "Transfer complete", `Sent ${output.filename} to ${modelName || 'the calculator'}`);
        } else {
            showNotification("danger", "Transfer failed", transfer.error || `Calculator returned error ${transfer.result}`);
        }
    } catch (err) {
        showNotification("danger", "Transfer failed", err.message || err);
    } finally {
        button.removeClass("disabled").attr("disabled", false).find("span.loadingicon").addClass("hidden");
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
