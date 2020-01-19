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

final class native_eZ80Project extends Project
{
    const PROJECT_MODULE_NAME        = 'C/C++ IDE for the TI CE calculators';
    const PROJECT_MODULE_DESCRIPTION = 'C/C++ IDE for the TI-84 Plus CE / TI-83 Premium CE. <span style="float:right"><i>Now LLVM-based!</i></span>';

    const REGEXP_GOOD_FILE_PATTERN = "/^([a-z0-9_]+)\\.(c|cpp|h|hpp|asm|inc)$/i";
    const TEMPLATE_FILE            = 'main.c';

    /**
     * @var native_eZ80ProjectBackend
     */
    private $backend;

    private $availableFiles;

    public function __construct($db_id, $randKey, UserInfo $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime)
    {
        parent::__construct($db_id, $randKey, $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime);

        require_once 'Backend.php';
        $this->backend = new native_eZ80ProjectBackend($this, $this->projDirectory);

        $this->availableFiles = $this->backend->getAvailableFiles();
        if (count($this->availableFiles) === 0)
        {
            // just to correctly handle things at template creation (ie, there's no directory in the FS until a first save/build)
            $this->availableFiles = [ self::TEMPLATE_FILE ];
        }
        $this->currentFile = $this->availableFiles[0];
    }

    public static function cleanPrgmName($prgName = '') { return preg_replace('/[^A-Z0-9]/', '', strtoupper($prgName)); }
    public static function isFileNameOK($fileName = '') { return preg_match(self::REGEXP_GOOD_FILE_PATTERN, $fileName); }
    public static function isPrgmNameOK($fileName = '') { return preg_match('/^[A-Z][A-Z0-9]{0,7}$/', $fileName); }


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
        return $this->backend->hasIconFile() ? ('/pb/projects/' . $this->getID() . '/icon.png') : '';
    }

    /**
     * @return string[]
     */
    public function getAvailableFiles()
    {
        return $this->availableFiles;
    }

    /**
     * @return string
     */
    public function getFileListHTML()
    {
        $fileListHTML = '';
        $filesCount = count($this->availableFiles);
        foreach ($this->availableFiles as $i => $file)
        {
            // Group same header and implementation files together
            // (no margin between tabs)
            $counterpartClass = '';
            if ($i < $filesCount-1)
            {
                preg_match(self::REGEXP_GOOD_FILE_PATTERN, $file, $matches);
                list(, $nameNoExtCurr, ) = $matches;
                preg_match(self::REGEXP_GOOD_FILE_PATTERN, $this->availableFiles[$i+1], $matches);
                list(, $nameNoExtNext, ) = $matches;
                if ($nameNoExtCurr === $nameNoExtNext) {
                    $counterpartClass = 'counterpart';
                }
            }

            if ($file === $this->currentFile)
            {
                $fileListHTML .= "<li class='active tabover renamableFile {$counterpartClass}'><a title='Click to rename' data-toggle='tooltip' data-placement='bottom' id='currentFileTab' href='#' onclick='renameFile(\"{$file}\"); return false;'>";
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
        if (is_string($name) && static::isFileNameOK($name))
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
        if (is_string($prgmName) && static::isPrgmNameOK($prgmName))
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
        if (($key = array_search($file, $this->availableFiles, true)) !== false) {
            unset($this->availableFiles[$key]);
        }
    }

}
