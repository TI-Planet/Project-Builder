<?php
/* This content will be included and displayed. */
if (!isset($pm)) { die('Ahem ahem'); }
/** @var \ProjectBuilder\bbcodeProject $currProject */
?>

<textarea id="fakeContainer" style="display:none" data-mtime="<?= $currProject->getCurrentFileMtime() ?>"><?= $currProject->getCurrentFileSourceHTML() ?></textarea>

<div class="filelist">
    <?php if ($pm->currentUserIsProjOwnerOrStaff() || $currProject->isMulti_ReadWrite()) { ?>
        <button id="saveButton" class="btn btn-primary btn-sm" onclick="saveFile(); return false" title="Save source on the server" disabled>
            <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
            Save <span class="loadingicon hidden"><span class="glyphicon glyphicon-refresh spinning"></span></span>
        </button>
    <?php } else { ?>
        <button id="saveButton" class="btn btn-primary btn-sm hide invisible"></button>
    <?php } ?>
</div>

<div class="row" style="height: 100%; margin-top:10px;">
    <div class="col-md-6" style="height: 100%; padding-right: 5px;">
        <textarea id="codearea"></textarea>
    </div>
    <div class="col-md-6" style="height: 100%; padding-left: 5px;">
        <div id="bbcodePreview" style="border: 1px solid #666; border-radius: 0 0 5px 5px; width: calc(100% - 15px); padding: 10px 0; background: #fff; height: calc(100% - 10px); overflow: scroll; position: absolute;">
            <style>
                @scope {
                    <?php echo file_get_contents("https://tiplanet.org/forum/style.php?id=1&lang=fr&v=2"); ?>
                }
            </style>
            <div class="post bg2">
                <div class="content">
                    <div id="bbcodePreviewContent"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
</div>
