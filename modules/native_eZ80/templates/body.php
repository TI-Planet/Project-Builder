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

$llvmGitSHA = htmlentities(exec('echo $(cd ' . __DIR__ . '/../../../../opt/llvm/ && git rev-parse --short HEAD)'), ENT_QUOTES);

/** @var \ProjectBuilder\native_eZ80Project $currProject */ ?>

    <textarea id="fakeContainer" style="display:none" title="" data-mtime="<?= $currProject->getCurrentFileMtime(); ?>"><?= $currProject->getCurrentFileSourceHTML(); ?></textarea>

    <div class="filelist">
        <ul class="nav nav-tabs">
            <?= $currProject->getFileListHTML(); ?>
            <?php if ($pm->currentUserIsProjOwnerOrStaff() || $currProject->isMulti_ReadWrite())
            {
                if (count($currProject->getAvailableFiles()) > 1)
                {
                    echo '<li class="active pull-right" style="margin-right:-2px;margin-left:3px;"><a style="color: #337ab7;" href="#" onclick="deleteCurrentFile(); return false;"><span class="glyphicon glyphicon-trash"></span> Delete current file</a></li>';
                }
                echo '<li class="active pull-right" style="margin-right:-2px;margin-left:5px;"><a style="color: #337ab7;" href="#" onclick="addFile(); return false;"><span class="glyphicon glyphicon-plus"></span> New file</a></li>';
                if (substr($currProject->getCurrentFile(), -2) === '.c')
                {
                    echo '<li class="active pull-right hasTooltip" style="margin-right:-2px;margin-left:3px;" data-placement="top" title="Click to show ASM"><a id="asmToggleButton" style="color: #337ab7;" href="#" onclick="dispSrc(); return false;"><span class="glyphicon glyphicon-sunglasses"></span></a></li>';
                }
                echo '<li class="active pull-right hasTooltip" style="margin-right:-2px;margin-left:3px;" data-placement="top" title="Click to toggle the code outline"><a id="codeOutlineToggleButton" style="color: #337ab7;" href="#" onclick="toggleOutline(); return false;"><span class="glyphicon glyphicon-align-left"></span></a></li>';
                if (preg_match('/\.[chp]+$/i', $currProject->getCurrentFile()) === 1)
                {
                    echo '<li class="active pull-right hasTooltip" style="margin-right:-2px;margin-left:3px;" data-placement="top" title="Click to re-indent the file"><a id="reindentButton" style="color: #337ab7;" href="#" onclick="reindent(); return false;"><span class="glyphicon glyphicon-thumbs-up"></span></a></li>';
                }
            }
            ?>
        </ul>
    </div>

    <?php if ($pm->currentUserIsProjOwnerOrStaff() || $currProject->isMulti_ReadWrite()) { ?>
    <form id="postForm" action="ActionHandler.php" method="POST">
        <input type="hidden" name="id" value="<?= $projectID ?>">
        <input type="hidden" name="file" id="currFileInput" value="<?= $currProject->getCurrentFile(); ?>">
        <input type="hidden" name="prgmName" id="prgmNameInput" value="CPRGMCE">
        <input type="hidden" name="action" value="download" id="actionInput">
        <input type="hidden" name="csrf_token" value="<?= $currUser->getSID() ?>">
    </form>
    <?php } ?>

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
        <div class="btn-group">
            <button id="buildButton" class="btn btn-primary btn-sm" onclick="buildAndGetLog(); return false" title="Compile, assemble, and link"><span class="glyphicon glyphicon-wrench" aria-hidden="true"></span> Build <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
            <button id="cleanButton" type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li class="hasTooltip" data-placement="right" title="Delete build files then build with ZDS"><a onclick="cleanProj(buildAndGetLog); return false">Clean &amp; Build</a></li>
                <li class="hasTooltip" data-placement="right" title="Delete build files"><a onclick="cleanProj(); return false">Clean only</a></li>
                <li role="separator" class="divider"></li>
                <li class="hasTooltip" data-placement="right" title="Show ASM from LLVM-eZ80 (<?= $llvmGitSHA ?>)"><a onclick="llvmCompile(); return false">Show ASM from LLVM <sup class="text-muted">alpha</sup></a></li>
                <li class="hasTooltip" data-placement="right" title="Show ASM from LLVM-eZ80 and diff with the ZDS one"><a onclick="llvmCompileAndDiff(); return false">Diff ASM from LLVM &amp; ZDS <sup class="text-muted">alpha</sup></a></li>
                <li class="hasTooltip" data-placement="right" title="Compile with LLVM-eZ80, and assemble+link with ZDS"><a onclick="buildAndGetLog(true); return false">Build from LLVM ASM <sup class="text-muted pull">alpha</sup></a></li>
            </ul>
        </div>
        <div class="btn-group">
            <button id="builddlButton" class="btn btn-primary btn-sm" onclick="buildAndDownload(); return false" title="Build and download the program (8xp file)"><span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> Download .8xp <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
            <button id="zipDlCaretButton" type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li class="hasTooltip" data-placement="right" title="Download this file only"><a onclick="downloadCurrentFile(proj.currFile); return false">Download current file</a></li>
                <li class="hasTooltip" data-placement="right" title="Download this file only as..."><a onclick="downloadCurrentFile(); return false">Download current file as...</a></li>
                <li role="separator" class="divider"></li>
                <li class="hasTooltip" data-placement="right" title="Download all the project's files in a .zip archive"><a onclick="$('#zipDlForm').submit(); return false">Download project as .zip</a></li>
            </ul>
        </div>
        <button id="buildRunButton" class="btn btn-primary btn-sm" onclick="buildAndRunInEmu(); return false" title="Build and run the program in the emulator"><span class="glyphicon glyphicon-share" aria-hidden="true"></span> Test in emulator <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
        <div id="buildTimestampContainer" class="hidden"><b>Latest build</b>: <span id="buildTimestamp"></span></div>
        <?php } else { ?>
        <a id="zipDlCaretButton" href="#" class="btn btn-primary btn-sm" title="Download the project's source code files in a .zip archive" onclick="$('#zipDlForm').submit(); return false">Download project as .zip</a>
        <?php }?>
    </div>

<?php if ($pm->currentUserIsProjOwnerOrStaff() || $currProject->isMulti_ReadWrite()) { ?>
    <div id="bottomToolsToggle" onclick="toggleBottomTools();"></div>
    <div id="bottomTools">
        <textarea id="consoletextarea" disabled></textarea>
    </div>
<?php } ?>

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

    <div class="modal fade" id="diffModal" tabindex="-1" role="dialog" aria-labelledby="myDiffModalLabel">
        <div class="modal-dialog" role="document" style="max-width: 1200px !important;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myDiffModalLabel">Diff ZDS - LLVM</h4>
                </div>
                <div class="modal-body" id="modalDiffSourceBody">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
