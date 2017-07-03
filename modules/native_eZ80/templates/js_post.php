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
/** @var \ProjectBuilder\native_eZ80Project $currProject */ ?>

<script>
    /** @return {boolean} **/
    $.expr[':'].Contains = (a,i,m) => { return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0; };

    function init_post_js_1()
    {
        textarea = document.getElementById('codearea');
        fakeContainer = document.getElementById('fakeContainer');

        /* CodeMirror init */

        CodeMirror.commands.autocomplete = function (cm)
        {
            cm.showHint({hint: CodeMirror.hint.anyword});
        };

        const isAsmFile = proj.currFile.endsWith(".asm");
        editor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            styleActiveLine: true,
            matchBrackets: true,
            indentWithTabs: isAsmFile,
            indentUnit: isAsmFile ? 8 : 4,
            tabSize: isAsmFile ? 8 : 4,
            foldGutter: true,
            showTrailingSpace: true,
            mode: isAsmFile ? "text/x-ez80" : (proj.currFile.match(/\.[ch]pp$/i) ? "text/x-c++src" : "text/x-csrc"),
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
            extraKeys: {"Ctrl-Space": "autocomplete"},
            highlightSelectionMatches: {showToken: /\w/},
            theme: 'xq-light',
            readOnly: <?= (!$currUser->isModeratorOrMore() && $currProject->getAuthorID() !== $currUser->getID() && $currProject->isMultiuser() && !$currProject->isMulti_ReadWrite()) ? 'true' : 'false' ?>
        });
        savedSinceLastChange = true; lastChangeTS = (new Date).getTime();
    }
    init_post_js_1();
</script>

<script src="<?= $modulePath ?>js/cm_custom.js"></script>

<script>
    globalSyncOK = true;

    function init_post_js_2(isChangingTab)
    {
        <?php if ($currProject->isMulti_ReadWrite()) { ?>

        firebaseRoot = new Firebase('https://glowing-torch-6891.firebaseio.com/pb_tip/');
        firebaseRoot.authWithCustomToken(user.firebase_token, (error, authData) => {
            if (error) { // possibly expired token, etc.
                window.onunload = window.onbeforeunload = null;
                ajax("firebase/tokenRefresh.php", `uid=${user.id}`, () => {
                    showNotification("success", "Collaborative edition token refreshed", "You can save and reload the page");
                });
                showNotification("danger", "Shared-project session expired or invalid - it will be regenerated now",
                    "You might want to backup any unsaved changes and refresh the page...", null, 999999);
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
                if (firepad.isHistoryEmpty())
                {
                    firepad.setText(fakeContainer.textContent);
                    lastSavedSource = fakeContainer.textContent;
                } else {
                    lastSavedSource = editor.getValue();
                }
                if (isChangingTab) {
                    updateHints(true);
                } else {
                    getBuildLogAndUpdateHints();
                    getCheckLogAndUpdateHints();
                }

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

        editor.setValue(fakeContainer.textContent);
        lastSavedSource = fakeContainer.textContent;
        savedSinceLastChange = true;
        lastChangeTS = (new Date).getTime();

        <?php if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore()) { ?>
            if (isChangingTab) {
                updateHints(true);
            } else {
                getBuildLogAndUpdateHints();
                getCheckLogAndUpdateHints();
            }
        <?php } ?>

        const saveButton = document.getElementById('saveButton');
        if (saveButton) saveButton.disabled = true;

        <?php } ?>
    }
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
                            chat._chat.createRoom(proj.pid, "public", roomID => {});
                        }
                    });
                }, 2000);
            }
        });
    }
    init_chat();
    <?php } ?>

</script>