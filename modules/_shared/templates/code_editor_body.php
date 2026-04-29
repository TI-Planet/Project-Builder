<?php
if (!isset($pm, $codeEditorConfig))
{
    die('Ahem ahem');
}

$isAnonymousViewer = $currUser->isAnonymous();
$editorUtilityButtonsHtml = $codeEditorConfig['editorUtilityButtonsHtml'] ?? '';
$showReformatButton = $codeEditorConfig['showReformatButton'] ?? false;
$showCanonicalizeButton = $codeEditorConfig['showCanonicalizeButton'] ?? false;
$codeareaPrefixHtml = $codeEditorConfig['codeareaPrefixHtml'] ?? '';
$downloadButtonOnclick = $codeEditorConfig['downloadButtonOnclick'];
$downloadButtonTitle = $codeEditorConfig['downloadButtonTitle'];
$downloadButtonLabel = $codeEditorConfig['downloadButtonLabel'];
$currentSourceDownloadLabel = $codeEditorConfig['currentSourceDownloadLabel'];
$preSourceDownloadMenuItemsHtml = $codeEditorConfig['preSourceDownloadMenuItemsHtml'] ?? '';
$extraSourceDownloadMenuItemsHtml = $codeEditorConfig['extraSourceDownloadMenuItemsHtml'] ?? '';
$postDownloadButtonsHtml = $codeEditorConfig['postDownloadButtonsHtml'] ?? '';
?>
    <textarea id="fakeContainer" style="display:none" title="" data-mtime="<?= $currProject->getCurrentFileMtime() ?>" data-source-hash="<?= $currProject->getCurrentFileSourceHash() ?>"><?= $currProject->getCurrentFileSourceHTML() ?></textarea>

    <div class="filelist">
        <ul class="nav nav-tabs">
            <?= $currProject->getFileListHTML($pm->currentUserCanWriteCurrentProject()) ?>
            <?php if ($pm->currentUserCanWriteCurrentProject())
            {
                if (count($currProject->getAvailableSrcFiles()) > 1 && $currProject->isCurrentFileDeletable())
                {
                    echo '<li class="active pull-right" style="margin-right:-2px;margin-left:3px;"><a style="color: #337ab7;" href="#" onclick="deleteCurrentFile(); return false;"><span class="glyphicon glyphicon-trash"></span> Delete current file</a></li>';
                }
                echo '<li class="active pull-right" style="margin-right:-2px;margin-left:5px;"><a style="color: #337ab7;" href="#" onclick="addFile(); return false;"><span class="glyphicon glyphicon-plus"></span> New file</a></li>';

                echo $editorUtilityButtonsHtml;
                echo '<li class="active pull-right hasTooltip" style="margin-right:-2px;margin-left:3px;" data-placement="top" title="Click to toggle the code outline"><a id="codeOutlineToggleButton" style="color: #337ab7;" href="#" onclick="toggleOutline(); return false;"><span class="glyphicon glyphicon-align-left"></span></a></li>';
                if (!$currProject->canUserEditCurrentFile($currUser))
                {
                    echo '<li class="active pull-right" style="margin-right:-2px;margin-left:3px;"><a href="#"><b>Note</b>: this file is read-only</a></li>';
                }
                else
                {
                    if ($showReformatButton)
                    {
                        echo '<li class="active pull-right hasTooltip" style="margin-right:-2px;margin-left:3px;" data-placement="top" title="Click to reformat/reindent the file"><a id="reformatButton" style="color: #337ab7;" href="#" onclick="reformat(); return false;"><span class="glyphicon glyphicon-thumbs-up"></span></a></li>';
                    }
                    if ($showCanonicalizeButton)
                    {
                        echo '<li class="active pull-right hasTooltip" style="margin-right:-2px;margin-left:3px;" data-placement="top" title="Click to canonicalize the code"><a id="canonicalizeButton" style="color: #337ab7;" href="#" onclick="canonicalize(); return false;"><span class="glyphicon glyphicon-ok-sign"></span></a></li>';
                    }
                }
            }
            ?>
        </ul>
    </div>

    <?php if (!$isAnonymousViewer) { ?>
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
    <?php } ?>

    <?php if (!$pm->currentUserHasLiveCollabEditAccess()) { echo '<div class="firepad">'; } ?>
    <?= $codeareaPrefixHtml ?>
    <textarea id="codearea"></textarea>
    <?php if (!$pm->currentUserHasLiveCollabEditAccess()) { echo '</div>'; } ?>

    <div class='subfirepad'>
        <?php if ($pm->currentUserCanWriteCurrentProject()) { ?>
        <button id="saveButton" class="btn btn-primary btn-sm" onclick="saveFile(); return false" title="Save source on the server" disabled><span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> Save <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
        <?php } else { ?>
            <button id="saveButton" class="btn btn-primary btn-sm hide invisible"></button>
        <?php } ?>
        <?php if (!$isAnonymousViewer) { ?>
        <div class="btn-group">
            <button id="builddlButton" class="btn btn-primary btn-sm" onclick="<?= $downloadButtonOnclick ?>" title="<?= htmlentities($downloadButtonTitle, ENT_QUOTES) ?>"><span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> <?= $downloadButtonLabel ?> <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
            <button id="zipDlCaretButton" type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <?= $preSourceDownloadMenuItemsHtml ?>
                <li><a onclick="downloadCurrentFile(proj.currFile); return false"><?= $currentSourceDownloadLabel ?></a></li>
                <?= $extraSourceDownloadMenuItemsHtml ?>
                <li role="separator" class="divider"></li>
                <li><a onclick="$('#zipDlForm').submit(); return false">Download project (.zip) with all sources</a></li>
            </ul>
        </div>
        <?= $postDownloadButtonsHtml ?>
        <?php } ?>
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
