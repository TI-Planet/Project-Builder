<?php
/* This content will be included and displayed. */
if (!isset($pm)) { die('Ahem ahem'); }
/** @var \ProjectBuilder\bbcodeProject $currProject */
?>

<textarea id="fakeContainer" style="display:none" data-mtime="<?= $currProject->getCurrentFileMtime() ?>"><?= $currProject->getCurrentFileSourceHTML() ?></textarea>

<div class="toolbar" style="display: flex; justify-content: space-between;">
    <?php if ($pm->currentUserIsProjOwnerOrStaff() || $currProject->isMulti_ReadWrite()) { ?>
        <button id="saveButton" class="btn btn-primary btn-sm" onclick="saveFile(); return false" title="Save source on the server" disabled>
            <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
            Save <span class="loadingicon hidden"><span class="glyphicon glyphicon-refresh spinning"></span></span>
        </button>
    <?php } else { ?>
        <button id="saveButton" class="btn btn-primary btn-sm hide invisible"></button>
    <?php } ?>
    <span style="padding: 4px"><b>BBCode processing time:</b> <span id="bbcodeRenderTime">?</span></span>
</div>

<form id="postForm" action="ActionHandler.php" method="POST">
    <input type="hidden" name="id" value="<?= $projectID ?>">
    <input type="hidden" name="file" id="currFileInput" value="<?= $currProject->getCurrentFile() ?>">
    <input type="hidden" name="prgmName" id="prgmNameInput" value="Article">
    <input type="hidden" name="action" value="download" id="actionInput">
    <input type="hidden" name="csrf_token" value="<?= $currUser->getSID() ?>">
</form>

<div id="bbcodeSplitContainer" style="height: calc(100% - 70px); margin-top:10px; display: flex; flex-direction: row; align-items: stretch;">
    <div id="bbcodeEditorPane" style="height: 100%; min-width: 200px; flex: 0 0 50%; padding-right: 2px;">
        <?php if (!$currProject->isMulti_ReadWrite()) { echo '<div class="firepad">'; } ?>
        <textarea id="codearea"></textarea>
        <?php if (!$currProject->isMulti_ReadWrite()) { echo '</div>'; } ?>
    </div>
    <div id="bbcodeSplitter" title="Drag to resize" style="width: 5px; cursor: col-resize; background: #ffdd8e; outline: 1px solid #d2af05; border-radius: 4px; margin: 0 5px; padding: 2px; height: 40px; position: relative; top: calc(50% - 35px);" role="separator"></div>
    <div id="bbcodePreviewPane" style="height: 100%; min-width: 200px; flex: 1 1 50%; padding-left: 2px; padding-right: 5px; display:flex; flex-direction: column;">
        <div id="bbcodePreview" style="border: 1px solid #666; border-radius: 0 0 5px 5px; width: 100%; padding: 10px 0; background: #f1f2f2; flex: 1 1 auto; overflow: auto; position: relative;">
            <style>
                @scope {
                    #previewFakeBody {
                        font-family: -apple-system, BlinkMacSystemFont, "Lucida Grande", "Trebuchet MS", Verdana, Helvetica, Arial, sans-serif;
                        background-color: #7d8b8c;
                        color: #525252;
                        font-size: 10px;
                    }
                    .postbody .content img {
                        border-radius: 2px;
                    }
                    .latexcode {
                        display:inline-block;
                    }
                    <?php
                        echo file_get_contents("https://tiplanet.org/forum/style.php?id=1&lang=fr&v=2");
                        echo "\n";
                        echo file_get_contents($phpbb_root_path . "/css/highlight_style.css");
                    ?>
                }
            </style>
            <div id="previewFakeBody">
                <div class="post bg2">
                    <div class="inner">
                        <div class="postbody" style="width: 100% !important;">
                            <div class="content">
                                <div id="bbcodePreviewContent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="bbcodeAnalysisDock" style="margin-top:6px; border: 1px solid #666; border-radius: 4px; background:#f8f9fa; padding: 6px; min-height: 15%; max-height: 35%; overflow: auto;"></div>
    </div>
</div>
