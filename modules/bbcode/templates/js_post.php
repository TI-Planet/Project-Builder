<?php
/* This content will be included and displayed. */
if (!isset($pm)) { die('Ahem ahem'); }
/** @var \ProjectBuilder\bbcodeProject $currProject */
?>
<script>
    let editor = null;
    let savedSinceLastChange = true;
    let lastChangeTS = 0;
    let textarea, fakeContainer;

    function init_post_js_1()
    {
        textarea = document.getElementById('codearea');
        fakeContainer = document.getElementById('fakeContainer');

        /* CodeMirror init */
        CodeMirror.commands.autocomplete = function(cm) { cm.showHint({ hint: CodeMirror.hint.anyword }); };

        const toggleComment = () => { editor.execCommand('toggleComment') }
        editor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            styleActiveLine: true,
            matchBrackets: true,
            indentWithTabs: false,
            indentUnit: 4,
            tabSize: 4,
            foldGutter: true,
            showTrailingSpace: true,
            dragDrop: false,
            mode: 'bbcode',
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
            extraKeys: {"Ctrl-Space": "autocomplete", 'Ctrl-/': toggleComment, 'Cmd-/': toggleComment },
            highlightSelectionMatches: {showToken: /\w/},
            theme: 'xq-light',
            readOnly: <?= $currProject->canUserEditCurrentFile($currUser) ? 'false' : 'true' ?>
        });
        savedSinceLastChange = true; lastChangeTS = (new Date).getTime();
    }

    const _updatePreviewImpl = () => {
        const src = editor.getValue().trim();
        if (!src.length) { return; }
        ajaxAction('preview_bbcode', `source=${encodeURIComponent(src)}`, (resp) => {
            document.getElementById('bbcodePreviewContent').innerHTML = (resp && resp.html) ? resp.html : '';
        }, () => {}, null);
    };
    const updatePreview = debounce(_updatePreviewImpl, 400);

    function init_post_js_2()
    {
        const editorContainer = $('#editorContainer');
        const initialText = fakeContainer.value || '';
        editor.setValue(initialText);
        lastSavedSource = initialText;
        const saveBtn = document.getElementById('saveButton');
        if (saveBtn) { saveBtn.disabled = true; }

        editorContainer.css('pointer-events', 'auto');

        editor.on('change', () => {
            savedSinceLastChange = false;
            if (saveBtn) { saveBtn.disabled = false; }
            updatePreview();
        });
        // Show first preview
        updatePreview();
    }

    // Initialize immediately on template load
    init_post_js_1();
    init_post_js_2();
</script>
