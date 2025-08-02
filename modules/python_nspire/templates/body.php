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

/** @var \ProjectBuilder\python_nspireProject $currProject */ ?>

    <textarea id="fakeContainer" style="display:none" title="" data-mtime="<?= $currProject->getCurrentFileMtime() ?>"><?= $currProject->getCurrentFileSourceHTML() ?></textarea>

    <div class="filelist">
        <ul class="nav nav-tabs">
            <?= $currProject->getFileListHTML() ?>
            <?php if ($pm->currentUserIsProjOwnerOrStaff() || $currProject->isMulti_ReadWrite())
            {
                if (count($currProject->getAvailableSrcFiles()) > 1 && $currProject->isCurrentFileDeletable())
                {
                    echo '<li class="active pull-right" style="margin-right:-2px;margin-left:3px;"><a style="color: #337ab7;" href="#" onclick="deleteCurrentFile(); return false;"><span class="glyphicon glyphicon-trash"></span> Delete current file</a></li>';
                }
                echo '<li class="active pull-right" style="margin-right:-2px;margin-left:5px;"><a style="color: #337ab7;" href="#" onclick="addFile(); return false;"><span class="glyphicon glyphicon-plus"></span> New file</a></li>';

                echo '<li class="active pull-right hasTooltip" style="margin-right:-2px;margin-left:3px;" data-placement="top" title="Click to toggle the code outline"><a id="codeOutlineToggleButton" style="color: #337ab7;" href="#" onclick="toggleOutline(); return false;"><span class="glyphicon glyphicon-align-left"></span></a></li>';
                if (!$currProject->canUserEditCurrentFile($currUser))
                {
                    echo '<li class="active pull-right" style="margin-right:-2px;margin-left:3px;"><a href="#"><b>Note</b>: this file is read-only</a></li>';
                }
                else
                {
                    echo '<li class="active pull-right hasTooltip" style="margin-right:-2px;margin-left:3px;" data-placement="top" title="Click to re-indent the file"><a id="reindentButton" style="color: #337ab7;" href="#" onclick="reindent(); return false;"><span class="glyphicon glyphicon-thumbs-up"></span></a></li>';
                }
            }
            ?>
        </ul>
    </div>

    <form id="postForm" action="ActionHandler.php" method="POST">
        <input type="hidden" name="id" value="<?= $projectID ?>">
        <input type="hidden" name="file" id="currFileInput" value="<?= $currProject->getCurrentFile() ?>">
        <input type="hidden" name="prgmName" id="prgmNameInput" value="CPRGMCE">
        <input type="hidden" name="action" value="download" id="actionInput">
        <input type="hidden" name="csrf_token" value="<?= $currUser->getSID() ?>">
    </form>

    <form id="zipDlForm" action="ActionHandler.php" method="POST">
        <input type="hidden" name="id" value="<?= $projectID ?>">
        <input type="hidden" name="action" value="downloadZipExport" id="actionInput2">
        <input type="hidden" name="csrf_token" value="<?= $currUser->getSID() ?>">
    </form>

    <?php if (!$currProject->isMulti_ReadWrite()) { echo '<div class="firepad">'; } ?>
    <textarea id="codearea"></textarea>
    <?php if (!$currProject->isMulti_ReadWrite()) { echo '</div>'; } ?>

    <div class='subfirepad'>
        <?php if ($pm->currentUserIsProjOwnerOrStaff() || $currProject->isMulti_ReadWrite()) { ?>
        <button id="saveButton" class="btn btn-primary btn-sm" onclick="saveFile(); return false" title="Save source on the server" disabled><span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> Save <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
        <?php } else { ?>
            <button id="saveButton" class="btn btn-primary btn-sm hide invisible"></button>
        <?php } ?>
        <div class="btn-group">
            <button id="builddlButton" class="btn btn-primary btn-sm" onclick="downloadTnsFile(); return false" title="Convert this code to a tns file"><span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> Download program (.tns) <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
            <button id="zipDlCaretButton" type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li><a onclick="downloadCurrentFile(proj.currFile); return false">Download current source file (.py)</a></li>
                <li role="separator" class="divider"></li>
                <li><a onclick="$('#zipDlForm').submit(); return false">Download project (.zip) with all sources</a></li>
            </ul>
        </div>
        <button id="transferButton" class="btn btn-primary btn-sm hasTooltip disabled" disabled title="Can't run it yet, however" onclick="transferToEmu(); return false"><span class="glyphicon glyphicon-share" aria-hidden="true"></span> Transfer to emulator <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
    </div>

    <div id="bottomToolsToggle" onclick="toggleBottomTools();"></div>
    <div id="bottomTools">
        <textarea id="consoletextarea" readonly></textarea>
    </div>

    <div class="modal fade" id="wizardModal" tabindex="-1" role="dialog" aria-labelledby="myWizardModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myWizardModalLabel">Project creation wizard</h4>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

