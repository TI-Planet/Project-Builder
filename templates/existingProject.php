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
if (!isset($pm))
{
    die('Ahem ahem');
}

require_once 'utils.php';

// Globals usable in the templates
$currUser = $pm->getCurrentUser();
$currProject = $pm->getCurrentProject();
$modulePath = 'modules/' . $currProject->getType() . '/';
$templatePath = $modulePath . 'templates/';
$currProjNameInTitle = htmlentities($currProject->getInternalName(), ENT_QUOTES);

?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>TI-Planet | '<?= $currProjNameInTitle ?>' | Online Project Builder</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="<?= cacheBusterPath('css/main.css') ?>">

<?php include $templatePath . 'css.php'; ?>

    <link rel="stylesheet" data-href="css/dark.css" class="darkThemeLink">

    <script src="<?= cacheBusterPath('js/utils.js') ?>"></script>
    <script src="<?= cacheBusterPath('js/localforage.min.js') ?>"></script>

    <script src="<?= cacheBusterPath('js/pb_common.js') ?>"></script>

    <script src="js/jquery-3.6.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/bootstrap-notify.min.js"></script>

    <script>window.CSRFToken = '<?= $currUser->getSID() ?>';</script>

<?php include $templatePath . 'js_pre.php'; ?>

</head>
<body>

<div id="contentcontainer">

    <div id="leftSidebar">
        <?php require 'sidebar.php'; ?>
    </div>
    <div id="leftSidebarToggle" class="sidebarToggle" onclick="toggleLeftSidebar();"></div>

    <div id="rightSidebar">
        <div id="rightSidebarContent">
            <?php include $templatePath . 'right_sidebar.php'; ?>
        </div>
    </div>
    <div id="rightSidebarToggle" class="sidebarToggle" onclick="toggleRightSidebar();"></div>
    <div id="rightSidebarBorder"></div>

    <div id="editorContainer" class="wrapper">
        <h3 style="margin-top: 0; margin-bottom: 6px; font-size: 22px;">Online <?= $currProject::PROJECT_MODULE_DESCRIPTION ?>
            <img id="xhrActivityIndicator" src="css/spinningIndicator.gif" title="There is some background activity happening right now..." class="hasTooltip" data-placement="bottom" />
        </h3>
        <?php require $templatePath . 'body.php'; ?>
    </div>
    <?php require $templatePath . 'config_modal.php'; ?>

</div>

<?php include $templatePath . 'js_post.php'; ?>

    <script>
        <?php if ($pm->currentUserIsProjOwnerOrStaff() || $currProject->isMulti_ReadWrite()) { ?>
        window.onbeforeunload = function() {
            if (!savedSinceLastChange)
            {
                return "It seems you have not saved your changes. Close anyway?";
            }
        };
        <?php } ?>
        loadProjConfig();
    </script>

<?php include $templatePath . 'js_afterConfInit.php'; ?>

</body>
</html>
