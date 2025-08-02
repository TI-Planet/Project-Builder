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

// TODO: constify projects folder somewhere up the class hierarchy

final class python_nspireProject extends Project
{
    const PROJECT_MODULE_NAME        = 'Python IDE for the TI-Nspire CX II calculators';
    const PROJECT_MODULE_DESCRIPTION = 'Python IDE for the TI-Nspire CX II calculators';

    const REGEXP_GOOD_FILE_PATTERN = "/^([a-z0-9_]+)\\.py$/i";
    const TEMPLATE_FILE            = 'script.py';

    private python_nspireProjectBackend $backend;

    private array $availableSrcFiles;

    public function __construct($db_id, $pid, UserInfo $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime)
    {
        parent::__construct($db_id, $pid, $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime);

        require_once 'Backend.php';
        $this->backend = new python_nspireProjectBackend($this, $this->projDirectory);

        $this->availableSrcFiles = $this->backend->getAvailableSrcFiles();
        if (count($this->availableSrcFiles) === 0)
        {
            // just to correctly handle things at template creation (ie, there's no directory in the FS until a first save/build)
            $this->availableSrcFiles = [ self::TEMPLATE_FILE ];
        }
        $this->currentFile = $this->availableSrcFiles[0];
    }

    public static function isPrgmNameOK($fileName = '')
    {
        return preg_match('/^[A-Z][A-Z0-9]{0,7}$/', $fileName);
    }

    public static function isFileNameOK($fileName = '')
    {
        return preg_match(self::REGEXP_GOOD_FILE_PATTERN, $fileName);
    }

    public function isCurrentFileEditable()
    {
        return true;
    }

    public function isCurrentFileRenamable()
    {
        return true;
    }

    public function isCurrentFileDeletable()
    {
        return true;
    }

    /****************************************************/
    /* Getters
    /****************************************************/

    /**
     * @return string
     */
    public function getCurrentFile()
    {
        return $this->currentFile;
    }

    /**
     * @return string
     */
    public function getIconURL()
    {
        return $this->backend->hasIconFile() ? ('/pb/projects/' . $this->getPID() . '/icon.png')
                                             : Project::PROJECT_ICON_URL_FALLBACK;
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
        $fileListHTML = '';
        $filesCount = count($this->availableSrcFiles);
        $gfxTakenCareOk = false;

        foreach ($this->availableSrcFiles as $i => $file)
        {
            // Group same header and implementation files together
            // (no margin between tabs)
            $counterpartClass = '';
            if ($i < $filesCount-1)
            {
                preg_match(self::REGEXP_GOOD_FILE_PATTERN, $file, $matches);
                [, $nameNoExtCurr, ] = $matches;
                preg_match(self::REGEXP_GOOD_FILE_PATTERN, $this->availableSrcFiles[$i +1], $matches);
                [, $nameNoExtNext, ] = $matches;
                if ($nameNoExtCurr === $nameNoExtNext) {
                    $counterpartClass = 'counterpart';
                }
            }

            if ($file === $this->currentFile)
            {
                $fileListHTML .= "<li class='active tabover {$counterpartClass}";
                if ($this->isCurrentFileRenamable())
                {
                    $fileListHTML .= " renamableFile '><a title='Click to rename' data-toggle='tooltip' data-placement='bottom' id='currentFileTab' href='#' onclick='renameFile(\"{$file}\"); return false;'>";
                } else {
                    $fileListHTML .= "'><a title='Cannot rename this file' data-toggle='tooltip' data-placement='bottom' id='currentFileTab' href='#'>";
                }
                $fileListHTML .= "<span class='filename'>{$file}</span> <span class='fileTabIconContainer'></span></a></li>";
            } else {
                $fileListHTML .= "<li class='{$counterpartClass}'><a href='#' onclick='saveFile(function() { goToFile(\"{$file}\") });'><span class='filename'>{$file}</span> <span class='fileTabIconContainer'></span></a></li>";
            }
        }
        return $fileListHTML;
    }

    /**
     * @return string
     */
    public function getCurrentFileSourceHTML()
    {
        return $this->backend->getCurrentFileSourceHTML();
    }

    /**
     * @return int
     */
    public function getCurrentFileMtime()
    {
        return $this->backend->getCurrentFileMtime();
    }


    /****************************************************/
    // Setters
    // May return a boolean to inform the caller if it went OK and to proceed accordingly (update DB etc.)
    /****************************************************/

    /**
     * @param string $name
     * @return bool
     */
    public function setCurrentFile($name)
    {
        if (is_string($name) && self::isFileNameOK($name))
        {
            if ($name === self::TEMPLATE_FILE || file_exists($this->projDirectory . 'src/' . $name))
            {
                $this->currentFile = $name;
                return true;
            }

            return false;
        }
        return false;
    }

    /**
     * Yes, description is actually the project name.
     * @param string $name
     * @return bool
     */
    public function setName($name)
    {
        if (!parent::setName($name)) {
            return false;
        }
        $newSettings = $this->backend->getSettings();
        $newSettings->description = $name;
        return $this->backend->setSettings((array)$newSettings) === PBStatus::OK;
    }

    /**
     * @param string $prgmName
     * @return bool
     */
    public function setInternalName($prgmName)
    {
        if (is_string($prgmName) && self::isPrgmNameOK($prgmName))
        {
            $this->internalName = $prgmName;
            return true;
        }
        return false;
    }


    /****************************************************/
    /* Public methods
    /****************************************************/

    public function doUserAction(UserInfo $user, array $params = [])
    {
        return $this->backend->doUserAction($user, $params);
    }

    public function getSettings()
    {
        return $this->backend->getSettings();
    }

    public function removeFromAvailableFilesList($file)
    {
        if (($key = array_search($file, $this->availableSrcFiles, true)) !== false) {
            unset($this->availableSrcFiles[$key]);
        }
    }

}
