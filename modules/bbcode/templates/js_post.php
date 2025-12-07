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
            lineWrapping: true,
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

    // Resizable vertical split between editor and preview
    (function(){
        const container = document.getElementById('bbcodeSplitContainer');
        const editorPane = document.getElementById('bbcodeEditorPane');
        const previewPane = document.getElementById('bbcodePreviewPane');
        const splitter = document.getElementById('bbcodeSplitter');
        if (!container || !editorPane || !previewPane || !splitter) { return; }

        const storageKey = `bbcode_split_${(window.proj && proj.pid) ? proj.pid : 'default'}`;
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            const pct = Math.max(15, Math.min(85, parseFloat(saved)));
            editorPane.style.flex = `0 0 ${pct}%`;
            previewPane.style.flex = '1 1 auto';
        }

        let dragging = false;
        const applyAt = (clientX) => {
            const rect = container.getBoundingClientRect();
            const relX = clientX - rect.left;
            const pct = Math.max(15, Math.min(85, (relX / rect.width) * 100));
            editorPane.style.flex = `0 0 ${pct}%`;
            previewPane.style.flex = '1 1 auto';
            localStorage.setItem(storageKey, pct.toFixed(2));
        };

        const onMouseMove = (e) => { if (dragging) { e.preventDefault(); applyAt(e.clientX); } };
        const onTouchMove = (e) => { if (dragging && e.touches && e.touches[0]) { e.preventDefault(); applyAt(e.touches[0].clientX); } };
        const stopDrag = () => { dragging = false; document.body.style.cursor = ''; document.body.style.userSelect = ''; };

        splitter.addEventListener('mousedown', (e) => { dragging = true; document.body.style.cursor = 'col-resize'; document.body.style.userSelect = 'none'; e.preventDefault(); });
        splitter.addEventListener('touchstart', () => { dragging = true; document.body.style.userSelect = 'none'; });
        window.addEventListener('mouseup', stopDrag);
        window.addEventListener('touchend', stopDrag);
        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('touchmove', onTouchMove, { passive: false });
    })();
</script>
