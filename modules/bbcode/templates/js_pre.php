<?php
/* This content will be included and displayed. */
if (!isset($pm)) { die('Ahem ahem'); }
require_once 'utils.php';
/** @var \ProjectBuilder\bbcodeProject $currProject */
?>
<script>
    proj = {
        pid: '<?= $projectID ?>',
        name: '<?= $currProject->getName() ?>',
        prgmName: '<?= $currProject->getInternalName() ?>',
        currFile: '<?= $currProject->getCurrentFile() ?>',
        updated: <?= $currProject->getUpdatedTstamp() ?>,
        is_multi: <?= $currProject->isMultiuser() ? 'true' : 'false' ?>,
        use_dark: false,
        show_left_sidebar: true,
        show_right_sidebar: false,
        show_bottom_tools: false,
        show_code_outline: false,
        autocomplete_delay: 600
    };
    user = {
        id: '<?= $currUser->getID() ?>',
        name: '<?= $currUser->getName() ?>',
        avatar: '<?= $currUser->getAvatarURL() ?>',
        firebase_token: '<?= $currProject->isMultiuser() ? $currUser->getOrGenerateFirebaseToken() : '' ?>'
    };
</script>

<script src="<?= cacheBusterPath("{$modulePath}js/pb_additions.js") ?>"></script>
<?php if (!$pm->currentUserIsProjOwnerOrStaff() && !$currProject->isMulti_ReadWrite()) { ?>
    <script>function saveFile(callback) { if (typeof callback === "function") callback(); }</script>
<?php } ?>

<script>
    function goToFile(newfile)
    {
        const newURL = `?id=${proj.pid}&file=${newfile}`;
        $.get(newURL, (data) =>
        {
            const editorContainer = $('#editorContainer');
            editorContainer.empty().append($(data).find('#editorContainer').children());
            proj.currFile = newfile;
            init_post_js_1();
            init_post_js_2();
            editorPostSetupAlways();
        });
    }
</script>

<script src="<?= cacheBusterPath('js/codemirror/codemirror.min.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/active-line.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/anyword-hint.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/comment.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/dialog.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/simple.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/bbcode.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/show-hint.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/anyword-hint.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/brace-fold.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/closebrackets.js"') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/comment-fold.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/foldcode.js"') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/foldgutter.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/matchbrackets.js"') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/search.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/searchcursor.js"') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/match-highlighter.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/annotatescrollbar.js"') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/matchesonscrollbar.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/trailingspace.js"') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/jump-to-line.js') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/diff_match_patch.js"') ?>"></script>
<script src="<?= cacheBusterPath('js/codemirror/merge.js') ?>"></script>

<?php
if ($currProject->isMulti_ReadWrite())
{
    echo "<script src='js/firebase.js'></script>\n";
    if ($currProject->isChatEnabled()) {
        echo "<link rel='stylesheet' href='css/firechat.min.css'/>\n";
        echo "<script src='js/firechat.min.js'></script>\n";
    }
    echo "<script src='js/firepad.min.js'></script>\n";
    echo "<script src='/pb/js/codemirror/firepad-userlist.js'></script>";
}
