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
    die("Ahem ahem");
}
/** @var \ProjectBuilder\native_eZ80Project $currProject */ ?>

<script>
    function init_post_js_1()
    {
        textarea = document.getElementById('codearea');
        fakeContainer = document.getElementById('fakeContainer');

        /* CodeMirror init */

        CodeMirror.commands.autocomplete = function (cm)
        {
            cm.showHint({hint: CodeMirror.hint.anyword});
        };

        editor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            styleActiveLine: true,
            matchBrackets: true,
            indentUnit: 4,
            foldGutter: true,
            showTrailingSpace: true,
            mode: "text/x-csrc",
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
            extraKeys: {"Ctrl-Space": "autocomplete"},
            highlightSelectionMatches: {showToken: /\w/},
            theme: 'xq-light'
        });
        savedSinceLastChange = true;
    }
    init_post_js_1();
</script>

<script src="<?= $modulePath ?>js/cm_custom.js"></script>

<script>
    function init_post_js_2(isChangingTab)
    {
        <?php if ($currProject->isMulti_ReadWrite()) { ?>

        firebaseRoot = new Firebase('https://glowing-torch-6891.firebaseio.com/pb_tip/');
        firebaseRoot.authWithCustomToken(user.firebase_token, (error, authData) => {
            if (error) { // possibly expired token, etc.
                saveFile(() => { ajax("firebase/tokenRefresh.php", `uid=${user.id}`, () => { window.location.reload(); }); });
            }
        });

        const firepadRef = firebaseRoot.child(`codes/${proj.pid}/${proj.currFile.replace('.', '~')}`);

        let firepad = null;
        let firepadUserList = null;
        function createOrResetFirepad()
        {
            if (firepad !== null)
            {
                firepad.dispose(); firepad = null;
                firepadUserList.dispose(); firepadUserList = null;
            }
            firepad = Firepad.fromCodeMirror(firepadRef, editor, { userId: user.id, userColor: `#${Math.floor(Math.random()*0xFFFFFF).toString(16)}` });
            firepadUserList = FirepadUserList.fromDiv(firepadRef.child('users'), document.getElementById('userlist'), user.id, user.name, user.avatar);

            firepad.on('ready', () => {
                if (localStorage.getItem("invalidateFirebaseContent") === "true")
                {
                    editor.setValue("");
                    firepadRef.parent().remove();
                    localStorage.removeItem("invalidateFirebaseContent");
                    createOrResetFirepad();
                    return;
                }
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
                savedSinceLastChange = true;
                document.getElementById('saveButton').disabled = true;
            });
        }

        createOrResetFirepad();

        <?php } else { ?>

        editor.setValue(fakeContainer.textContent);
        lastSavedSource = fakeContainer.textContent;
        savedSinceLastChange = true;

        if (isChangingTab) {
            updateHints(true);
        } else {
            getBuildLogAndUpdateHints();
            getCheckLogAndUpdateHints();
        }

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
                        for (const roomkey in rooms)
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