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
    die('Ahem ahem');
}
/** @var \ProjectBuilder\native_eZ80Project $currProject */

if ($currProject->isMultiuser())
{
    /* TODO: Maybe put this stuff into the UserInfo class... */
    require_once 'firebase/firebase.php';
    $firebase_token = getOrGenerateFirebaseTokenForUID($currUser->getID());
} else {
    $firebase_token = '';
}
?>
<script>
    proj = {
        pid: '<?= $projectID ?>',
        name: '<?= $currProject->getName(); ?>',
        prgmName: '<?= $currProject->getInternalName(); ?>',
        currFile: '<?= $currProject->getCurrentFile(); ?>',
        updated: <?= $currProject->getUpdatedTstamp(); ?>,
        is_multi: <?= $currProject->isMultiuser() ? 'true' : 'false' ?>,
        use_dark: false,
        show_left_sidebar: true,
        show_right_sidebar: true,
        show_bottom_tools: true,
        show_code_outline: true
    };
    user = {
        id: '<?= $currUser->getID(); ?>',
        name: '<?= $currUser->getName(); ?>',
        avatar: '<?= $currUser->getAvatarURL(); ?>',
        firebase_token: '<?= $firebase_token ?>'
    };
</script>

<?php if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore() || $currProject->isMulti_ReadWrite()) { ?>
    <script src="<?= $modulePath ?>js/pb_additions.js"></script>
<?php } else { ?>
    <script>function saveFile(callback) { if (typeof callback === "function") callback(); }</script>
<?php } ?>

<script>
    // Common functions for all users

    function goToFile(newfile)
    {
        const newURL = `?id=${proj.pid}&file=${newfile}`;
        $.get(newURL, (data) =>
        {
            const wasReadOnly = editor.isReadOnly();
            editor.setOption("readOnly", true);
            proj.cursors[proj.currFile] = JSON.stringify(editor.getCursor());
            saveProjConfig();
            window.history.pushState(null, "", newURL);
            const oldConsoleContent = $("#consoletextarea").val();
            typeof(removeMyselfFromFirepad) === "function" && removeMyselfFromFirepad();
            $('#editorContainer').empty().append($(data).find('#editorContainer').children());
            $("#consoletextarea").val(oldConsoleContent);
            $(".firepad-userlist").remove();
            proj.currFile = newfile;
            init_post_js_1();
            do_cm_custom();
            init_post_js_2(true);
            editor.setOption("readOnly", wasReadOnly);
            editorPostSetup();
        });
    }
</script>

<script src="<?= $modulePath ?>codemirror/codemirror.min.js"></script>
<script src="<?= $modulePath ?>codemirror/active-line.js"></script>
<script src="<?= $modulePath ?>codemirror/clike.js"></script>
<script src="<?= $modulePath ?>codemirror/z80.js"></script>
<script src="<?= $modulePath ?>codemirror/dialog.js"></script>
<script src="<?= $modulePath ?>codemirror/show-hint.js"></script>
<script src="<?= $modulePath ?>codemirror/anyword-hint.js"></script>
<script src="<?= $modulePath ?>codemirror/brace-fold.js"></script>
<script src="<?= $modulePath ?>codemirror/closebrackets.js"></script>
<script src="<?= $modulePath ?>codemirror/comment-fold.js"></script>
<script src="<?= $modulePath ?>codemirror/foldcode.js"></script>
<script src="<?= $modulePath ?>codemirror/foldgutter.js"></script>
<script src="<?= $modulePath ?>codemirror/matchbrackets.js"></script>
<script src="<?= $modulePath ?>codemirror/search.js"></script>
<script src="<?= $modulePath ?>codemirror/searchcursor.js"></script>
<script src="<?= $modulePath ?>codemirror/match-highlighter.js"></script>
<script src="<?= $modulePath ?>codemirror/annotatescrollbar.js"></script>
<script src="<?= $modulePath ?>codemirror/matchesonscrollbar.js"></script>
<script src="<?= $modulePath ?>codemirror/trailingspace.js"></script>
<script src="<?= $modulePath ?>codemirror/jump-to-line.js"></script>

<?php
if ($currProject->isMulti_ReadWrite())
{
    echo "<script src='https://cdn.firebase.com/js/client/2.4.2/firebase.js'></script>\n";
    if ($currProject->isChatEnabled()) {
        echo "<link rel='stylesheet' href='https://cdn.firebase.com/libs/firechat/2.0.1/firechat.min.css'/>\n";
        echo "<script src='https://cdn.firebase.com/libs/firechat/2.0.1/firechat.min.js'></script>\n";
    }
    echo "<script src='https://cdn.firebase.com/libs/firepad/1.3.0/firepad.min.js'></script>\n";
    echo "<script src='{$modulePath}codemirror/firepad-userlist.js'></script>";
}

