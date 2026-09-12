<?php
/* This content will be included and displayed. */
if (!isset($pm)) { die('Ahem ahem'); }
/** @var \ProjectBuilder\bbcodeProject $currProject */
$isAnonymousViewer = $currUser->isAnonymous();
?>

<textarea id="fakeContainer" style="display:none" data-mtime="<?= $currProject->getCurrentFileMtime() ?>" data-source-hash="<?= $currProject->getCurrentFileSourceHash() ?>"><?= $currProject->getCurrentFileSourceHTML() ?></textarea>

<div class="toolbar" style="display: flex; align-items: center; justify-content: space-between; padding-right: 5px">
    <?php if ($pm->currentUserCanWriteCurrentProject()) { ?>
        <button id="saveButton" class="btn btn-primary btn-sm" onclick="saveFile(); return false" title="Save source on the server" disabled>
            <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
            Save <span class="loadingicon hidden"><span class="glyphicon glyphicon-refresh spinning"></span></span>
        </button>
    <?php } else { ?>
        <button id="saveButton" class="btn btn-primary btn-sm hide invisible"></button>
    <?php } ?>

    <div id="bbcodeTagToolbar" class="bbcodeTagToolbar" role="toolbar" aria-label="BBCode tools">
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-default bbcode-tag-button" data-bbcode-action="wrap" data-bbcode-tag="b" title="Bold"><strong>b</strong></button>
            <button type="button" class="btn btn-default bbcode-tag-button" data-bbcode-action="wrap" data-bbcode-tag="i" title="Italic"><em>i</em></button>
            <button type="button" class="btn btn-default bbcode-tag-button" data-bbcode-action="wrap" data-bbcode-tag="u" title="Underline"><span style="text-decoration: underline;">u</span></button>
        </div>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-default bbcode-tag-button" data-bbcode-action="snippet" data-bbcode-snippet="url" title="Insert link">url</button>
            <button type="button" class="btn btn-default bbcode-tag-button" data-bbcode-action="snippet" data-bbcode-snippet="img" title="Insert image">img</button>
        </div>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-default bbcode-tag-button" data-bbcode-action="wrap" data-bbcode-tag="code" title="Code block">code</button>
            <button type="button" class="btn btn-default bbcode-tag-button" data-bbcode-action="wrap" data-bbcode-tag="quote" title="Quote block">quote</button>
        </div>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-default bbcode-tag-button" data-bbcode-action="snippet" data-bbcode-snippet="list" title="Bullet list">list</button>
            <button type="button" class="btn btn-default bbcode-tag-button" data-bbcode-action="snippet" data-bbcode-snippet="table" title="Table">table</button>
        </div>
    </div>

    <div class="bbcodePreviewToolbarMeta">
        <label class="bbcodeScrollSyncLabel" title="Synchronize source and preview scrolling">
            <input type="checkbox" id="bbcodeScrollSync" checked> Sync scrolling
        </label>
        <span style="padding: 2px"><b>Render time:</b> <span id="bbcodeRenderTime">?</span></span>
        <div id="bbcodePreviewLanguageToolbar" class="btn-group btn-group-sm" role="group" aria-label="Preview language">
            <button type="button" class="btn btn-default bbcode-preview-language-button" data-preview-language-mode="fr" title="Force French preview">🇫🇷 FR</button>
            <button type="button" class="btn btn-default bbcode-preview-language-button" data-preview-language-mode="en" title="Force English preview">🇺🇸 EN</button>
        </div>
    </div>
</div>

<?php if (!$isAnonymousViewer) { ?>
<form id="postForm" action="ActionHandler.php" method="POST">
    <input type="hidden" name="id" value="<?= $projectID ?>">
    <input type="hidden" name="file" id="currFileInput" value="<?= $currProject->getCurrentFile() ?>">
    <input type="hidden" name="prgmName" id="prgmNameInput" value="Article">
    <input type="hidden" name="action" value="download" id="actionInput">
    <input type="hidden" name="csrf_token" value="<?= $currUser->getSID() ?>">
</form>
<?php } ?>

<div id="bbcodeSplitContainer" style="height: calc(100% - 72px); margin-top:10px; display: flex; flex-direction: row; align-items: stretch;">
    <div id="bbcodeEditorPane" style="height: 100%; min-width: 200px; flex: 0 0 50%; padding-right: 2px;">
        <?php if (!$pm->currentUserHasLiveCollabEditAccess()) { echo '<div class="firepad">'; } ?>
        <textarea id="codearea"></textarea>
        <?php if (!$pm->currentUserHasLiveCollabEditAccess()) { echo '</div>'; } ?>
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
        <div id="bbcodeAnalysisDock" style="margin-top:6px; border: 1px solid #666; border-radius: 4px; background:#f8f9fa; padding: 6px; min-height: 75px; max-height: 300px; overflow: auto;"></div>
    </div>
</div>
