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

/** @var \ProjectBuilder\python_eZ80Project $currProject */ ?>

<script>
    /** @return {boolean} **/
    $.expr[':'].Contains = (a,i,m) => { return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0; };

    function init_post_js_1()
    {
        destroyPythonMenuEditor();
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
            mode: isPythonMenuFile() ? 'text/plain' : 'text/x-python',
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
            extraKeys: {"Ctrl-Space": "autocomplete", 'Ctrl-/': toggleComment, 'Cmd-/': toggleComment },
            highlightSelectionMatches: {showToken: /\w/},
            theme: 'xq-light',
            readOnly: <?= $currProject->canUserEditCurrentFile($currUser) ? 'false' : 'true' ?>
        });
        savedSinceLastChange = true; lastChangeTS = (new Date).getTime();
        initPythonMenuEditor();
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
        var collabBindings = null;
        var collabSessionBlockedByAuth = false;
        firebaseRoot.authWithCustomToken(user.firebase_token, (error, authData) => {
            if (!isActiveRuntimeSession()) {
                return;
            }
            if (error) { // possibly expired token, etc.
                collabSessionBlockedByAuth = true;
                collabBindings && collabBindings.setSessionBlocked(true);
                globalSyncOK = false;
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
        collabBindings = createCollaborativeFirepadBindings({
            firepadRef: firepadRef,
            editor: editor,
            user: user,
            userListElement: document.getElementById('userlist'),
            isActiveRuntimeSession: isActiveRuntimeSession,
            getServerSource: () => fakeContainer.value || fakeContainer.textContent || '',
            onHealthy: () => { globalSyncOK = true; },
            onStalled: (title, message) => {
                globalSyncOK = false;
                showNotification("danger", title, message, null, 999999);
            },
            onReady: ({ serverSource, liveSource, usedServerSource }) => {
                lastSavedSource = usedServerSource ? serverSource : liveSource;

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
            }
        });
        const createOrResetFirepad = registerCollaborativeFirepadGlobals({
            getBindings: () => collabBindings,
            isCollaborative: () => proj.is_multi,
            isSessionBlocked: () => collabSessionBlockedByAuth
        });

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
        if (!isPythonMenuFile() && (!window.sdk_ctags || window.sdk_ctags.length === 0) && editor.getMode().name !== 'yaml')
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
