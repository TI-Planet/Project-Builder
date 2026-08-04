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
var tokens_json = null;
var tokens_json_promise = null;
var lastSavedSource = '';
var basicExportFormat = '8xp';
const basicSignatureHelpStorageKey = 'pb_basic_ez80_signature_help_enabled';
const basicTokenBrowserModeStorageKey = 'pb_basic_ez80_token_browser_mode';
let basicTokenBrowserTokens = null;

function isBasicSignatureHelpEnabled()
{
    try {
        return window.localStorage.getItem(basicSignatureHelpStorageKey) !== '0';
    } catch (e) {
        return true;
    }
}

function setBasicSignatureHelpEnabled(enabled)
{
    try {
        window.localStorage.setItem(basicSignatureHelpStorageKey, enabled ? '1' : '0');
    } catch (e) {}
    window.dispatchEvent(new CustomEvent('pb-basic-signature-help-setting-change', {
        detail: { enabled: enabled }
    }));
}

function getEditorPreferencesHTML()
{
    return `
        <h4 style="margin-top:0;">Preferences</h4>
        <div class="checkbox" style="margin-bottom:15px;">
            <label>
                <input type="checkbox" id="basicSignatureHelpEnabled">
                Show automatic signature help popups
            </label>
        </div>
    `;
}

function setupEditorPreferencesUI(root)
{
    const signatureHelpCheckbox = (root || document).querySelector('#basicSignatureHelpEnabled');
    if (!signatureHelpCheckbox) {
        return;
    }
    signatureHelpCheckbox.checked = isBasicSignatureHelpEnabled();
    signatureHelpCheckbox.addEventListener('change', () => {
        setBasicSignatureHelpEnabled(signatureHelpCheckbox.checked);
    });
}

function normalizeBasicExportFormat(format)
{
    return format === '8xp2' ? '8xp2' : '8xp';
}

function getBasicExportFormat()
{
    return normalizeBasicExportFormat(basicExportFormat);
}

function setBasicExportFormat(format)
{
    basicExportFormat = normalizeBasicExportFormat(format);
    try {
        window.localStorage.setItem('pb_basic_ez80_export_format', basicExportFormat);
    } catch (e) {}
    const label = document.getElementById('basicExportFormatLabel');
    if (label) {
        label.innerText = `.${basicExportFormat}`;
    }
    document.querySelectorAll('[data-basic-export-format]').forEach((item) => {
        const isSelected = item.getAttribute('data-basic-export-format') === basicExportFormat;
        item.classList.toggle('active', isSelected);
        const checkmark = item.querySelector('.glyphicon-ok');
        if (checkmark) {
            checkmark.style.visibility = isSelected ? 'visible' : 'hidden';
        }
    });
}

function initBasicExportFormat()
{
    let storedFormat = '8xp';
    try {
        storedFormat = window.localStorage.getItem('pb_basic_ez80_export_format') || storedFormat;
    } catch (e) {}
    setBasicExportFormat(storedFormat);
}

function getTIVarsLibErrorMessage(e)
{
    if (typeof TIVarsLib !== 'undefined' && TIVarsLib && typeof TIVarsLib.getExceptionMessage === 'function') {
        try {
            const message = TIVarsLib.getExceptionMessage(e);
            if (Array.isArray(message)) {
                return message.filter(Boolean).join(': ');
            }
            if (message) {
                return message;
            }
        } catch (ignored) {}
    }
    return e && e.message ? e.message : String(e);
}

function uniqueStrings(values)
{
    return [...new Set(values.filter((value) => typeof(value) === 'string' && value.length > 0))];
}

function getBasicTokenSourceNames(token)
{
    return uniqueStrings([token.name, token.accessibleName].concat(token.nameVariants || []));
}

function buildTokensJSONIndexes(json)
{
    const tokDataByName = Object.create(null);
    const tokBytesByAccessibleName = Object.create(null);
    const callTokens = [];
    const valueTokens = [];
    const writableStoreTargets = {
        any: [],
        scalar: [],
        string: [],
        list: [],
        matrix: [],
        picture: [],
        image: []
    };

    for (const [bytes, data] of Object.entries(json)) {
        data.bytes = bytes;
        tokDataByName[data.name] = data;
        if ('accessibleName' in data) {
            tokBytesByAccessibleName[data.accessibleName] = bytes;
        }

        getBasicTokenSourceNames(data).forEach((name) => {
            if (name.endsWith('(')) {
                callTokens.push({ match: name, token: data });
            } else if (['variable', 'constant'].includes(data.type)) {
                valueTokens.push({ match: name, token: data });
            }
        });

        const storeTargetGroup = getBasicStoreTargetGroup(data);
        if (storeTargetGroup) {
            const completionTag = {
                n: data.name,
                k: 'variable',
                file: data.categories?.[0] || '',
                bytes: data.bytes,
                group: storeTargetGroup
            };
            writableStoreTargets.any.push(completionTag);
            writableStoreTargets[storeTargetGroup].push(completionTag);
        }
    }

    callTokens.sort((a, b) => b.match.length - a.match.length);
    valueTokens.sort((a, b) => b.match.length - a.match.length);

    return {
        byName: tokDataByName,
        byAccessibleName: tokBytesByAccessibleName,
        byBytes: json,
        completionData: {
            callTokens: callTokens,
            valueTokens: valueTokens,
            writableStoreTargets: writableStoreTargets
        }
    };
}

function ensureTokensJSONLoaded()
{
    if (window.tokens_json) {
        tokens_json = window.tokens_json;
        return Promise.resolve(window.tokens_json);
    }
    if (tokens_json_promise) {
        return tokens_json_promise;
    }

    tokens_json_promise = fetch('/pb/modules/basic_eZ80/js/tokens.json')
        .then((res) => res.json())
        .then((json) => {
            window.tokens_json = buildTokensJSONIndexes(json);
            tokens_json = window.tokens_json;
            return window.tokens_json;
        })
        .catch((err) => {
            tokens_json_promise = null;
            throw err;
        });

    return tokens_json_promise;
}

function getBasicSDKCtagKind(token)
{
    switch (token.type) {
        case 'function':
        case 'userfunction':
            return 'function';
        case 'constant':
            return 'constant';
        case 'variable':
            return 'variable';
        case 'label':
            return 'label';
        case 'infix operator':
        case 'postfix operator':
        case 'and':
        case 'dim':
        case 'for':
        case 'goto':
        case 'if':
        case 'other':
        case 'store':
            return 'operator';
        case 'action':
        case 'command':
        case 'basic instruction only':
        case 'execlib':
            return 'keyword';
        default:
            return 'keyword';
    }
}

function tokenHasCategory(token, categoryFragment)
{
    return (token.categories || []).some((category) => category.includes(categoryFragment));
}

function getBasicValueGroupFromToken(token)
{
    if (tokenHasCategory(token, 'String')) {
        return 'string';
    }
    if (tokenHasCategory(token, 'Matrix')) {
        return 'matrix';
    }
    if (tokenHasCategory(token, 'List')) {
        return 'list';
    }
    if (tokenHasCategory(token, 'Pictures')) {
        return 'picture';
    }
    if (tokenHasCategory(token, 'Images')) {
        return 'image';
    }
    return 'scalar';
}

function getBasicStoreTargetGroup(token)
{
    if (token.type !== 'variable' || token.isAlias) {
        return null;
    }
    if ((token.categories || []).some((category) => category.startsWith('Statistics >'))) {
        return null;
    }
    return getBasicValueGroupFromToken(token);
}

function shouldExposeTokenAsSDKCtag(token)
{
    return !token.isAlias && !['delimiter', 'text/symbol only', 'variable'].includes(token.type);
}

function getBasicSDKCtagArgs(token)
{
    if (!Array.isArray(token.syntaxes) || token.syntaxes.length === 0) {
        return '';
    }

    for (const syntaxData of token.syntaxes) {
        const syntax = syntaxData?.syntax || '';
        if (!syntax.length) {
            continue;
        }
        for (const nameCandidate of [token.name, token.accessibleName]) {
            if (nameCandidate && syntax.startsWith(nameCandidate)) {
                return syntax.substring(nameCandidate.length);
            }
        }
    }

    return '';
}

function getBasicSDKCtagDescription(token)
{
    if (!Array.isArray(token.syntaxes)) {
        return '';
    }
    const descriptions = token.syntaxes.map((syntaxData) => syntaxData?.description?.trim()).filter(Boolean);
    return descriptions[0] || '';
}

function buildBasicSDKCtags(tokensJSON)
{
    return Object.values(tokensJSON.byBytes)
        .filter((token) => shouldExposeTokenAsSDKCtag(token))
        .map((token) => {
            const location = token.syntaxes?.find((syntaxData) => Array.isArray(syntaxData?.location) && syntaxData.location.length)?.location || [];
            const category = token.categories?.[0] || '';
            return {
                n: token.name,
                k: getBasicSDKCtagKind(token),
                a: getBasicSDKCtagArgs(token),
                d: getBasicSDKCtagDescription(token),
                file: category || location.join(' > '),
                bytes: token.bytes
            };
        });
}

function getBasicExpressionTypeGroup(expressionText)
{
    const expr = (expressionText || '').trim();
    if (!expr.length || !window.tokens_json?.completionData) {
        return 'scalar';
    }

    if (expr.startsWith('"') && expr.endsWith('"') && expr.length >= 2) {
        return 'string';
    }
    if (/^\[\s*\[/.test(expr)) {
        return 'matrix';
    }

    let inString = false;
    const stack = [];
    let braceDepth = 0;
    let lastClosedToken = null;

    for (let i = 0; i < expr.length; i++) {
        const char = expr[i];

        if (!inString && char === '#') {
            break;
        }
        if (char === '"') {
            inString = !inString;
            continue;
        }
        if (inString) {
            continue;
        }

        const remaining = expr.substring(i);
        const matchedCallToken = window.tokens_json.completionData.callTokens.find((entry) => remaining.startsWith(entry.match));
        if (matchedCallToken) {
            stack.push(matchedCallToken.token);
            i += matchedCallToken.match.length - 1;
            continue;
        }

        if (char === '{') {
            braceDepth++;
            continue;
        }

        if (char === '}') {
            if (braceDepth > 0) {
                braceDepth--;
            }
            continue;
        }

        if (char === ')' && stack.length) {
            lastClosedToken = stack.pop();
        }
    }

    if (inString) {
        return 'string';
    }

    if (braceDepth > 0 || expr.includes('{')) {
        return 'list';
    }

    if (stack.length) {
        return getBasicValueGroupFromToken(stack[stack.length - 1]);
    }

    const directValueToken = window.tokens_json.completionData.valueTokens.find((entry) => expr.endsWith(entry.match));
    if (directValueToken) {
        return getBasicValueGroupFromToken(directValueToken.token);
    }

    return lastClosedToken ? getBasicValueGroupFromToken(lastClosedToken) : 'scalar';
}

function getBasicStoreCompletionTagsForGroup(group)
{
    if (!window.tokens_json?.completionData) {
        return [];
    }

    const writableStoreTargets = window.tokens_json.completionData.writableStoreTargets;
    switch (group) {
        case 'string':
        case 'list':
        case 'matrix':
        case 'picture':
        case 'image':
            return writableStoreTargets[group] || [];
        default:
            return writableStoreTargets.scalar;
    }
}

function getEditorCompletionContext(editor, context)
{
    if (editor.getMode().name !== 'tibasic') {
        return null;
    }

    const linePrefix = context.curLine.substring(0, context.cur.ch);
    if (/\bGoto\s+[A-Z0-9θ]{0,2}$/u.test(linePrefix)) {
        return {
            force: true,
            skipAnyWord: true,
            ctags: (window.ctags || []).filter((tag) => tag.k === 'label'),
            sdkCtags: []
        };
    }

    const storeMatch = linePrefix.match(/^(.*?)(?:→|->)\s*([^\s:]*)$/u);
    if (storeMatch && window.tokens_json?.completionData) {
        return {
            force: true,
            skipAnyWord: true,
            ctags: [],
            sdkCtags: getBasicStoreCompletionTagsForGroup(getBasicExpressionTypeGroup(storeMatch[1]))
        };
    }

    return null;
}

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
    const newName = prompt("Enter the new file name (Chars: A-Z,0-9 Extension: bas)", oldName);
    if (newName === null || !isValidFileName(newName))
    {
        err = true;
        if (newName) {
            showNotification("danger", "Invalid name", "Chars: A-Z,0-9 Extension: bas");
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
    stripTrailingSpaces();
    if (refreshHexViewerContents)
        refreshHexViewerContents();
    _saveFile_impl(callback);
}

function isValidFileName(name)
{
    return name === 'icon.png' || /^[A-Z0-9]+\.bas$/i.test(name);
}

function isValidFileNameForBinary(name)
{
    return /.+\.(?:8[23x]p|8xp2)$/i.test(name);
}

function getNextAvailableBasicImportFileName()
{
    const existingFiles = new Set((proj.files || []).map((fileName) => fileName.toLowerCase()));
    let index = 1;
    let candidate;
    do {
        candidate = `SRC${index}.bas`;
        index++;
    } while (existingFiles.has(candidate.toLowerCase()));
    return candidate;
}

function createFileWithContent(name, content, cb, isLast, numFiles)
{
    if (isValidFileName(name) || isValidFileNameForBinary(name))
    {
        // deal with calculator var files
        if (isValidFileNameForBinary(name)) {
            if (!TIVarsLib) {
                alert('tivars_lib not ready?!');
                return;
            }
            TIVarsLib.FS.writeFile(name, new Uint8Array(content));
            const options = new TIVarsLib.options_t();
            options.set("prettify", false); // we want maximum roundtrippability
            options.set("reindent", false); // by default, keep it as-is
            let nameOverride = null;
            try {
                const varFile = TIVarsLib.TIVarFile.loadFromFile(name);
                content = varFile.getReadableContent(options);
                if (varFile.isEvoFormat && varFile.isEvoFormat()) {
                    const evoJSON = JSON.parse(content);
                    if (!evoJSON) { throw new Error('Invalid Evo format'); }
                    if (evoJSON?.typeName !== 'Program') { throw new Error('Not an Evo program'); }
                    content = evoJSON.code;
                    if (isValidFileName(evoJSON.name + '.bas')) {
                        nameOverride = evoJSON.name + '.bas';
                    }
                }
            } catch (e) {
                alert(`Unable to import ${name}: ${getTIVarsLibErrorMessage(e)}`);
                return;
            } finally {
                TIVarsLib.FS.unlink(name);
            }
            if (!content.length) {
                alert('Program appears to be empty or invalid');
                return;
            } else if (content.startsWith('[Error] This is a squished ASM program')) {
                alert('[Error] This is a squished ASM program, cannot import it!');
                return;
            }
            name = nameOverride ?? getNextAvailableBasicImportFileName();

            // If the current file is empty (or just the placeholer), just drop the new content into it.
            const editorContent = editor.getValue().trim();
            if (editorContent.length === 0 || editorContent === 'Disp "HELLO WORLD!') {
                editor.setValue(content);
                return;
            }
        }

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
            } else {
                ajaxAction("addFile", `fileName=${name}`, () =>
                {
                    proj.files = proj.files.concat([name]);
                    saveProjConfig();
                    ajaxAction("save", `file=${name}&source=${encodeURIComponent(content)}`, null, null, () => { cb(name, true) });
                }, () => { showNotification("danger", 'Oops?', 'An error happened, retry?'); cb(name) });
            }
        } else {
            const escapedName = $('<div/>').text(name).html();
            showNotification("warning", "File not imported", `'${escapedName}' already exists in the project`, null, 10000);
            if (typeof(cb) === "function") { cb(name); }
        }
    } else {
        const escapedName = $('<div/>').text(name).html();
        showNotification("warning", "File not imported", `'${escapedName}' is not a valid name (Chars: A-Z,0-9 Extension: bas)`, null, 10000);
        if (typeof(cb) === "function") { cb(name); }
    }
}

function deleteCurrentFile()
{
    if (window.confirm("Do you really want to delete this file?"))
    {
        if (proj.currFile && (isValidFileName(proj.currFile)))
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
        name = prompt("Enter the new file name (Chars: A-Z,0-9 Extension: bas)");
        if (name === null || !isValidFileName(name))
        {
            err = true;
            if (name) {
                showNotification("danger", "Invalid name", "Chars: A-Z,0-9 Extension: bas");
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
    ajaxAction("getAnalysis", `file=${proj.currFile}`, (output) => {
        code_analysis = parseAnalysisLog(output);
        doUpdateHints && updateHints(true);
    });
}

function getCtags(scope, cb)
{
    if (scope === undefined) { scope = proj.currFile; }
    console.log("TODO: Ctags ti-basic.");
/*
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
 */
    setTimeout(function() {
        const list = [];
        for (let i= 0; i<editor.lineCount(); i++) {
            editor.getLineTokens(i).forEach((o) => {
                if (o.type === 'label' || o.type === 'menu') {
                    list.push({ k: o.type, n: o.string, l: i+1 });
                }
            });
        }
        ctags = list;
        dispCodeOutline(list);
        if (typeof(cb) === "function") {
            cb();
        }
    }, 1);
}

function getSDKCtags()
{
    return ensureTokensJSONLoaded().then((tokensJSON) => {
        sdk_ctags = buildBasicSDKCtags(tokensJSON);
        return sdk_ctags;
    });
}

function getTokensJSON()
{
    return ensureTokensJSONLoaded();
}

function escapeBasicTokenBrowserHTML(value)
{
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getBasicTokenBrowserMode()
{
    try {
        return window.localStorage.getItem(basicTokenBrowserModeStorageKey) === 'hierarchical' ? 'hierarchical' : 'alphabetical';
    } catch (e) {
        return 'alphabetical';
    }
}

function setBasicTokenBrowserMode(mode)
{
    mode = mode === 'hierarchical' ? 'hierarchical' : 'alphabetical';
    try {
        window.localStorage.setItem(basicTokenBrowserModeStorageKey, mode);
    } catch (e) {}
    renderBasicTokenBrowser();
}

function getBasicTokenSortName(token)
{
    return (token.name || token.accessibleName || '').trim().toLocaleLowerCase();
}

function getBasicTokenBrowserSearchText(token)
{
    return [
        token.name,
        token.accessibleName,
        token.type,
        ...(token.categories || []),
        ...(token.nameVariants || []),
        ...((token.syntaxes || []).map((syntaxData) => syntaxData.syntax || ''))
    ].join('\n').toLocaleLowerCase();
}

function getBasicTokenBrowserDisplayTokens(tokensJSON)
{
    if (basicTokenBrowserTokens) {
        return basicTokenBrowserTokens;
    }

    basicTokenBrowserTokens = Object.values(tokensJSON.byBytes)
        .filter((token) => token.bytes !== '0x00')
        .slice()
        .sort((a, b) => {
            const nameCmp = getBasicTokenSortName(a).localeCompare(getBasicTokenSortName(b));
            return nameCmp || String(a.bytes).localeCompare(String(b.bytes));
        });

    return basicTokenBrowserTokens;
}

function getBasicTokenBrowserTokenHTML(token)
{
    const primaryCategory = (token.categories || [])[0] || '';
    const accessibleName = token.accessibleName && token.accessibleName !== token.name
        ? `<span class="basicTokenBrowserAccessibleName">${escapeBasicTokenBrowserHTML(token.accessibleName)}</span>`
        : '';
    const metaParts = [token.type, primaryCategory].filter(Boolean);
    const titleParts = [
        token.name,
        token.accessibleName ? `Accessible: ${token.accessibleName}` : '',
        token.bytes ? `Bytes: ${token.bytes}` : '',
        primaryCategory
    ].filter(Boolean);

    return `<li class="basicTokenBrowserToken" tabindex="0" data-token-bytes="${escapeBasicTokenBrowserHTML(token.bytes)}" title="${escapeBasicTokenBrowserHTML(titleParts.join('\n'))}">` +
        `<span class="basicTokenBrowserTokenBytes">${escapeBasicTokenBrowserHTML(token.bytes)}</span>` +
        `<code class="basicTokenBrowserTokenName">${escapeBasicTokenBrowserHTML(token.name)}</code>` +
        accessibleName +
        `<div class="basicTokenBrowserTokenMeta">${escapeBasicTokenBrowserHTML(metaParts.join(' · '))}</div>` +
        `</li>`;
}

function renderBasicTokenBrowserAlphabetical(tokens)
{
    return `<ul class="basicTokenBrowserList">${tokens.map(getBasicTokenBrowserTokenHTML).join('')}</ul>`;
}

function addTokenToBasicTokenBrowserTree(tree, categoryPath, token)
{
    const parts = categoryPath.split(' > ').map((part) => part.trim()).filter(Boolean);
    let node = tree;
    parts.forEach((part) => {
        if (!node.children[part]) {
            node.children[part] = { children: Object.create(null), tokens: [] };
        }
        node = node.children[part];
    });
    node.tokens.push(token);
}

function renderBasicTokenBrowserTreeNode(node, label, depth, forceOpen)
{
    const childNames = Object.keys(node.children).sort((a, b) => a.localeCompare(b));
    const tokensHTML = node.tokens.length
        ? `<ul class="basicTokenBrowserList">${node.tokens.map(getBasicTokenBrowserTokenHTML).join('')}</ul>`
        : '';
    const childrenHTML = childNames.map((childName) =>
        renderBasicTokenBrowserTreeNode(node.children[childName], childName, depth + 1, forceOpen)
    ).join('');

    if (label === null) {
        return tokensHTML + childrenHTML;
    }

    const tokenCount = countBasicTokenBrowserTreeTokens(node);
    return `<details class="basicTokenBrowserGroup basicTokenBrowserDepth${depth}" ${forceOpen || depth === 0 ? 'open' : ''}>` +
        `<summary>${escapeBasicTokenBrowserHTML(label)} <span>${tokenCount}</span></summary>` +
        tokensHTML +
        childrenHTML +
        `</details>`;
}

function countBasicTokenBrowserTreeTokens(node)
{
    return node.tokens.length + Object.values(node.children).reduce((count, child) => {
        return count + countBasicTokenBrowserTreeTokens(child);
    }, 0);
}

function renderBasicTokenBrowserHierarchical(tokens, forceOpen)
{
    const tree = { children: Object.create(null), tokens: [] };
    tokens.forEach((token) => {
        const categories = token.categories?.length ? token.categories : ['Uncategorized'];
        categories.forEach((category) => addTokenToBasicTokenBrowserTree(tree, category, token));
    });
    return renderBasicTokenBrowserTreeNode(tree, null, -1, forceOpen);
}

function renderBasicTokenBrowser()
{
    const browser = document.getElementById('basicTokenBrowser');
    if (!browser) {
        return;
    }

    const list = browser.querySelector('#basicTokenBrowserList');
    if (!list) {
        return;
    }

    const mode = getBasicTokenBrowserMode();
    browser.querySelectorAll('[data-basic-token-browser-mode]').forEach((button) => {
        button.classList.toggle('active', button.dataset.basicTokenBrowserMode === mode);
    });

    if (!window.tokens_json?.byBytes) {
        list.innerHTML = '<div class="basicTokenBrowserStatus">Loading tokens...</div>';
        ensureTokensJSONLoaded()
            .then(() => { renderBasicTokenBrowser(); })
            .catch(() => {
                list.innerHTML = '<div class="basicTokenBrowserStatus text-danger">Unable to load tokens.</div>';
            });
        return;
    }

    const query = (browser.querySelector('#basicTokenBrowserFilter')?.value || '').trim().toLocaleLowerCase();
    const tokens = getBasicTokenBrowserDisplayTokens(window.tokens_json)
        .filter((token) => !query || getBasicTokenBrowserSearchText(token).includes(query));

    browser.querySelector('#basicTokenBrowserCount').innerText = `${tokens.length} token${tokens.length === 1 ? '' : 's'}`;
    list.innerHTML = mode === 'hierarchical'
        ? renderBasicTokenBrowserHierarchical(tokens, query.length > 0)
        : renderBasicTokenBrowserAlphabetical(tokens);
}

function createBasicTokenBrowserIfNeeded()
{
    if (document.getElementById('basicTokenBrowser')) {
        return true;
    }

    const firepad = $("div.firepad").eq(0);
    if (!firepad.length) {
        return false;
    }

    firepad.prepend(
        '<div id="basicTokenBrowser" style="display:none">' +
            '<div id="basicTokenBrowserToolbar">' +
                '<input id="basicTokenBrowserFilter" type="text" placeholder="Filter tokens...">' +
                '<div class="btn-group btn-group-xs" role="group">' +
                    '<button type="button" class="btn btn-default" data-basic-token-browser-mode="alphabetical" title="Alphabetical">A-Z</button>' +
                    '<button type="button" class="btn btn-default" data-basic-token-browser-mode="hierarchical" title="Hierarchical">Tree</button>' +
                '</div>' +
            '</div>' +
            '<div id="basicTokenBrowserCount"></div>' +
            '<div id="basicTokenBrowserList"></div>' +
        '</div>'
    );

    $("#basicTokenBrowserFilter").on("input", debounce(renderBasicTokenBrowser, 50));
    $("#basicTokenBrowser").on("click", "[data-basic-token-browser-mode]", function() {
        setBasicTokenBrowserMode(this.dataset.basicTokenBrowserMode);
    });
    $("#basicTokenBrowser").on("click keydown", ".basicTokenBrowserToken", function(e) {
        if (e.type === "keydown" && !["Enter", " "].includes(e.key)) {
            return;
        }
        e.preventDefault();
        insertBasicTokenFromBrowser(this.dataset.tokenBytes);
    });

    renderBasicTokenBrowser();
    return true;
}

function recalcBasicTokenBrowserSize()
{
    const browser = document.getElementById("basicTokenBrowser");
    const divFirepad = document.querySelector("div.firepad");
    if (!browser || !divFirepad) { return; }

    const finalHeight = divFirepad.offsetHeight;
    browser.style.height = finalHeight + "px";
    const toolbarHeight = document.getElementById("basicTokenBrowserToolbar").offsetHeight;
    const countHeight = document.getElementById("basicTokenBrowserCount").offsetHeight;
    document.getElementById("basicTokenBrowserList").style.height = `${finalHeight - toolbarHeight - countHeight - 4}px`;
}

function refreshBasicTokenBrowserSize()
{
    const browser = document.getElementById("basicTokenBrowser");
    if (browser && $(browser).is(":visible")) {
        browser.style.display = "none";
        recalcBasicTokenBrowserSize();
        browser.style.display = "block";
        recalcBasicTokenBrowserSize();
    }
}

function insertBasicTokenFromBrowser(bytes)
{
    const token = window.tokens_json?.byBytes?.[bytes];
    if (!token || typeof editor !== "object" || editor.isReadOnly()) {
        return;
    }
    const tokenSource = token.name || token.accessibleName || '';
    const cursor = editor.getCursor();
    const tokenType = editor.getTokenTypeAt(cursor) || '';
    const forceToken = tokenType.split(/\s+/).includes('string');
    editor.replaceSelection((forceToken ? '\\' : '') + tokenSource);
    editor.focus();
}

function toggleBasicTokenBrowser(show, auto)
{
    if (auto === undefined) { auto = false; }
    if (!createBasicTokenBrowserIfNeeded()) {
        return;
    }

    recalcBasicTokenBrowserSize();

    const browser = $("#basicTokenBrowser");
    if (browser.is(":visible"))
    {
        if (typeof(show) === "boolean" && show) { return; }
        $("#basicTokenBrowserToggleButton").css('background-color', 'white');
    } else {
        if (typeof(show) === "boolean" && !show) { return; }
        $("#basicTokenBrowserToggleButton").css('background-color', '#CACBC7');
    }

    browser.toggle();
    $("div.CodeMirror").toggleClass("hasTokenBrowser");
    proj.show_token_browser = browser.is(":visible");
    if (proj.show_token_browser) {
        renderBasicTokenBrowser();
        recalcBasicTokenBrowserSize();
    }
    if (!auto) { saveProjConfig(); }
}

function downloadCurrentFile(name)
{
    name = (typeof(name) === 'undefined') ? prompt('Name of the file') : proj.currFile;
    if (name === null) { return false; }
    return downloadTextFile(name, editor.getValue());
}

function downloadTextFile(name, content)
{
    const dlLink = document.createElement('a');
    dlLink.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
    dlLink.setAttribute('download', name);

    if (document.createEvent) {
        const event = document.createEvent('MouseEvents');
        event.initEvent('click', true, true);
        dlLink.dispatchEvent(event);
    } else {
        dlLink.click();
    }

    return true;
}

function getAccessibleSourceDownloadName(name)
{
    return /\.[^.]+$/.test(name) ? name.replace(/(\.[^.]*)$/, '-accessible$1') : `${name}-accessible`;
}

function downloadAccessibleCurrentFile(name)
{
    const currPrgmName = proj.currFile.split(".")[0];

    name = (typeof(name) === 'undefined') ? prompt('Name of the file') : currPrgmName;
    if (name === null) { return false; }
    if (!TIVarsLib) {
        alert('tivars_lib not ready?!');
        return false;
    }

    const prgm = TIVarsLib.TIVarFile.createNew("Program", currPrgmName, '84+CE');
    prgm.setContentFromString(cm_getPrgmSourceTrimmed());

    const options = new TIVarsLib.options_t();
    options.set("accessible", true);
    options.set("prettify", false);
    options.set("reindent", false);

    return downloadTextFile(getAccessibleSourceDownloadName(name), prgm.getReadableContent(options));
}

function basicSourceHasLegacyByteEscapes(source)
{
    if (typeof TIVarsLib.TH_Tokenized_scanSourceTokens !== 'function') {
        return false;
    }

    const scanned = TIVarsLib.TH_Tokenized_scanSourceTokens(source, true);
    try {
        for (let i = 0; i < scanned.size(); i++) {
            const item = scanned.get(i);
            if (item.matched && /^\\x[0-9a-f]{2}$/i.test(item.text)) {
                return true;
            }
        }
    } finally {
        scanned.delete();
    }
    return false;
}

function makeBasicPrgm(format)
{
    if (!TIVarsLib) {
        alert('tivars_lib not ready?!');
        return;
    }

    format = normalizeBasicExportFormat(format);
    let prgmSource = cm_getPrgmSourceTrimmed();
    if (format === '8xp2' && basicSourceHasLegacyByteEscapes(prgmSource)
        && !confirm('This source uses \\xNN legacy 8-bit token escapes. TI-84 Evo programs use 16-bit tokens, so these values must be converted through the legacy-to-Evo mapping and may be ambiguous.\n\nUse named escapes or \\uNNNN when possible. Export the .8xp2 anyway?')) {
        return;
    }

    let file;
    try {
        const currPrgmName = proj.currFile.split(".")[0];
        const model = format === '8xp2' ? '84Evo' : '84+CE';
        const prgm = TIVarsLib.TIVarFile.createNew("Program", currPrgmName, model);
        prgm.setContentFromString(prgmSource);
        const filePath = prgm.saveVarToFile("", currPrgmName);
        file = TIVarsLib.FS.readFile(filePath, {encoding: 'binary'});
    } catch (e) {
        alert(`Unable to export this program as .${format}: ${getTIVarsLibErrorMessage(e)}`);
        return;
    }
    if (!file) {
        alert('Unable to convert the script to a program file - Try a smaller one?');
        return;
    }
    if (format === '8xp' && file.byteLength > 65525) {
        alert('File too big !?');
        return;
    }
    return file;
}

function downloadBasicPrgm(format)
{
    format = normalizeBasicExportFormat(format || getBasicExportFormat());
    setBasicExportFormat(format);
    const file = makeBasicPrgm(format);
    if (!file) {
        return;
    }
    const currPrgmName = proj.currFile.split(".")[0];
    const blob = new Blob([file], {type: 'application/octet-stream'});
    window['saveAs'](blob, `${currPrgmName}.${format}`);
}

function transferToEmuAndRun()
{
    // TODO: use a flag
    if ($("#rightSidebar").css("right")[0] === "-")
    {
        toggleRightSidebar();
    }
    if (emul_is_inited)
    {
        window.emul_file_load_error_extcb = function() {
            $("#buildRunButton").removeClass("disabled").attr("disabled", false).find("span.loadingicon").addClass("hidden");
        }
        window.emul_file_load_done_extcb = function() {
            const currPrgmName = proj.currFile.split(".")[0];
            console.log(`[PB] launching on CEmu: prgm${currPrgmName} ...`);
            setTimeout(() => { sendKey(0xDA); }, 100); // prgm
            setTimeout(() => { sendStringKeyPress(currPrgmName); }, 800);
            setTimeout(() => { sendKey(0x05); }, 500 + 300 * currPrgmName.length); // Enter
            setTimeout(() => { $("#buildRunButton").removeClass("disabled").attr("disabled", false).find("span.loadingicon").addClass("hidden"); }, 2500);
            window.emul_file_load_error_extcb = window.emul_file_load_done_extcb = null;
        }
        $("#buildRunButton").addClass("disabled").attr("disabled", true).find("span.loadingicon").removeClass("hidden");
        pauseEmul(false);
        const file = makeBasicPrgm('8xp');
        const currPrgmName = proj.currFile.split(".")[0];
        fileLoad(new Blob([file], {type: "application/octet-stream"}), `${currPrgmName}.8xp`, false);
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
    if (!navigator.usb || !self.isSecureContext) {
        alert("WebUSB is not available. Use a compatible browser (Chrome/Edge).");
        button.addClass("disabled").attr("disabled", true);
        return;
    }
    const file = makeBasicPrgm('8xp');
    if (!file) {
        return;
    }
    button.addClass("disabled").attr("disabled", true).find("span.loadingicon").removeClass("hidden");
    try {
        if (!window.pbWebUsbTransfer) {
            throw new Error("WebUSB transfer helper not loaded");
        }
        const result = await window.pbWebUsbTransfer.sendFileBytes(file, `${proj.prgmName}.8xp`);
        if (result === 0) {
            showNotification("success", "Transfer complete", `Sent ${proj.prgmName}.8xp to the calculator`);
        } else {
            showNotification("danger", "Transfer failed", `Calculator returned error ${result}`);
        }
    } catch (err) {
        showNotification("danger", "Transfer failed", err.message || err);
    }
    button.removeClass("disabled").attr("disabled", false).find("span.loadingicon").addClass("hidden");
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
    return []; // TODO
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
    refreshHexViewerSize();
    refreshBasicTokenBrowserSize();
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
