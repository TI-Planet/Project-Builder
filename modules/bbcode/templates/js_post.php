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
            document.getElementById('bbcodeRenderTime').textContent = (resp && resp.renderTime) ? (resp.renderTime + 'ms') : '?';
        }, () => {}, null);
    };
    const updatePreview = debounce(_updatePreviewImpl, 400);

    globalSyncOK = true;

    function init_post_js_2()
    {
        const editorContainer = $('#editorContainer');
        const saveBtn = document.getElementById('saveButton');

        <?php if ($currProject->isMulti_ReadWrite()) { ?>

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
            updatePreview();
        });
        // Show first preview
        updatePreview();
    }

    // Initialize immediately on template load
    init_post_js_1();
    init_post_js_2();

    <?php if ($currProject->isMulti_ReadWrite() && $currProject->isChatEnabled()) { ?>

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
