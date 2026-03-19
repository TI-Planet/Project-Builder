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
if (!isset($pm))
{
    die('Ahem ahem');
}

require_once 'utils.php';

/** @var \ProjectBuilder\python_nspireProject $currProject */ ?>

<script>
    /** @return {boolean} **/
    $.expr[':'].Contains = (a,i,m) => { return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0; };

    function init_post_js_1()
    {
        textarea = document.getElementById('codearea');
        fakeContainer = document.getElementById('fakeContainer');

        /* CodeMirror init */
        CodeMirror.commands.autocomplete = function(cm) { cm.showHint({ hint: CodeMirror.hint.any_and_ctags }); };

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
            mode: 'text/x-python',
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
            extraKeys: {"Ctrl-Space": "autocomplete", 'Ctrl-/': toggleComment, 'Cmd-/': toggleComment },
            highlightSelectionMatches: {showToken: /\w/},
            theme: 'xq-light',
            readOnly: <?= $currProject->canUserEditCurrentFile($currUser) ? 'false' : 'true' ?>
        });
        savedSinceLastChange = true; lastChangeTS = (new Date).getTime();
    }
    init_post_js_1();
</script>

<script src="<?= cacheBusterPath("{$modulePath}js/cm_custom.js") ?>"></script>

<script>
    globalSyncOK = true;

    function init_post_js_2(isChangingTab, cbDone)
    {
        const editorContainer = $('#editorContainer');
        const editorRuntimeSession = beginEditorRuntimeSession();
        const isActiveRuntimeSession = () => isCurrentEditorRuntimeSession(editorRuntimeSession);

        <?php if ($pm->currentUserHasLiveCollabEditAccess()) { ?>

        window.Firebase?.INTERNAL?.forceWebSockets();
        firebaseRoot = new Firebase('https://glowing-torch-6891.firebaseio.com/pb_tip/');
        firebaseRoot.authWithCustomToken(user.firebase_token, (error, authData) => {
            if (!isActiveRuntimeSession()) {
                return;
            }
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

        function disposeFirepadBindings()
        {
            if (firepadUserList !== null) {
                firepadUserList.dispose();
                firepadUserList = null;
            }
            if (firepad !== null) {
                firepad.dispose();
                firepad = null;
            }
        }
        registerRealtimeEditorCleanup(disposeFirepadBindings);

        window.removeMyselfFromFirepad = function()
        {
            if (proj.is_multi) {
                disposeFirepadBindings();
            }
        };

        window.tryFirepadSync = function()
        {
            if (!isActiveRuntimeSession() || firepad === null) {
                return;
            }
            firepad.client_.updateCursor();
            firepad.client_.sendCursor(firepad.client_.cursor);
        };

        function createOrResetFirepad()
        {
            disposeFirepadBindings();
            firepad = Firepad.fromCodeMirror(firepadRef, editor, { userId: user.id, userColor: `#${Math.floor(Math.random()*0xFFFFFF).toString(16)}` });
            firepadUserList = FirepadUserList.fromDiv(firepadRef.child('users'), document.getElementById('userlist'), user.id, user.name, user.avatar);

            firepad.on('ready', () => {
              if (!isActiveRuntimeSession()) { return; }
              firepadRef.child("history").orderByKey().limitToLast(1).once('value', (s) => {
                if (!isActiveRuntimeSession()) { return; }
                const revSnaps = s.val() || { foo: { t: -1 } };
                const lastRev = revSnaps[Object.keys(revSnaps)[0]]; // first (and only)
                const fileMTime_firepad  = (lastRev.t/1000)|0;
                const fileMTime_tiplanet = fakeContainer.dataset.mtime|0;
                const serverSource = fakeContainer.value || fakeContainer.textContent || '';
                const liveSource = editor.getValue();
                const firepadIsOlderThanServer = fileMTime_tiplanet > fileMTime_firepad;

                if (firepad.isHistoryEmpty())
                {
                    firepad.setText(serverSource);
                    lastSavedSource = serverSource;
                } else if (liveSource === serverSource) {
                    lastSavedSource = liveSource;
                } else if (firepadIsOlderThanServer) {
                    globalSyncOK = false;
                    lastSavedSource = liveSource;
                    showNotification("warning", "Shared copy diverged",
                        "The live collaborative copy differs from the saved server copy. To avoid overwriting content, automatic server-to-live replacement was skipped. Reload and merge before saving.", null, 999999);
                } else {
                    lastSavedSource = liveSource;
                }

                if (typeof(cbDone) === "function") { cbDone(); }
                editorContainer.css('pointer-events', 'auto');

                getAnalysisLogAndUpdateHintsMaybe(true);

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
                if (!isActiveRuntimeSession()) { return; }
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

        editor.setValue(fakeContainer.textContent);
        lastSavedSource = fakeContainer.textContent;
        savedSinceLastChange = true;
        lastChangeTS = (new Date).getTime();

        if (typeof(cbDone) === "function") { cbDone(); }
        editorContainer.css('pointer-events', 'auto');

        <?php if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore()) { ?>
            getAnalysisLogAndUpdateHintsMaybe(true);
        <?php } ?>

        const saveButton = document.getElementById('saveButton');
        if (saveButton) saveButton.disabled = true;

        <?php } ?>

        <?php if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore()) { ?>
        if ((!window.sdk_ctags || window.sdk_ctags.length === 0) && editor.getMode().name !== 'yaml')
        {
            getSDKCtags();
        }
        <?php } ?>
    }
    init_post_js_2(false);

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

</script>