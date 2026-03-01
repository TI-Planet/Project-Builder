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

namespace ProjectBuilder;

final class bbcodeProject extends Project
{
    const PROJECT_MODULE_NAME        = 'BBCode content editor';
    const PROJECT_MODULE_DESCRIPTION = 'TI-Planet BBCode editor with live preview';

    const REGEXP_GOOD_FILE_PATTERN = "/^([A-Za-z0-9_\-]{1,25})\\.bbcode$/";
    const TEMPLATE_FILE            = 'Article.bbcode';

    private bbcodeProjectBackend $backend;

    private array $availableSrcFiles;

    public function __construct($db_id, $pid, UserInfo $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime, $isReadWriteCustom = false, array $readWriteAllowedUserIDs = [])
    {
        parent::__construct($db_id, $pid, $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime, $isReadWriteCustom, $readWriteAllowedUserIDs);

        require_once 'Backend.php';
        $this->backend = new bbcodeProjectBackend($this, $this->projDirectory);

        $this->availableSrcFiles = $this->backend->getAvailableSrcFiles();
        if (count($this->availableSrcFiles) === 0)
        {
            // just to correctly handle things at template creation (no directory yet until first save)
            $this->availableSrcFiles = [ self::TEMPLATE_FILE ];
        }
        $this->currentFile = $this->availableSrcFiles[0];
    }

    public static function isFileNameOK($fileName = '')
    {
        return preg_match(self::REGEXP_GOOD_FILE_PATTERN, $fileName);
    }

    public function isCurrentFileEditable() { return true; }
    public function isCurrentFileRenamable() { return true; }
    public function isCurrentFileDeletable() { return true; }

    public function getCurrentFile()
    {
        return $this->currentFile;
    }

    public function getIconURL()
    {
        return Project::PROJECT_ICON_URL_FALLBACK;
    }

    /**
     * @return string[]
     */
    public function getAvailableSrcFiles()
    {
        return $this->availableSrcFiles;
    }

    /**
     * @return string
     */
    public function getFileListHTML()
    {
        return '';
    }

    public function doUserAction(UserInfo $user, array $params = [])
    {
        return $this->backend->doUserAction($user, $params);
    }

    public function getCurrentFileSourceHTML()
    {
        return $this->backend->getCurrentFileSourceHTML();
    }

    public function getCurrentFileMtime()
    {
        return $this->backend->getCurrentFileMtime();
    }

    /****************************************************/
    // Setters
    /****************************************************/

    public function setInternalName($internalName)
    {
        if (is_string($internalName) && strlen($internalName) <= 25 && preg_match('~^[\w ._+\-*/<>,:()]{0,25}$~', $internalName) === 1)
        {
            $this->internalName = $internalName;
            return true;
        }
        return false;
    }

    public function setCurrentFile($name)
    {
        if (is_string($name) && self::isFileNameOK($name))
        {
            if ($name === self::TEMPLATE_FILE || file_exists($this->projDirectory . 'src/' . $name))
            {
                $this->currentFile = $name;
                return true;
            }
        }
        return false;
    }

    public function getSettings()
    {
        return $this->backend->getSettings();
    }
}
