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

var build_output = [];
var build_check  = [];
var lastSavedSource = '';

function loadProjConfig()
{
    var lsConfig = localStorage.getItem("config_" + proj.pid);
    if (lsConfig)
    {
        // Overwrite some custom properties
        var conf = JSON.parse(lsConfig);
        if (typeof conf.use_dark !== "undefined") { proj.use_dark = conf.use_dark; }
        if (typeof conf.show_left_sidebar !== "undefined") { proj.show_left_sidebar = conf.show_left_sidebar; }
        if (typeof conf.show_right_sidebar !== "undefined") { proj.show_right_sidebar = conf.show_right_sidebar; }
    }
    if (proj.use_dark === true) {
        toggleDarkTheme();
    }
    if (proj.show_left_sidebar === false) {
        toggleLeftSidebar(0);
    }
    if (proj.show_right_sidebar === false) {
        toggleRightSidebar(0);
    }
}

function saveProjConfig()
{
    proj.updated = new Date().getTime();
    localStorage.setItem("config_" + proj.pid, JSON.stringify(proj));
}

function forkProject(doConfirm)
{
    if (typeof doConfirm !== "boolean") {
        doConfirm = true;
    }
    if (doConfirm && confirm("Are you sure?"))
    {
        saveFile(function() {
            ajax("ActionHandler.php", "id=" + proj.pid + "&action=fork", function(data) {
                alert("Forked succesfully - You will now be redirected to your new project");
                window.onbeforeunload = null;
                window.location.replace(window.location.href.split('?')[0] + '?id=' + JSON.parse(data));
            });
        });
    }
}

function enableMultiUserRW()
{
    localStorage.setItem("invalidateFirebaseContent", "true");
    saveFile(function() {
        ajax("ActionHandler.php", "id=" + proj.pid + "&action=enableMultiRW", function() { window.location.reload(); } );
    });
}

function enableMultiUserRO()
{
    localStorage.setItem("invalidateFirebaseContent", "true");
    saveFile(function() {
        ajax("ActionHandler.php", "id=" + proj.pid + "&action=enableMultiRO", function() { window.location.reload(); } );
    });
}

function disableMultiUser()
{
    if (confirm("Are you sure?"))
    {
        saveFile(function() {
            ajax("ActionHandler.php", "id=" + proj.pid + "&action=disableMulti", function() { window.location.reload(); } );
        });
    }
}

function deleteProject()
{
    if (confirm("Are you sure you want to delete this project?"))
    {
        ajax("ActionHandler.php", "id=" + proj.pid + "&action=deleteProj", function ()
        {
            alert('Project succesfully deleted from the server');
            window.location.replace("https://tiplanet.org/pb/");
        });
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

    var mainWrapper = $(".wrapper");
    var sideBar = $("#leftSidebar");

    var needToggleLeftValue = parseFloat(sideBar.css("margin-left")) < 0;

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

    var mainWrapper = $(".wrapper");
    var rightSidebar = $("#rightSidebar");
    var rightSidebarBorder = $("#rightSidebarBorder");
    var rightSidebarToggle = $("#rightSidebarToggle");

    var needToggleRightValue = parseFloat(rightSidebarToggle.css("right")) < 50;

    proj.show_right_sidebar = needToggleRightValue;
    saveProjConfig();

    if (needToggleRightValue)
        rightSidebar.toggle();

    var constRightValue = 350; // mainWrapper.css("padding-right")
    rightSidebarBorder.animate({right: (needToggleRightValue ? '+=' : '-=') + (constRightValue+10)}, delay);
    rightSidebarToggle.animate({width: (needToggleRightValue ? '-=' : '+=')+(7)}, { duration: delay, queue: false });
    rightSidebarToggle.animate({right: (needToggleRightValue ? '+=' : '-=') + (constRightValue)}, { duration: delay, queue: false });

    mainWrapper.animate({"padding-right": ((parseFloat(mainWrapper.css("padding-right"))-10 >= needToggleRightValue) ? '-=' : '+=')+(constRightValue)}, delay);

    rightSidebar.animate({right: (parseFloat(rightSidebar.css("right")) == 0 ? '-=' : '+=') + constRightValue}, 200, 0, function() { if (!needToggleRightValue) rightSidebar.toggle(); } );

    document.getElementById("rightSidebarToggle").onclick = toggleRightSidebar;

    if (typeof rightSidebar_toggle_callback !== "undefined") {
        rightSidebar_toggle_callback(!needToggleRightValue);
    }
}

function toggleDarkTheme()
{
    proj.use_dark = !proj.use_dark;
    saveProjConfig();
    $(".darkThemeLink").each(function(idx, el) {
        var darkThemeLink = $(el);
        darkThemeLink.attr("href", darkThemeLink.attr("href") ? "" : darkThemeLink.data("href"));
    });
}