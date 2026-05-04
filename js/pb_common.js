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

/* Project builder-related functions etc. */

// Todo: check if that needs to be here too (it shouldn't...)
var build_output = [];
var build_check  = [];
var code_analysis = [];
var lastSavedSource = '';
const PB_DARK_THEME_STORAGE_KEY = "pb_use_dark";

function loadProjConfig()
{
    const lsConfig = localStorage.getItem(`config_${proj.pid}`);
    if (lsConfig)
    {
        // Overwrite some custom properties
        const conf = JSON.parse(lsConfig);
        if (typeof conf.show_left_sidebar !== "undefined") { proj.show_left_sidebar = conf.show_left_sidebar; }
        if (typeof conf.show_right_sidebar !== "undefined") { proj.show_right_sidebar = conf.show_right_sidebar; }
        if (typeof conf.show_bottom_tools !== "undefined") { proj.show_bottom_tools = conf.show_bottom_tools; }
        if (typeof conf.show_code_outline !== "undefined") { proj.show_code_outline = conf.show_code_outline; }
        if (typeof conf.show_hex_viewer !== "undefined") { proj.show_hex_viewer = conf.show_hex_viewer; }
        if (typeof conf.cursors !== "undefined") { proj.cursors = conf.cursors; }
        if (typeof conf.autocomplete_delay !== "undefined") { proj.autocomplete_delay = conf.autocomplete_delay; }
    }

    $(document).on("change", "#settingsForm input[name='sharingMode']", refreshSharingModeFormVisibility);
    refreshSharingModeFormVisibility();

    editorPostSetup();

    if (typeof editor === "object")
    {
        $("#customExtraSBButton").html('<span class="glyphicon glyphicon-question-sign"></span>')
            .attr("title", "Editor preferences, key bindings & credits")
            .on("click", showKeybindings)
            .show();
    }
}

function editorPostSetupAlways()
{
    const editorMode = editor.getMode().name;

    if (proj.show_bottom_tools === false && editorMode !== 'bbcode') {
        toggleBottomTools(0);
    }
    if (!proj.cursors) {
        proj.cursors = {};
    }
    $(".hasTooltip, [data-toggle='tooltip']").tooltip({container: 'body'});
    if (editorMode !== 'yaml' && editorMode !== 'bbcode') {
        toggleOutline(proj.show_code_outline, true);
    }
    if (!proj.is_multi && editorMode === 'tibasic') {
        toggleHexViewer(proj.show_hex_viewer, true);
    }
}

function editorPostSetup()
{
    setDarkTheme(getDarkThemePreference() === true, false);
    if (proj.show_left_sidebar === false) {
        toggleLeftSidebar(0);
    }
    if (proj.show_right_sidebar === false) {
        toggleRightSidebar(0);
    }
    editorPostSetupAlways();
}

function saveProjConfig()
{
    proj.updated = new Date().getTime();
    const conf = Object.assign({}, proj);
    delete conf.use_dark;
    localStorage.setItem(`config_${proj.pid}`, JSON.stringify(conf));
}

function refreshSharingModeFormVisibility()
{
    const isCustom = $("#settingsForm input[name='sharingMode']:checked").val() === "custom";
    const customRWAllowedUsersBlock = $("#customRWAllowedUsersBlock");
    if (!customRWAllowedUsersBlock.length) {
        return;
    }
    customRWAllowedUsersBlock.toggle(isCustom);
    $("#allowedRWUserIDs").prop("disabled", !isCustom);
}

function forkProject(doConfirm)
{
    if (typeof doConfirm !== "boolean") {
        doConfirm = true;
    }
    if (doConfirm && confirm("Are you sure?"))
    {
        saveFile(() => {
            ajaxAction("fork", "", (newID) => {
                showNotification("success", "Forked succesfully", "You will now be redirected to your new project", () =>
                {
                    window.onbeforeunload = null;
                    window.location.replace(`${window.location.href.split('?')[0]}?id=${newID}`);
                });
            });
        });
    }
}

function enableMultiUserRW()
{
    $("#settingsForm input[name='sharingMode'][value='custom']").prop("checked", true).trigger("change");
    refreshSharingModeFormVisibility();
    $("#settingsModal").modal();
}

function enableMultiUserRO()
{
    saveFile(() => {
        ajaxAction("enableMultiRO", "", () => { window.location.reload(); } );
    });
}

function disableMultiUser()
{
    if (confirm("Are you sure?"))
    {
        saveFile(() => {
            ajaxAction("disableMulti", "", () => {
                showNotification("info", "OK, Project unshared", "", () => { window.location.reload(); }, 1);
            });
        });
    } else {
        showNotification("info", "OK, Project is still shared", "", null, 1);
    }
}

function deleteProject()
{
    if (confirm("Are you sure you want to delete this project?"))
    {
        ajaxAction("deleteProj", "", () => {
            showNotification("success", "Project successfully deleted from the server", "You will now be redirected", () =>
            {
                window.onbeforeunload = null;
                window.location.replace("https://tiplanet.org/pb/");
            });
        });
    } else {
        showNotification("info", "Project not deleted", "", null, 1);
    }
}

function resetAll()
{
    deleteProject();
    window.onbeforeunload = null;
    window.location.replace(window.location.href.split('?')[0]);
}

function toggleLeftSidebar(delay)
{
    delay = (typeof delay === "number") ? delay : 180;

    document.getElementById("leftSidebarToggle").onclick = null;

    const mainWrapper = $(".wrapper");
    const sideBar = $("#leftSidebar");

    const needToggleLeftValue = parseFloat(sideBar.css("margin-left")) < 0;

    proj.show_left_sidebar = needToggleLeftValue;
    saveProjConfig();

    sideBar.animate( { "margin-left": (needToggleLeftValue ? '+=' : '-=') +(sideBar.width()+20) }, delay);
    mainWrapper.animate( { "margin-left": (needToggleLeftValue ? '+=' : '-=') +(sideBar.width()+13) }, delay);
    $("#leftSidebarToggle").animate( {width: (needToggleLeftValue ? '-=' : '+=')+(7) }, delay, 0);

    document.getElementById("leftSidebarToggle").onclick = toggleLeftSidebar;
}

function toggleRightSidebar(delay)
{
    delay = (typeof delay === "number") ? delay : 180;

    document.getElementById("rightSidebarToggle").onclick = null;

    const mainWrapper = $(".wrapper");
    const rightSidebar = $("#rightSidebar");
    const rightSidebarBorder = $("#rightSidebarBorder");
    const rightSidebarToggle = $("#rightSidebarToggle");

    const needToggleRightValue = parseFloat(rightSidebarToggle.css("right")) < 50;

    proj.show_right_sidebar = needToggleRightValue;
    saveProjConfig();

    if (needToggleRightValue)
        rightSidebar.toggle();

    const rightValue = 350; // mainWrapper.css("padding-right")
    rightSidebarBorder.animate({right: (needToggleRightValue ? '+=' : '-=') + (rightValue+10)}, delay);
    rightSidebarToggle.animate({width: (needToggleRightValue ? '-=' : '+=')+(7)}, { duration: delay, queue: false });
    rightSidebarToggle.animate({right: (needToggleRightValue ? '+=' : '-=') + (rightValue)}, { duration: delay, queue: false });

    mainWrapper.animate({"padding-right": ((parseFloat(mainWrapper.css("padding-right"))-10 >= needToggleRightValue) ? '-=' : '+=')+(rightValue)}, delay);

    rightSidebar.animate({right: (parseFloat(rightSidebar.css("right")) == 0 ? '-=' : '+=') + rightValue}, 200, 0, () => { if (!needToggleRightValue) rightSidebar.toggle(); } );

    document.getElementById("rightSidebarToggle").onclick = toggleRightSidebar;

    if (typeof rightSidebar_toggle_callback !== "undefined") {
        rightSidebar_toggle_callback(!needToggleRightValue);
    }
}

function toggleBottomTools(delay)
{
    delay = (typeof delay === "number") ? delay : 140;

    document.getElementById("bottomToolsToggle").onclick = null;

    const codeOutline = $("#codeOutline");
    const hexViewer = $("#hexViewer");
    const bottomTools = $("#bottomTools");

    const hexViewerVisible = hexViewer.is(":visible");

    const needOutlineToggle = delay > 0 && codeOutline.is(":visible");
    const needHexViewerToggle = delay > 0 && hexViewerVisible;

    if (needOutlineToggle && !bottomTools.is(":visible")) {
        codeOutline.hide();
    }
    if (hexViewerVisible && needHexViewerToggle && !bottomTools.is(":visible")) {
        hexViewer.hide();
    }

    bottomTools.slideToggle(delay, "swing", () => {
        proj.show_bottom_tools = bottomTools.is(":visible");
        saveProjConfig();
        $("div.subfirepad ul.dropdown-menu").parent().toggleClass("dropup", !proj.show_bottom_tools);
        document.getElementById("bottomToolsToggle").onclick = toggleBottomTools;
        if (needOutlineToggle) {
            recalcOutlineSize();
            codeOutline.show();
        }
        if (needHexViewerToggle) {
            recalcHexViewerSize();
            hexViewerVisible && hexViewer.show();
        }
    });
}

function toggleDarkTheme()
{
    setDarkTheme(!isDarkThemeEnabled(), true);
}

function isDarkThemeEnabled()
{
    return $(".darkThemeLink").filter((idx, el) => !!$(el).attr("href")).length > 0;
}

function setDarkTheme(enabled, savePreference)
{
    $(".darkThemeLink").each((idx, el) => {
        const darkThemeLink = $(el);
        darkThemeLink.attr("href", enabled ? darkThemeLink.data("href") : "");
    });
    proj.use_dark = enabled;

    if (savePreference) {
        localStorage.setItem(PB_DARK_THEME_STORAGE_KEY, enabled ? "1" : "0");
    }
}

function getDarkThemePreference()
{
    const storedValue = localStorage.getItem(PB_DARK_THEME_STORAGE_KEY);
    if (storedValue !== null) {
        return storedValue === "1" || storedValue === "true";
    }

    return migrateProjectDarkThemePreference();
}

function migrateProjectDarkThemePreference()
{
    let migratedPreference = null;
    let latestUpdate = -1;

    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (!key || key.indexOf("config_") !== 0) {
            continue;
        }

        try {
            const conf = JSON.parse(localStorage.getItem(key));
            if (typeof conf.use_dark !== "boolean") {
                continue;
            }

            const confUpdated = typeof conf.updated === "number" ? conf.updated : 0;
            if (confUpdated >= latestUpdate) {
                migratedPreference = conf.use_dark;
                latestUpdate = confUpdated;
            }
        } catch (e) {
            // Ignore invalid legacy project config entries.
        }
    }

    if (migratedPreference !== null) {
        localStorage.setItem(PB_DARK_THEME_STORAGE_KEY, migratedPreference ? "1" : "0");
    }

    return migratedPreference;
}

/* Adapted from https://gist.github.com/anaran/9198993 */
function showKeybindings()
{
    if (typeof editor !== "object") { return; }

    let i;
    let keymap = window.CodeMirror.keyMap[window.CodeMirror.defaults.keyMap];

    const newBindingsKeys = Object.keys(editor.state.keyMaps[0] ?? {});
    for (i=0; i<newBindingsKeys.length; i++) {
        keymap[newBindingsKeys[i]] = editor.state.keyMaps[0][newBindingsKeys[i]].name;
    }
    delete keymap.fallthrough;

    const orderedKM = {};
    Object.keys(keymap).sort().forEach( (key) => { orderedKM[key] = keymap[key]; });

    const commentKey = /(Mac|iPhone|iPod|iPad)/i.test(navigator.platform) ? 'Cmd-/' : 'Ctrl-/';
    orderedKM[commentKey] = '(Un)Comment selected lines';

    const modal = $("#keybindingsModal");
    let keymapHTML = '';
    Object.keys(orderedKM).forEach( (key) => {
        keymapHTML += key.split("-").map( (txt) => `<span class="calcButton"><tt>${txt}</tt></span>` ).join("") + ` : ${orderedKM[key]}<br/>`;
    });
    const editorPreferencesHTML = (typeof getEditorPreferencesHTML === "function") ? getEditorPreferencesHTML() : '';
    modal.find("div.modal-body").eq(0).html(`${editorPreferencesHTML}<h4 style="margin-top:0;">Key bindings</h4><div style='max-height:300px;overflow-y:scroll;'>${keymapHTML}</div>`);
    if (typeof setupEditorPreferencesUI === "function") {
        setupEditorPreferencesUI(modal[0]);
    }
    modal.appendTo("body").modal();
}
