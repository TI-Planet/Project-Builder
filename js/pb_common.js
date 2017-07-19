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

function loadProjConfig()
{
    const lsConfig = localStorage.getItem(`config_${proj.pid}`);
    if (lsConfig)
    {
        // Overwrite some custom properties
        const conf = JSON.parse(lsConfig);
        if (typeof conf.use_dark !== "undefined") { proj.use_dark = conf.use_dark; }
        if (typeof conf.show_left_sidebar !== "undefined") { proj.show_left_sidebar = conf.show_left_sidebar; }
        if (typeof conf.show_right_sidebar !== "undefined") { proj.show_right_sidebar = conf.show_right_sidebar; }
        if (typeof conf.show_bottom_tools !== "undefined") { proj.show_bottom_tools = conf.show_bottom_tools; }
        if (typeof conf.show_code_outline !== "undefined") { proj.show_code_outline = conf.show_code_outline; }
        if (typeof conf.cursors !== "undefined") { proj.cursors = conf.cursors; }
    }
    editorPostSetup();
}

function editorPostSetupAlways()
{
    if (proj.show_bottom_tools === false) {
        toggleBottomTools(0);
    }
    if (!proj.cursors) {
        proj.cursors = {};
    }
    $(".hasTooltip, [data-toggle='tooltip']").tooltip({container: 'body'});
    toggleOutline(proj.show_code_outline, true);
}

function editorPostSetup()
{
    if (proj.use_dark === true) {
        toggleDarkTheme();
    }
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
    localStorage.setItem(`config_${proj.pid}`, JSON.stringify(proj));
}

function forkProject(doConfirm)
{
    if (typeof doConfirm !== "boolean") {
        doConfirm = true;
    }
    if (doConfirm && confirm("Are you sure?"))
    {
        saveFile(() => {
            ajax("ActionHandler.php", `id=${proj.pid}&action=fork`, newID => {
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
    saveFile(() => {
        ajax("ActionHandler.php", `id=${proj.pid}&action=enableMultiRW`, () => { window.location.reload(); } );
    });
}

function enableMultiUserRO()
{
    saveFile(() => {
        ajax("ActionHandler.php", `id=${proj.pid}&action=enableMultiRO`, () => { window.location.reload(); } );
    });
}

function disableMultiUser()
{
    if (confirm("Are you sure?"))
    {
        saveFile(() => {
            ajax("ActionHandler.php", `id=${proj.pid}&action=disableMulti`, () => {
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
        ajax("ActionHandler.php", `id=${proj.pid}&action=deleteProj`, () => {
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
    mainWrapper.animate( { "margin-left": (needToggleLeftValue ? '+=' : '-=') +(sideBar.width()+14) }, delay);
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
    const bottomTools = $("#bottomTools");

    const needOutlineToggle = delay > 0 && codeOutline.is(":visible");
    if (needOutlineToggle && !bottomTools.is(":visible")) {
        codeOutline.hide();
    }

    bottomTools.slideToggle(delay, "swing", () => {
        proj.show_bottom_tools = bottomTools.is(":visible");
        saveProjConfig();
        if (proj.show_bottom_tools) {
            $("#bottomTools").siblings().find(".dropdown-menu").parent().removeClass("dropup");
        } else {
            $("#bottomTools").siblings().find(".dropdown-menu").parent().addClass("dropup");
        }
        document.getElementById("bottomToolsToggle").onclick = toggleBottomTools;
        if (needOutlineToggle) {
            recalcOutlineSize();
            codeOutline.show();
        }
    });
}

function toggleDarkTheme()
{
    $(".darkThemeLink").each((idx, el) => {
        const darkThemeLink = $(el);
        darkThemeLink.attr("href", darkThemeLink.attr("href") ? "" : darkThemeLink.data("href"));
    });
    proj.use_dark = !!($(".darkThemeLink").attr('href'));
    saveProjConfig();
}