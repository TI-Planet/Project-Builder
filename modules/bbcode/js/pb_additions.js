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
    ajaxAction("save", `source=${encodeURIComponent(currSource)}`, () => {
        addClass(saveButton.querySelector('span.loadingicon'), 'hidden');
        savedSinceLastChange = true; lastSavedSource = currSource; proj.updated = (new Date).getTime(); saveProjConfig();
        if (typeof callback === 'function') callback();
    }, () => {
        addClass(saveButton.querySelector('span.loadingicon'), 'hidden');
        savedSinceLastChange = false;
    });
}

function saveFile(callback)
{
    if (!document.getElementById('saveButton') || document.getElementById('saveButton').classList.contains('hide')) { if (typeof callback === 'function') callback(); return; }
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
