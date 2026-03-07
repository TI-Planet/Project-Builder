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
        firebase_token: '<?= $pm->currentUserHasLiveCollabEditAccess() ? $currUser->getOrGenerateFirebaseToken() : '' ?>'
    };
</script>

<script src="<?= cacheBusterPath("{$modulePath}js/pb_additions.js") ?>"></script>
<?php if (!$pm->currentUserCanWriteCurrentProject()) { ?>
    <script>function saveFile(callback) { if (typeof callback === "function") callback(); }</script>
<?php } ?>

<script>
    function goToFile(newfile)
    {
        const newURL = `?id=${proj.pid}&file=${newfile}`;
        fetchGET(newURL, 10000).then((resp) =>
        {
            const nextEditorHTML = resp.ok ? extractEditorContainerHTML(resp.body, '#codearea') : null;
            if (!nextEditorHTML) {
                fallbackToFullPageNavigation(isForumLoginRedirectURL(resp.url) ? resp.url : newURL,
                    isForumLoginRedirectURL(resp.url)
                        ? "Your TI-Planet session expired. Redirecting to login..."
                        : "The file view could not be refreshed automatically. Reloading this file normally...");
                return;
            }
            const editorContainer = $('#editorContainer');
            editorContainer.empty().html(nextEditorHTML);
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

<script src="/forum/js/highlighter.min.js"></script>
<script type="text/javascript">
    hljs.configure({"useBR":true});
    function do_highlight_codes() {
        $(document).ready(function(){
            $(".codebox dd code, code.inline").each( function(i, e){ hljs.highlightBlock(e); } );
            $("code.hljs:not(.inline)").each(function(){var n=$(this);if("true"!=n.attr("data-lines-done")){var e=n.attr("class").replace("hljs","").trim().split(/\s+/),t=""===e[0]?e[1]:e.length>1&&""!==e[e.length-1]?"<span title='("+e[1]+" ?)'>"+e[0]+"</span>":e[0],a=n.parent().siblings().eq(0)[0];a&&a.innerHTML&&(a.innerHTML=a.innerHTML.replace("Code: ","Code "+(t?t:'')+" :  "));var l=n.html().split("<br>");l.forEach(function(n,e){l[e]='<span class="hljs-comment line-number" data-line="'+(e+1)+'"></span>'+n}),n.html(l.join("<br>")),n.attr("data-lines-done","true")}});
        });
    }
    do_highlight_codes();
</script>

<script type="text/x-mathjax-config">
    MathJax.Hub.Config({ tex2jax: {inlineMath: [['$mathjax$','$mathjax$']]} });
    MathJax.Hub.Register.StartupHook("End Jax",function() { return MathJax.Hub.setRenderer('NativeMML'); });
</script>
<script type="text/javascript" src="/forum/js/mathjax_loader_new.js?v=2"></script>

<?php
if ($pm->currentUserHasLiveCollabEditAccess())
{
    echo "<script src='js/firebase.js'></script>\n";
    if ($currProject->isChatEnabled()) {
        echo "<link rel='stylesheet' href='css/firechat.min.css'/>\n";
        echo "<script src='js/firechat.min.js'></script>\n";
    }
    echo "<script src='js/firepad.min.js'></script>\n";
    echo "<script src='/pb/js/codemirror/firepad-userlist.js'></script>";
}
