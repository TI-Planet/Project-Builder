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

$currProjectSettings = $currProject->getSettings();
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

                        <!-- todo: to put in a distinct modal, global to the PB, not CE C -->
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
                                        <input type="radio" name="sharingMode" value="publicRO" <?= $currProject->isMultiuser() ? 'checked' : '' ?>>
                                        <b>Public read-only</b> - Anyone can read project files, but cannot edit them
                                    </label>
                                </div>
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="sharingMode" value="publicRW" <?= $currProject->isMulti_ReadWrite() ? 'checked' : '' ?>>
                                        <b>Public read+write</b> - Anyone can read and edit project files
                                    </label>
                                </div>
                                <div class="radio disabled text-muted" title="Not available yet ; soon!">
                                    <label>
                                        <input type="radio" name="sharingMode" value="custom" disabled>
                                        <b>Custom...</b> - Choose exactly who can read/write or not
                                    </label>
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

                        <h4>Output</h4>
                        <div class="form-group">
                            <label class="col-sm-1 control-label"></label>
                            <div class="col-sm-11">
                                Format:
                                <div class="radio-inline">
                                    <label>
                                        <input type="radio" name="outputFormat" value="program" <?= $currProjectSettings->outputFormat === 'program' ? 'checked' : '' ?>>
                                        <b>Program</b> (.8xp)
                                    </label>
                                </div>
                                <div class="radio-inline">
                                    <label>
                                        <input type="radio" name="outputFormat" value="appvar" <?= $currProjectSettings->outputFormat === 'appvar' ? 'checked' : '' ?>>
                                        <b>Appvar</b> (.8xv)
                                    </label>
                                </div>
                                <br/>

                                Location:
                                <div class="radio-inline">
                                    <label>
                                        <input type="radio" name="outputLoc" value="ram" <?= $currProjectSettings->outputLoc === 'ram' ? 'checked' : '' ?>>
                                        <b>RAM</b>
                                    </label>
                                </div>
                                <div class="radio-inline">
                                    <label>
                                        <input type="radio" name="outputLoc" value="archive" <?= $currProjectSettings->outputLoc === 'archive' ? 'checked' : '' ?>>
                                        <b>Archive</b>
                                    </label>
                                </div>
                                <br/>
                            </div>
                        </div>

                        <h4>Compilation optimization</h4>
                        <div class="form-group">
                            <label class="col-sm-1 control-label"></label>
                            <div class="col-sm-11">
                                Optimize for...
                                <div class="radio-inline">
                                    <label>
                                        <input type="radio" name="optFor" value="speed" <?= $currProjectSettings->optFor === 'speed' ? 'checked' : '' ?>>
                                        <b>Speed</b> (faster code)
                                    </label>
                                </div>
                                <div class="radio-inline">
                                    <label>
                                        <input type="radio" name="optFor" value="size" <?= $currProjectSettings->optFor === 'size' ? 'checked' : '' ?>>
                                        <b>Size</b> (smaller code)
                                    </label>
                                </div>
                                <br/>

                                <i>(Advanced)</i> Use Flash functions:
                                <div class="radio-inline">
                                    <label>
                                        <input type="radio" name="flashFuncs" value="YES" <?= $currProjectSettings->flashFuncs === 'YES' ? 'checked' : '' ?>>
                                        <b>Yes</b> (linked)
                                    </label>
                                </div>
                                <div class="radio-inline">
                                    <label>
                                        <input type="radio" name="flashFuncs" value="NO" <?= $currProjectSettings->flashFuncs === 'NO' ? 'checked' : '' ?>>
                                        <b>No</b> (static)
                                    </label>
                                </div>
                                <br/>
                            </div>
                        </div>

                        <h4>Tools options</h4>
                        <div class="form-group">
                            <label class="col-sm-1 control-label"></label>
                            <div class="col-sm-11">
                                Clang extra args
                                <div class="radio-inline" style="width: 400px;">
                                    <label style="width: 100%;">
                                        <input class="form-control" type="text" pattern="^(?:(?:-(?:[wWDO]|std))[\w=+-]* *)*$" name="clangArgs" value=<?= json_encode((string)$currProjectSettings->clangArgs) ?>>
                                    </label>
                                </div>
                                <br/>
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <span class="pull-left" id="configModalStatus"></span>

                    <button type="button" onclick="sendSettings();" class="btn btn-primary">Save and close</button>
                </div>
            </div>
        </div>
    </div>
