<?php
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

/* This content will be included and displayed.
   This page should not be called directly. */
if (!isset($pb))
{
    die("Ahem ahem");
}

require "nocsrf.php";
$initialCSRFToken = \NoCSRF::generate('csrf_token');
header('pb-csrf-token: ' . $initialCSRFToken);

// TODO : FireChat can be global, not per-module.

// Globals usable in the templates
$currUser = $pb->getCurrentUser();
$currProject = $pb->getCurrentProject();
$modulePath = "modules/" . $currProject->getType() . "/";
$templatePath = $modulePath . "templates/";

?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>TI-Planet | Project Builder - Online <?= $currProject::MODULE_NAME; ?></title>

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="css/main.css">

<?php include $templatePath . "css.php"; ?>


    <script src="js/utils.js"></script>
    <script src="js/pb_common.js"></script>

    <script src="js/jquery-2.1.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

    <script>window.CSRFToken = '<?= $initialCSRFToken ?>';</script>

<?php include $templatePath . "js_pre.php"; ?>

</head>
<body>

<div class="allcontainer">

<div id="leftSidebar">
    <?php require "sidebar.php"; ?>
</div>

    <div id="leftSidebarToggle" class="sidebarToggle" onclick="toggleLeftSidebar();"></div>
    <div id="rightSidebarToggle" class="sidebarToggle" onclick="toggleRightSidebar();"></div>
    <div id="rightSidebarBorder"></div>

<div class="wrapper" id="editorContainer">
    <h3 style="margin-top: 0; margin-bottom: 6px;">Online <?= $currProject::MODULE_DESCRIPTION; ?></h3>

<?php include $templatePath . "body.php"; ?>

</div>

    <div id="rightSidebar" style="display: block">

        <div id="userlist">
            <div id="multiuser-invite">
                <?php
                if ($currProject->getAuthorID() === $currUser->getID()) {
                    if ($currProject->isMultiuser()) {
                        echo "<i>This project is shared (" . ($currProject->isMulti_ReadWrite() ? "Read/Write" : "Read only") . ")</i></br>
                                  <button class='btn btn-warning btn-xs' onclick='disableMultiUser();'>Disable sharing</button>";
                    } else {
                        echo "<i>Share your project with others...</i></br>
                                  <button title='Other users will not be able to modify the project' class='btn btn-success btn-xs' onclick='enableMultiUserRO();'>Read-Only</button>
                                  <button title='Other users will be able to modify the project' class='btn btn-success btn-xs' onclick='enableMultiUserRW();'>Read-Write</button>";
                    }
                    echo "<br/></br/>";
                    echo "<i>Want to clone this project?</i></br>
                              <button class='btn btn-success btn-xs' onclick='forkProject();'>Clone it</button><br/><br/>";
                } else {
                    echo "<i>Want to work on this project on your own?</i></br>
                              <button class='btn btn-success btn-xs' onclick='forkProject();'>Fork it</button><br/><br/>";
                }
                ?>
            </div>
        </div>

    <?php if ($currProject->isMultiuser() && $currProject->isMulti_ReadWrite()) { ?>
        <div id="firechat-wrapper"></div>
    <?php } ?>

    </div>

</div>

<?php include $templatePath . "js_post.php"; ?>


    <script>
        <?php if ($currProject->getAuthorID() == $currUser->getID() || $currUser->isModeratorOrMore() || $currProject->isMulti_ReadWrite()) { ?>
        window.onbeforeunload = function() {
            if (!savedSinceLastChange)
            {
                return "It seems you have not saved your changes. Close anyway?";
            }
        };
        <?php } ?>

        loadProjConfig();
    </script>

<?php include $templatePath . "js_afterConfInit.php"; ?>

    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

        ga('create', 'UA-25340424-5', 'auto');
        ga('send', 'pageview');
    </script>

</body>
</html>
