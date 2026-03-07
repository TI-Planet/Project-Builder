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
?>
    <div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="mySettingsModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="mySettingsModalLabel">Project settings</h4>
                </div>
                <div class="modal-body" id="modalSettingsBody">
                    <form class="form-horizontal" id="settingsForm" onsubmit="return false;">
                        <h4>Sharing mode</h4>
                        <div class="form-group">
                            <label class="col-sm-1 control-label"></label>
                            <div class="col-sm-11">
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="sharingMode" value="private" <?= $currProject->isMultiuser() ? '' : 'checked' ?>>
                                        <b>Private</b> - Only you have read+write access to the project
                                    </label>
                                </div>
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="sharingMode" value="publicRO" <?= ($currProject->isMultiuser() && !$currProject->isMulti_ReadWrite()) ? 'checked' : '' ?>>
                                        <b>Public read-only</b> - Anyone can read project files, but cannot edit them
                                    </label>
                                </div>
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="sharingMode" value="publicRW" <?= ($currProject->isMulti_ReadWrite() && !$currProject->isMulti_ReadWrite_CustomRestricted()) ? 'checked' : '' ?>>
                                        <b>Public read+write</b> - Anyone can read and edit project files
                                    </label>
                                </div>
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="sharingMode" value="custom" <?= $currProject->isMulti_ReadWrite_CustomRestricted() ? 'checked' : '' ?>>
                                        <b>Custom read+write</b> - Only listed TI-Planet users can edit (project remains readable-only to others with the link)
                                    </label>
                                </div>
                                <div id="customRWAllowedUsersBlock" style="margin-left: 20px; display: <?= $currProject->isMulti_ReadWrite_CustomRestricted() ? 'block' : 'none' ?>;">
                                    Allowed TI-Planet user IDs (comma, space or newline separated):
                                    <div class="radio-inline" style="width: 100%; padding-left: 0;">
                                        <label style="width: 100%;">
                                            <textarea class="form-control" rows="2" name="allowedRWUserIDs" id="allowedRWUserIDs" placeholder="1234, 5678"><?= htmlentities(implode(', ', $currProject->getMulti_ReadWriteAllowedUserIDs()), ENT_QUOTES) ?></textarea>
                                        </label>
                                    </div>
                                </div>

                                Chat enabled (when shared):
                                <div class="radio-inline">
                                    <label>
                                        <input type="radio" name="chatEnabled" value="0" <?= $currProject->isChatEnabled() ? '' : 'checked' ?>>
                                        <b>No</b>
                                    </label>
                                </div>
                                <div class="radio-inline">
                                    <label>
                                        <input type="radio" name="chatEnabled" value="1" <?= $currProject->isChatEnabled() ? 'checked' : '' ?>>
                                        <b>Yes</b>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="sendSettings();" class="btn btn-primary">Save and close</button>
                </div>
            </div>
        </div>
    </div>
