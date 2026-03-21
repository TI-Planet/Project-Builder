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
    let bbcodeInlineStyleMarks = [];

    const clearBBCodeInlineStyleMarks = () => {
        bbcodeInlineStyleMarks.forEach((mark) => mark.clear());
        bbcodeInlineStyleMarks = [];
    };

    const getBBCodeInlineStyleClassName = (activeTags) => {
        if (activeTags.length === 0) {
            return '';
        }

        const tagSet = new Set(activeTags);
        const classNames = ['cm-bbcode-inline'];
        if (tagSet.has('b')) { classNames.push('cm-bbcode-inline-bold'); }
        if (tagSet.has('i')) { classNames.push('cm-bbcode-inline-italic'); }
        if (tagSet.has('u') && tagSet.has('s')) {
            classNames.push('cm-bbcode-inline-decoration-both');
        } else if (tagSet.has('u')) {
            classNames.push('cm-bbcode-inline-decoration-underline');
        } else if (tagSet.has('s')) {
            classNames.push('cm-bbcode-inline-decoration-strikethrough');
        }

        return classNames.join(' ');
    };

    const _updateBBCodeInlineStyleMarksImpl = () => {
        if (!editor) {
            return;
        }

        clearBBCodeInlineStyleMarks();

        const src = editor.getValue();
        if (!src.length) {
            return;
        }

        const inlineTags = new Set(['b', 'i', 'u', 's']);
        const tagRe = /\[(\/)?([a-zA-Z*]+)(?:=[^\]\n]*)?(\s*\/)?\]/g;
        const stack = [];
        let inCodeDepth = 0;
        let lastIndex = 0;
        let m;

        const addStyledRange = (fromIndex, toIndex) => {
            if (inCodeDepth > 0 || toIndex <= fromIndex) {
                return;
            }

            const className = getBBCodeInlineStyleClassName(stack);
            if (!className) {
                return;
            }

            bbcodeInlineStyleMarks.push(editor.markText(
                editor.posFromIndex(fromIndex),
                editor.posFromIndex(toIndex),
                { className }
            ));
        };

        while ((m = tagRe.exec(src)) !== null) {
            const idx = m.index;
            addStyledRange(lastIndex, idx);

            const isClosing = !!m[1];
            const name = (m[2] || '').toLowerCase();
            const trailingSlash = !!(m[3] && m[3].trim().length);

            if (name === 'code' && !trailingSlash) {
                if (isClosing) {
                    inCodeDepth = Math.max(0, inCodeDepth - 1);
                } else {
                    inCodeDepth++;
                }
            } else if (inlineTags.has(name) && inCodeDepth === 0 && !trailingSlash) {
                if (isClosing) {
                    for (let i = stack.length - 1; i >= 0; i--) {
                        if (stack[i] === name) {
                            stack.splice(i, 1);
                            break;
                        }
                    }
                } else {
                    stack.push(name);
                }
            }

            lastIndex = tagRe.lastIndex;
        }

        addStyledRange(lastIndex, src.length);
    };

    const updateBBCodeInlineStyleMarks = debounce(_updateBBCodeInlineStyleMarksImpl, 100);

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
        updateBBCodeInlineStyleMarks();
        savedSinceLastChange = true; lastChangeTS = (new Date).getTime();
    }

    // Basic BBCode analyzer: produces stats and errors for user feedback
    const analyzeBBCode = (src) => {
        const result = {
            totalTags: 0,
            opened: 0,
            closed: 0,
            selfClosed: 0,
            errors: [],
            warnings: [],
            lines: (src.match(/\n/g) || []).length + 1,
            length: src.length,
        };

        const selfClosing = new Set(['*']);
        const balancedLikely = new Set(['b','i','u','s','url','img','quote','code','list','color','size','center','left','right','table','tr','td','th','spoiler','indent','align']);
        const tagRe = /\[(\/)?([a-zA-Z*]+)(?:=([^\]\n]*))?(\s*\/)?\]/g; // [tag], [tag=...], [/tag], [tag /]

        const stack = [];
        let m;
        let inCode = false; // ignore parsing inside [code]...[/code]

        const posToLineCol = (idx) => {
            let line = 1, col = 1;
            for (let i = 0; i < idx && i < src.length; i++) {
                if (src.charCodeAt(i) === 10) { line++; col = 1; } else { col++; }
            }
            return { line, col };
        };

        while ((m = tagRe.exec(src)) !== null) {
            const full = m[0];
            const isClosing = !!m[1];
            const rawName = m[2] || '';
            const trailingSlash = !!(m[4] && m[4].trim().length);
            const name = rawName.toLowerCase();
            const idx = m.index;

            // Count totals
            result.totalTags++;

            // Handle [code] sections: toggle when encountering open/close
            if (!isClosing && name === 'code' && !trailingSlash) {
                stack.push({ name, index: idx });
                result.opened++;
                inCode = true;
                continue;
            }
            if (isClosing && name === 'code') {
                // find last code in stack
                let foundAt = -1;
                for (let i = stack.length - 1; i >= 0; i--) { if (stack[i].name === 'code') { foundAt = i; break; } }
                if (foundAt === -1) {
                    const { line, col } = posToLineCol(idx);
                    result.errors.push({ type: 'orphanClosing', message: `Closing [/code] without an opening [code]`, line, col });
                } else {
                    stack.splice(foundAt, 1);
                    result.closed++;
                }
                inCode = false;
                continue;
            }

            if (inCode) { continue; }

            const isSelfClosing = selfClosing.has(name) || (!isClosing && trailingSlash);

            if (!isClosing) {
                if (isSelfClosing) {
                    result.selfClosed++;
                } else {
                    stack.push({ name, index: idx });
                    result.opened++;
                }
            } else {
                // closing tag
                if (stack.length === 0) {
                    const { line, col } = posToLineCol(idx);
                    result.errors.push({ type: 'orphanClosing', message: `Closing [/${name}] without an opening [${name}]`, line, col });
                } else {
                    const top = stack[stack.length - 1];
                    if (top.name === name) {
                        stack.pop();
                        result.closed++;
                    } else {
                        // Search for matching open deeper in the stack
                        let foundAt = -1;
                        for (let i = stack.length - 1; i >= 0; i--) {
                            if (stack[i].name === name) { foundAt = i; break; }
                        }
                        const { line, col } = posToLineCol(idx);
                        if (foundAt === -1) {
                            result.errors.push({ type: 'mismatch', message: `Closing [/${name}] does not match the last opened [${top.name}]`, line, col });
                        } else {
                            // Unclosed tags between foundAt and top
                            for (let i = stack.length - 1; i > foundAt; i--) {
                                const unclosed = stack[i];
                                const p = posToLineCol(unclosed.index);
                                result.errors.push({ type: 'prematureClose', message: `Tag [${unclosed.name}] was not closed before closing [/${name}]`, line: p.line, col: p.col });
                            }
                            stack.splice(foundAt, 1);
                            result.closed++;
                        }
                    }
                }
            }
        }

        // Remaining unclosed tags
        for (const t of stack) {
            if (!balancedLikely.has(t.name)) { continue; }
            const p = posToLineCol(t.index);
            result.errors.push({ type: 'unclosed', message: `Tag [${t.name}] opened here is not closed`, line: p.line, col: p.col });
        }

        // Prepare HTML
        const errorItems = result.errors.map(e => `<li><span style="color:#b00;">${e.message}</span> <span style="opacity:.7;">(line ${e.line}, col ${e.col})</span></li>`).join('');
        const warnItems = result.warnings.map(e => `<li>${e}</li>`).join('');
        const statusColor = result.errors.length ? '#b00' : '#2b7a0b';
        const statusText = result.errors.length ? `${result.errors.length} error${result.errors.length>1?'s':''} detected` : 'No BBCode structural issues detected';

        const html = `
            <div id="bbcodeAnalysis" style="font:13px/1.4 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
                <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
                    <strong>BBCode analysis</strong>
                    <span style="color:${statusColor}">${statusText}</span>
                    <span style="opacity:.8">Lines: ${result.lines}</span>
                    <span style="opacity:.8">Chars: ${result.length}</span>
                    <span style="opacity:.8">Tags: ${result.totalTags} (open: ${result.opened}, closed: ${result.closed}, self: ${result.selfClosed})</span>
                </div>
                ${warnItems ? `<ul style="margin:6px 0 0 18px;color:#a60;">${warnItems}</ul>` : ''}
                ${errorItems ? `<ul style="margin:6px 0 0 18px;">${result.errors.map(e => `
                    <li>
                        <span style="color:#b00;">${e.message}</span>
                        <a href="#" class="bbcode-jump" data-line="${e.line}" data-col="${e.col}" style="margin-left:6px; text-decoration: underline; color: #06c;">
                            (line ${e.line}, col ${e.col})
                        </a>
                    </li>`).join('')}</ul>` : ''}
            </div>`;

        return { ...result, html };
    };

    const _updatePreviewImpl = () => {
        const src = editor.getValue().trim();
        if (!src.length) { return; }
        ajaxAction('preview_bbcode', `source=${encodeURIComponent(src)}`, (resp) => {
            document.getElementById('bbcodePreviewContent').innerHTML = (resp && resp.html) ? resp.html : '';
            document.getElementById('bbcodeRenderTime').textContent = (resp && resp.renderTime) ? (resp.renderTime + 'ms') : '?';
            window.do_mathJax && do_mathJax();
            window.do_highlight_codes && do_highlight_codes();

            // Run analysis locally and render it in a dedicated dock below the preview
            try {
                const analysis = analyzeBBCode(src);
                const dock = document.getElementById('bbcodeAnalysisDock');
                if (dock) {
                    dock.innerHTML = analysis.html;
                    dock.style.minHeight = dock.style.maxHeight = Math.min(300, Math.max(75, $("#bbcodeAnalysis").height())) + 'px';
                    // Wire click-to-jump handlers
                    dock.querySelectorAll('.bbcode-jump').forEach((a) => {
                        a.addEventListener('click', (ev) => {
                            ev.preventDefault();
                            const line = Math.max(0, parseInt(a.getAttribute('data-line') || '1', 10) - 1);
                            const ch = Math.max(0, parseInt(a.getAttribute('data-col') || '1', 10) - 1);
                            try {
                                editor.focus();
                                editor.setCursor({ line, ch });
                                editor.scrollIntoView({ line, ch }, 100);
                            } catch (_) { /* noop */ }
                        });
                    });
                }
            } catch(e) {
                // Best-effort: ignore analysis errors
            }
        }, () => {}, null);
    };
    const updatePreview = debounce(_updatePreviewImpl, 400);

    globalSyncOK = true;

    function init_post_js_2()
    {
        const editorContainer = $('#editorContainer');
        const saveBtn = document.getElementById('saveButton');

        <?php if ($pm->currentUserHasLiveCollabEditAccess()) { ?>

        window.Firebase?.INTERNAL?.forceWebSockets();
        firebaseRoot = new Firebase('https://glowing-torch-6891.firebaseio.com/pb_tip/');
        firebaseRoot.authWithCustomToken(user.firebase_token, (error, authData) => {
            if (error) { // possibly expired token, etc.
                window.onunload = window.onbeforeunload = null;
                showNotification("danger", "Shared-project session expired or invalid - it will be regenerated now",
                    "You might want to backup any unsaved changes...", null, 999999);
                setTimeout( () => {
                    ajaxAction("refreshFirebaseToken", "",
                        (text) => {
                            showNotification("success", "Collaborative edition token refreshed", "Reload the page to continue.", null, 999999);
                        },
                        (text) => {
                            showNotification("danger", "Collaborative edition token refresh failed: ", (text || "") + "Reload the page to continue.", null, 999999);
                        });
                }, 1000);
            }
        });

        const firepadRef = firebaseRoot.child(`codes/${proj.pid}/${proj.currFile.replace('.', '~')}`);

        let firepad = null;
        let firepadUserList = null;

        window.removeMyselfFromFirepad = function()
        {
            if (proj.is_multi && typeof(firepad) !== "undefined") {
                firepad.firebaseAdapter_.userRef_.remove();
            }
        };

        window.tryFirepadSync = function()
        {
            firepad.client_.updateCursor();
            firepad.client_.sendCursor(firepad.client_.cursor);
        };

        function createOrResetFirepad()
        {
            if (firepad !== null)
            {
                firepad.dispose(); firepad = null;
            }
            if (firepadUserList !== null) {
                firepadUserList.dispose(); firepadUserList = null;
            }
            firepad = Firepad.fromCodeMirror(firepadRef, editor, { userId: user.id, userColor: `#${Math.floor(Math.random()*0xFFFFFF).toString(16)}` });
            firepadUserList = FirepadUserList.fromDiv(firepadRef.child('users'), document.getElementById('userlist'), user.id, user.name, user.avatar);

            firepad.on('ready', () => {
                firepadRef.child("history").orderByKey().limitToLast(1).once('value', (s) => {
                    const revSnaps = s.val() || { foo: { t: -1 } };
                    const lastRev = revSnaps[Object.keys(revSnaps)[0]]; // first (and only)
                    const fileMTime_firepad  = (lastRev.t/1000)|0;
                    const fileMTime_tiplanet = fakeContainer.dataset.mtime|0;
                    const firepadIsOld = fileMTime_tiplanet > fileMTime_firepad;

                    if (firepadIsOld || firepad.isHistoryEmpty())
                    {
                        firepad.setText(fakeContainer.textContent);
                        lastSavedSource = fakeContainer.textContent;
                    } else {
                        lastSavedSource = editor.getValue();
                    }

                    editorContainer.css('pointer-events', 'auto');

                    proj.cursors[proj.currFile] && editor.setCursor(JSON.parse(proj.cursors[proj.currFile]));

                    const hashMatches = window.location.hash.match(/#L(\d+)/);
                    if (hashMatches && hashMatches.length > 1)
                    {
                        const lineFromHash = hashMatches.pop();
                        if (lineFromHash) {
                            editor.setCursor((+lineFromHash)-1, 0);
                        }
                    }

                    savedSinceLastChange = true;
                    lastChangeTS = (new Date).getTime();
                    document.getElementById('saveButton').disabled = true;
                });
            });

            let syncStatusTimeoutID = null;
            firepad.on('synced', (isSynced) =>
            {
                clearTimeout(syncStatusTimeoutID); // some basic debouncing...
                if (isSynced === false)
                {
                    syncStatusTimeoutID = window.setTimeout(() => {
                        globalSyncOK = false;
                        showNotification("danger", "The latest changes couldn't be synced to other users, data may get lost",
                            "Check your internet connectivity and make a local backup...", null, 999999);
                    }, 5000);
                } else {
                    globalSyncOK = true;
                }
            });
        }

        createOrResetFirepad();

        <?php } else { ?>

        const initialText = fakeContainer.value || '';
        editor.setValue(initialText);
        lastSavedSource = initialText;
        if (saveBtn) { saveBtn.disabled = true; }

        editorContainer.css('pointer-events', 'auto');

        <?php } ?>

        editor.on('change', () => {
            savedSinceLastChange = false;
            if (saveBtn) { saveBtn.disabled = false; }
            updateBBCodeInlineStyleMarks();
            updatePreview();
        });
        // Show first preview
        updateBBCodeInlineStyleMarks();
        updatePreview();
    }

    // Initialize immediately on template load
    init_post_js_1();
    init_post_js_2();

    <?php if ($pm->currentUserHasLiveCollabEditAccess() && $currProject->isChatEnabled()) { ?>

    function init_chat()
    {
        const chatRef = firebaseRoot.child(`chat/${proj.pid}`);
        let chat = null;

        chatRef.onAuth(authData => {
            if (authData)
            {
                chat = new FirechatUI(chatRef, document.getElementById('firechat-wrapper'));
                chat.setUser(user.id, user.name);
                setTimeout(() => {
                    chat._chat.getRoomList(rooms => {
                        let found = false;
                        let roomkey;
                        for (roomkey in rooms)
                        {
                            if (!rooms.hasOwnProperty(roomkey)) {
                                continue;
                            }
                            const room = rooms[roomkey];
                            if (room.name == proj.pid)
                            {
                                found = true;
                                chat._chat.enterRoom(room.id);
                                break;
                            }
                        }
                        if (!found)
                        {
                            chat._chat.createRoom(proj.pid, "public", (roomID) => {});
                        }
                    });
                }, 2000);
            }
        });
    }
    init_chat();
    <?php } ?>

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
