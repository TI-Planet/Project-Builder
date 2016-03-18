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
/** @var \ProjectBuilder\native_eZ80Project $currProject */ ?>

    <textarea id="fakeContainer" style="display:none" title=""><?= $currProject->getCurrentFileSourceHTML(); ?></textarea>

    <div class="filelist">
        <ul class="nav nav-tabs">
            <?= $currProject->getFileListHTML(); ?>
            <?php if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore() || $currProject->isMulti_ReadWrite()) {
                if ($currProject->getCurrentFile() !== "main.c")
                {
                    echo '<li class="active pull-right" style="margin-left: 5px;margin-right: -2px;"><a style="color: #337ab7;" href="#" onclick="deleteCurrentFile(); return false;"><span class="glyphicon glyphicon-trash"></span> Delete current file</a></li>';
                }
                echo '<li class="active pull-right" style="margin-right: -2px;"><a style="color: #337ab7;" href="#" onclick="addFile(); return false;"><span class="glyphicon glyphicon-plus"></span> Add file</a></li>';
             } ?>
        </ul>
    </div>

    <?php if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore() || $currProject->isMulti_ReadWrite()) { ?>
    <form id="postForm" action="ActionHandler.php" method="POST">
        <input type="hidden" name="id" value="<?= $projectID ?>">
        <input type="hidden" name="file" id="currFileInput" value="<?= $currProject->getCurrentFile(); ?>">
        <input type="hidden" name="prgmName" id="prgmNameInput" value="CPRGMCE">
        <input type="hidden" name="action" value="download" id="actionInput">
    </form>
    <?php } ?>

    <?php if (!$currProject->isMulti_ReadWrite())
        echo '<div class="firepad">';
    ?>
    <textarea id="codearea"></textarea>
    <?php if (!$currProject->isMulti_ReadWrite())
        echo '</div>';
    ?>

    <div class='subfirepad'>
        <?php if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore() || $currProject->isMulti_ReadWrite()) { ?>
        <button id="saveButton" class="btn btn-primary btn-large" onclick="saveFile(); return false" title="Save source on the server" disabled><span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> Save <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
        <button id="buildButton" class="btn btn-primary btn-large" onclick="buildAndGetLog(); return false" title="Compile, assemble, and link"><span class="glyphicon glyphicon-wrench" aria-hidden="true"></span> Build <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
        <button id="builddlButton" class="btn btn-primary btn-large" onclick="buildAndDownload(); return false" title="Download the built file"><span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> Build and get .8xp <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
        <div id="buildTimestampContainer" class="hidden"><b>Latest build</b>: <span id="buildTimestamp"></span></div>
        <div id="prgmNameContainer"><b>Program name</b>: <span id="prgmNameSpan">CPRGMCE</span><span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span> (<a href="#" onclick="changePrgmName(); return false;">Change</a>)</div>
        <?php } ?>
    </div>

<?php if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore() || $currProject->isMulti_ReadWrite()) { ?>
    <div class="console">
        <textarea id="consoletextarea" disabled></textarea>
    </div>
<?php } ?>
