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

// For prod. TODO: Enable for admins
error_reporting(0);
ini_set('display_errors', 'Off');

require_once 'PBStatus.class.php';
require_once 'ProjectFactory.php';
require_once 'modules/IBackend.class.php';

abstract class Project
{
    // To override
    const PROJECT_MODULE_NAME        = "Project's Module name here";
    const PROJECT_MODULE_DESCRIPTION = "Project's Module description here";

    const PROJECT_ICON_URL_FALLBACK  = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';

    protected string $pid;
    protected int $db_id;
    protected UserInfo $author;
    protected string $type;
    protected string $name;
    protected string $internalName;
    protected bool $multiuser;
    protected bool $multi_readwrite;
    protected bool $multi_readwrite_custom;
    protected array $multi_readwrite_allowed_userids;
    protected bool $chatEnabled;
    protected int $createdTstamp;
    protected int $updatedTstamp;

    protected string $projDirectory;
    protected string $currentFile;
    protected IBackend $backend;
    protected array $availableSrcFiles = [];

    // This is protected since only children classes extending it will call it.
    protected function __construct($db_id, $pid, UserInfo $author, $type, $name, $internalName, $multiuser, $wantReadWrite, $chatEnabled, $cTime, $uTime, $isReadWriteCustom = false, array $readWriteAllowedUserIDs = [])
    {
        if (!is_int($db_id))
        {
            throw new \InvalidArgumentException("db_id isn't an int");
        }
        if (!is_string($pid) || empty($pid))
        {
            throw new \InvalidArgumentException('pid is invalid');
        }
        if ($author === null)
        {
            throw new \InvalidArgumentException("Author can't be null");
        }
        if (!in_array($type, ProjectFactory::$projectTypes, true))
        {
            throw new \InvalidArgumentException('Project type must be one of ' . json_encode(ProjectFactory::$projectTypes));
        }
        if (!is_bool($multiuser))
        {
            throw new \InvalidArgumentException('multiuser must be a boolean');
        }
        if (!is_bool($wantReadWrite))
        {
            throw new \InvalidArgumentException('wantReadWrite must be a boolean');
        }
        if (!is_bool($chatEnabled))
        {
            throw new \InvalidArgumentException('chatEnabled must be a boolean');
        }
        if (!is_bool($isReadWriteCustom))
        {
            throw new \InvalidArgumentException('isReadWriteCustom must be a boolean');
        }
        if (!is_int($cTime) || strlen((string)$cTime) !== 10)
        {
            throw new \InvalidArgumentException('Creation timestamp must be an unix timestamp (10 digits unsigned int)');
        }
        if (!is_int($uTime) || strlen((string)$uTime) !== 10)
        {
            throw new \InvalidArgumentException('Update timestamp must be an unix timestamp (10 digits unsigned int)');
        }

        $this->db_id = $db_id;
        $this->author = $author;
        $this->pid = $pid;
        $this->type = $type;
        $this->name = $name;
        $this->internalName = $internalName;
        $this->multiuser = false;
        $this->multi_readwrite = false;
        $this->multi_readwrite_custom = false;
        $this->multi_readwrite_allowed_userids = [];
        $this->setMultiuser($multiuser, $wantReadWrite, $isReadWriteCustom);
        $this->setMultiReadWriteAllowedUserIDs($readWriteAllowedUserIDs);
        $this->chatEnabled = $chatEnabled;
        $this->createdTstamp = $cTime;
        $this->updatedTstamp = $uTime;
        $this->projDirectory = __DIR__ . "/../../pbprojects/{$this->pid}/";
    }

    final protected function initProjectBackend(string $backendPath, string $backendClass)
    {
        require_once $backendPath;
        $backend = new $backendClass($this, $this->projDirectory);
        $this->initFileBasedProject($backend);
        return $backend;
    }

    /****************************************************/
    /* Getters
    /****************************************************/

    /**
     * @return int
     */
    final public function getDBID()
    {
        return $this->db_id;
    }

    /**
     * @return string
     */
    final public function getPID()
    {
        return $this->pid;
    }

    /**
     * @return int
     */
    final public function getAuthorID()
    {
        return $this->author->getID();
    }

    /**
     * @return UserInfo
     */
    final public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return string
     */
    final public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    final public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    final public function getInternalName()
    {
        return $this->internalName;
    }

    /**
     * @return string
     */
    final public function getIconURL()
    {
        return IBackend::getWebPublicIconURL($this->pid);
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

    /**
     * @return string
     */
    public function getCurrentFileSourceHash()
    {
        return $this->backend->getCurrentFileSourceHash();
    }

    /**
     * @return boolean
     */
    final public function isMultiuser()
    {
        return $this->multiuser;
    }

    /**
     * @return boolean
     */
    final public function isMulti_ReadWrite()
    {
        return $this->multi_readwrite;
    }

    final public function isMulti_ReadWrite_CustomRestricted()
    {
        return $this->multi_readwrite && $this->multi_readwrite_custom;
    }

    final public function getMulti_ReadWriteAllowedUserIDs()
    {
        return $this->multi_readwrite_allowed_userids;
    }

    final public function isChatEnabled()
    {
        return $this->chatEnabled;
    }

    /**
     * @return int
     */
    final public function getCreatedTstamp()
    {
        return $this->createdTstamp;
    }

    /**
     * @return int
     */
    final public function getUpdatedTstamp()
    {
        return $this->updatedTstamp;
    }

    /****************************************************/
    // Setters
    // May return a boolean to inform the caller if it went OK and to proceed accordingly (update DB etc.)
    /****************************************************/

    public function setName(string $name)
    {
        if (strlen($name) > 25 || preg_match('~^[\w ._+\-*/<>,:()]{0,25}$~', $name) !== 1)
        {
            return false;
        }
        $this->name = $name;
        $newSettings = $this->backend->getSettings();
        $newSettings->description = $name; // Yes, description is actually the project name.
        return $this->backend->setSettings((array)$newSettings) === PBStatus::OK;
    }

    // $internalName = program name, really
    public function setInternalName(string $internalName)
    {
        if (preg_match('/^[A-Z][A-Z0-9]{0,7}$/', $internalName) === 1)
        {
            $this->internalName = $internalName;
            return true;
        }
        return false;
    }

    public static function isFileNameOK($fileName = '')
    {
        return preg_match(static::REGEXP_GOOD_FILE_PATTERN, $fileName);
    }

    final protected function initFileBasedProject(IBackend $backend)
    {
        $this->backend = $backend;
        $this->availableSrcFiles = $this->backend->getAvailableSrcFiles();
        if (count($this->availableSrcFiles) === 0)
        {
            // just to correctly handle things at template creation (ie, there's no directory in the FS until a first save/build)
            $this->availableSrcFiles = [ static::TEMPLATE_FILE ];
        }
        $this->currentFile = $this->availableSrcFiles[0];
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

    public function setCurrentFile($name)
    {
        if (is_string($name) && static::isFileNameOK($name))
        {
            if ($name === static::TEMPLATE_FILE || file_exists($this->projDirectory . 'src/' . $name))
            {
                $this->currentFile = $name;
                return true;
            }

            return false;
        }

        return false;
    }

    public function getCurrentFile()
    {
        return $this->currentFile;
    }

    /**
     * @return string[]
     */
    public function getAvailableSrcFiles()
    {
        return $this->availableSrcFiles;
    }

    public function getFileListHTML($allowRename = true)
    {
        $fileListHTML = '';
        $filesCount = count($this->availableSrcFiles);

        foreach ($this->availableSrcFiles as $i => $file)
        {
            // Group same header and implementation files together
            // (no margin between tabs)
            $counterpartClass = '';
            if ($i < $filesCount - 1)
            {
                preg_match(static::REGEXP_GOOD_FILE_PATTERN, $file, $matches);
                [, $nameNoExtCurr, ] = $matches;
                preg_match(static::REGEXP_GOOD_FILE_PATTERN, $this->availableSrcFiles[$i + 1], $matches);
                [, $nameNoExtNext, ] = $matches;
                if ($nameNoExtCurr === $nameNoExtNext) {
                    $counterpartClass = 'counterpart';
                }
            }

            if ($file === $this->currentFile)
            {
                $fileListHTML .= "<li class='active tabover {$counterpartClass}";
                if ($allowRename && $this->isCurrentFileRenamable())
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

    public function removeFromAvailableFilesList($file)
    {
        if (($key = array_search($file, $this->availableSrcFiles, true)) !== false) {
            unset($this->availableSrcFiles[$key]);
        }
    }

    public function canUserEditCurrentFile(UserInfo $user)
    {
        return $this->isCurrentFileEditable() && $this->canUserWriteProject($user);
    }

    public function canUserWriteProject(UserInfo $user)
    {
        if ($user->isAnonymous())
        {
            return false;
        }
        if ($user->isModeratorOrMore() || $this->getAuthorID() === $user->getID())
        {
            return true;
        }
        if (!($this->isMultiuser() && $this->isMulti_ReadWrite()))
        {
            return false;
        }
        if (!$this->isMulti_ReadWrite_CustomRestricted())
        {
            return true;
        }

        return in_array($user->getID(), $this->multi_readwrite_allowed_userids, true);
    }

    final public function setMultiReadWriteAllowedUserIDs(array $userIDs)
    {
        $tmp = [];
        foreach ($userIDs as $userID)
        {
            if (is_int($userID) || (is_string($userID) && preg_match('/^\d+$/', $userID) === 1))
            {
                $uid = (int)$userID;
                if ($uid > 1) {
                    $tmp[$uid] = true;
                }
            }
        }
        ksort($tmp, SORT_NUMERIC);
        $this->multi_readwrite_allowed_userids = array_map('intval', array_keys($tmp));
    }

    final public function setMultiuser(bool $multiuser, bool $wantReadWrite, bool $isReadWriteCustom)
    {
        $this->multiuser = $multiuser;
        $this->multi_readwrite = $multiuser && $wantReadWrite;
        $this->multi_readwrite_custom = $this->multi_readwrite && $isReadWriteCustom;
        if (!$this->multi_readwrite_custom)
        {
            $this->multi_readwrite_allowed_userids = [];
        }
    }

    /**
     * @param int $updatedTstamp
     * @return bool
     */
    final public function setUpdatedTstamp($updatedTstamp)
    {
        if (is_int($updatedTstamp) && strlen((string)$updatedTstamp) === 10)
        {
            $this->updatedTstamp = $updatedTstamp;
            return true;
        }
        return false;
    }

    /****************************************************/
    /* Public methods
    /****************************************************/
    // To override, especially for backend-powered projects

    public function doUserAction(UserInfo $user, array $params = [])
    {
        return $this->backend->doUserAction($user, $params);
    }

    public function getSettings()
    {
        return $this->backend->getSettings();
    }

}
