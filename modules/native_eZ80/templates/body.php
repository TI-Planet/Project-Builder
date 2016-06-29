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
            <?php if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore() || $currProject->isMulti_ReadWrite())
            {
                if ($currProject->getCurrentFile() !== "main.c")
                {
                    echo '<li class="active pull-right" style="margin-right:-2px;margin-left:3px;"><a style="color: #337ab7;" href="#" onclick="deleteCurrentFile(); return false;"><span class="glyphicon glyphicon-trash"></span> Delete current file</a></li>';
                }
                echo '<li class="active pull-right" style="margin-right:-2px;margin-left:3px;"><a style="color: #337ab7;" href="#" onclick="addFile(); return false;"><span class="glyphicon glyphicon-plus"></span> New file</a></li>';
                echo '<li class="active pull-right" style="margin-right:-2px;margin-left:3px;" data-toggle="tooltip" data-placement="bottom" title="Click to show ASM"><a id="asmToggleButton" style="color: #337ab7;" href="#" onclick="dispSrc(); return false;"><span class="glyphicon glyphicon-sunglasses"></span></a></li>';
            }
            ?>
        </ul>
    </div>

    <?php if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore() || $currProject->isMulti_ReadWrite()) { ?>
    <form id="postForm" action="ActionHandler.php" method="POST">
        <input type="hidden" name="id" value="<?= $projectID ?>">
        <input type="hidden" name="file" id="currFileInput" value="<?= $currProject->getCurrentFile(); ?>">
        <input type="hidden" name="prgmName" id="prgmNameInput" value="CPRGMCE">
        <input type="hidden" name="action" value="download" id="actionInput">
        <input type="hidden" name="csrf_token" value="<?= $currUser->getSID() ?>">
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
        <button id="saveButton" class="btn btn-primary btn-sm" onclick="saveFile(); return false" title="Save source on the server" disabled><span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> Save <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
        <div class="btn-group">
            <button id="buildButton" class="btn btn-primary btn-sm" onclick="buildAndGetLog(); return false" title="Compile, assemble, and link"><span class="glyphicon glyphicon-wrench" aria-hidden="true"></span> Build <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
            <button id="cleanButton" type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li title="Delete build files then build"><a onclick="cleanProj(buildAndGetLog); return false">Clean &amp; Build</a></li>
                <li title="Delete build files"><a onclick="cleanProj(); return false">Clean only</a></li>
            </ul>
        </div>
        <button id="builddlButton" class="btn btn-primary btn-sm" onclick="buildAndDownload(); return false" title="Build and download the program (8xp file)"><span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> Download .8xp <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
        <button id="buildRunButton" class="btn btn-primary btn-sm" onclick="buildAndRunInEmu(); return false" title="Build and run the program in the emulator"><span class="glyphicon glyphicon-share" aria-hidden="true"></span> Test in emulator <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
        <div id="buildTimestampContainer" class="hidden"><b>Latest build</b>: <span id="buildTimestamp"></span></div>
        <?php } ?>
    </div>

<?php if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore() || $currProject->isMulti_ReadWrite()) { ?>
    <div class="console">
        <textarea id="consoletextarea" disabled></textarea>
    </div>
<?php } ?>

    <div class="modal fade" id="bootstrapPopup" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Project creation wizard</h4>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
