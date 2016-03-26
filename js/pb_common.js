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
        proj = JSON.parse(lsConfig);
    }
    typeof(applyPrgmNameChange) === "function" && applyPrgmNameChange(proj.prgmName);
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

function toggleLeftSidebar()
{
    document.getElementById("leftSidebarToggle").onclick = null;
    var sideBar = $("#leftSidebar"); var mainWrapper = $(".wrapper");
    sideBar.animate( { "margin-left": (parseFloat(sideBar.css("margin-left")) < 0 ? '+=' : '-=') +(sideBar.width()+20) }, 200);
    mainWrapper.animate( { "margin-left": (parseFloat(sideBar.css("margin-left")) < 0 ? '+=' : '-=') +(sideBar.width()+14) }, 200);
    $("#leftSidebarToggle").animate( {width: (parseFloat(sideBar.css("margin-left")) < 0 ? '-=' : '+=')+(7) }, 200, 0, function() {
        document.getElementById("leftSidebarToggle").onclick = toggleLeftSidebar;
    });
}

function toggleRightSidebar()
{
    document.getElementById("rightSidebarToggle").onclick = null;
    var mainWrapper = $(".wrapper"); var sideBar = $("#rightSidebar");
    var rightSidebarBorder = $("#rightSidebarBorder"); var rightSidebarToggle = $("#rightSidebarToggle");
    var needToggleRightValue = parseFloat(rightSidebarToggle.css("right")) < 50;
    var constRightValue = 250; // mainWrapper.css("padding-right")
    rightSidebarBorder.animate({right: (needToggleRightValue ? '+=' : '-=') + constRightValue}, 200, 0, function() { rightSidebarBorder.toggle(); } );
    rightSidebarToggle.animate({right: (needToggleRightValue ? '+=' : '-=') + (constRightValue+10)}, 200);
    if (needToggleRightValue) {
        rightSidebarToggle.width(rightSidebarToggle.width()-20);
    } else {
        rightSidebarToggle.animate({width: (needToggleRightValue ? '-=' : '+=')+(20)}, 200);
    }
    mainWrapper.animate({"padding-right": ((parseFloat(mainWrapper.css("padding-right")) >= needToggleRightValue) ? '-=' : '+=')+(constRightValue)}, 200);
    var childrenCount = sideBar.children().length;
    sideBar.children().each(function(i)
    {
        sideBar.toggle();
        var child = $($(this)[0]);
        child.animate({right: (parseFloat(child.css("right")) == 0 ? '-=' : '+=') + (child.width() + 50)}, 200, 0, function ()
        {
            if (i==childrenCount-1) {
                document.getElementById("rightSidebarToggle").onclick = toggleRightSidebar;
            }
        });
    });
}

function toggleDarkTheme()
{
    $(".darkThemeLink").each(function(idx, el) {
        var darkThemeLink = $(el);
        darkThemeLink.attr("href", darkThemeLink.attr("href") ? "" : darkThemeLink.data("href"));
    });
}