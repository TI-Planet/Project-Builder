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
/** @var \ProjectBuilder\native_eZ80Project $currProject */ ?>
<script>
    proj.currFile = '<?= $currProject->getCurrentFile(); ?>';
    proj.files = <?php echo json_encode($currProject->getAvailableFiles()); ?>;
</script>

<?php if ($pm->currentUserIsProjOwnerOrStaff() || $currProject->isMulti_ReadWrite()) { ?>
<script src="<?= cacheBusterPath('js/jquery.filedrop.js') ?>"></script>
<script>
    $(function(){
        let inviteNotif;
        let progressNotif;
        let progressNotifMsg = '';
        const progressCallback = (name) => {
            inviteNotif && inviteNotif.close();
            progressNotifMsg += `${name}, `;
            if (!progressNotif) {
                progressNotif = showNotification("info", "File import in progress...", progressNotifMsg, null, 999999);
            } else {
                progressNotif.update('message', progressNotifMsg);
            }
        };
        const endCallback = (name) => { progressCallback(name); window.location.reload(); };
        $("#editorContainer").filedrop({
            filterFunc: isValidFileName,
            onDrop: (file, str, isLast) => {
                if (file.size < 1024*1024)
                {
                    createFileWithContent(file.name, str, isLast ? endCallback : progressCallback);
                } else {
                    showNotification("warning", "A file was not created", `The file ${file.name} looks a bit too big... Max = 1 MB`, isLast ? endCallback : null, 5000);
                }
            },
            onEnter: (event) => {
                if (!inviteNotif)
                {
                    inviteNotif = showNotification("info", "File import", "Drop source code files on the editor to import them into the project");
                    inviteNotif.$ele[0].addEventListener("dragenter", () => { inviteNotif.$ele.hide(); inviteNotif.close() }, false);
                }
            }
        });
    });
</script>
<?php } else { ?>
<script>
    window.addEventListener("dragover", function(e){ e = e || event; e.preventDefault(); },false);
    window.addEventListener("drop",     function(e){ e = e || event; e.preventDefault(); },false);
</script>
<?php }?>
