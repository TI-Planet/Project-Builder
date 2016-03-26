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

class native_eZ80Project extends Project
{
    private $backend;
    private $availableFiles;

    const MODULE_NAME = 'C compiler for the TI CE calculators';
    const MODULE_DESCRIPTION = 'C compiler for the TI-84 Plus CE / TI-83 Premium CE';

    public function __construct($db_id, $randKey, $authorID, $type, $name, $internalName, $multiuser, $readonly, $cTime, $uTime)
    {
        parent::__construct($db_id, $randKey, $authorID, $type, $name, $internalName, $multiuser, $readonly, $cTime, $uTime);

        $this->currentFile = 'main.c'; // default

        $this->availableFiles = array_filter(array_map('basename', glob($this->projDirectory . "*.*")), function($el) {
            return preg_match("/^[a-z0-9_]+\\.(c|h|asm)$/i", $el);
        });
        $this->availableFiles = array_unique(array_merge($this->availableFiles, ['main.c']));
        sort($this->availableFiles);
    }

    public static function cleanPrgmName($str = '') { return preg_replace('/[^A-Z0-9]/', '', strtoupper($str)); }
    public static function isFileNameOK($fileName = '') { return preg_match("/^[a-z0-9_]+\\.(c|h|asm)$/i", $fileName); }
    public static function isPrgmNameOK($fileName = '') { return preg_match("/^[A-Z][A-Z0-9]{0,7}$/", $fileName); }


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
        foreach ($this->availableFiles as $key => $file)
        {
            if ($file === $this->currentFile)
            {
                $fileListHTML .= "<li class='active tabover'><a href='#'>{$file}</a></li>";
            } else {
                $fileListHTML .= "<li><a href='#' onclick='saveFile(function() { goToFile(\"{$file}\") });'>{$file}</a></li>";
            }
        }
        return $fileListHTML;
    }

    /**
     * @return string
     */
    public function getCurrentFileSourceHTML()
    {
        $sourceFile = $this->projDirectory . $this->currentFile;
        $whichSource = file_exists($sourceFile) ? $sourceFile : (__DIR__ . '/internal/toolchain/projecttemplate/main.c');
        return htmlentities(file_get_contents($whichSource), ENT_QUOTES);
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
        if (is_string($name) && $this->isFileNameOK($name))
        {
            if (file_exists($this->projDirectory . $name))
            {
                $this->currentFile = $name;
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * @param string $prgmName
     * @return bool
     */
    public function setInternalName($prgmName)
    {
        if (is_string($prgmName) && $this->isPrgmNameOK($prgmName))
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
        // handle actions that don't need calling the internal backend but aren't global.
        require_once "internal/builder.php";
        $this->backend = new native_eZ80ProjectBackend($this, $this->projDirectory);
        return $this->backend->doUserAction($user, $params);
    }


}
