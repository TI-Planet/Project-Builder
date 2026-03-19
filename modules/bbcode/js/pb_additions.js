/* BBCode module additions */
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
    let name = prompt("Enter the new name (25 letters max, A-Z 0-9 _-)", "");
    if (name != null)
    {
        if (!name.match(/^([A-Z0-9_\-]{1,25})$/i))
        {
            showNotification("danger", "Invalid name", "25 letters max, A-Z 0-9 _-");
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

function isValidFileName(name) {
    return /^[A-Za-z0-9 _\-]{1,64}\.bbcode$/.test(name);
}

const _saveFile_impl = (callback) =>
{
    const saveButton = document.getElementById('saveButton');
    const currSource = editor.getValue();
    if (currSource === lastSavedSource) { if (typeof callback === 'function') callback(); return; }
    removeClass(saveButton.querySelector('span.loadingicon'), 'hidden');
    ajaxAction("save", `source=${encodeURIComponent(currSource)}&baseSourceHash=${encodeURIComponent(fakeContainer?.dataset?.sourceHash || '')}`, (saveResp) => {
        addClass(saveButton.querySelector('span.loadingicon'), 'hidden');
        savedSinceLastChange = true; lastSavedSource = currSource; proj.updated = (new Date).getTime(); saveProjConfig();
        updateLoadedFileSnapshot(currSource, saveResp && saveResp.source_hash, saveResp && saveResp.mtime);
        if (typeof callback === 'function') callback();
    }, () => {
        addClass(saveButton.querySelector('span.loadingicon'), 'hidden');
        savedSinceLastChange = false;
    });
}

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

    _saveFile_impl(callback);
}

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
