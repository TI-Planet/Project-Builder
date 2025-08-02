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
    protected bool $chatEnabled;
    protected int $createdTstamp;
    protected int $updatedTstamp;

    protected string $projDirectory;
    protected string $currentFile;

    // This is protected since only children classes extending it will call it.
    protected function __construct($db_id, $pid, UserInfo $author, $type, $name, $internalName, $multiuser, $wantReadWrite, $chatEnabled, $cTime, $uTime)
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
        $this->multiuser = $multiuser;
        $this->multi_readwrite = $multiuser && $wantReadWrite;
        $this->chatEnabled = $chatEnabled;
        $this->createdTstamp = $cTime;
        $this->updatedTstamp = $uTime;
        $this->projDirectory = __DIR__ . "/../../pbprojects/{$this->pid}/";
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
    abstract public function getIconURL();

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

    /**
     * @param mixed $name
     * @return bool
     */
    public function setName($name)
    {
        if (is_string($name) && strlen($name) <= 25 && preg_match('~^[\w ._+\-*/<>,:()]{0,25}$~', $name) === 1)
        {
            $this->name = $name;
            return true;
        }
        return false;
    }

    abstract public function setInternalName($internalName);
    abstract public function setCurrentFile($name);
    abstract public function getCurrentFile();
    abstract public static function isFileNameOK($fileName = '');
    abstract public function isCurrentFileEditable();
    abstract public function isCurrentFileRenamable();
    abstract public function isCurrentFileDeletable();

    public function canUserEditCurrentFile(UserInfo $user)
    {
        return $this->isCurrentFileEditable() && ($user->isModeratorOrMore() || $this->getAuthorID() === $user->getID() || ($this->isMultiuser() && $this->isMulti_ReadWrite()));
    }

    /**
     * @param boolean $multiuser
     * @param boolean $wantReadWrite
     * @return bool
     */
    final public function setMultiuser($multiuser, $wantReadWrite)
    {
        if (is_bool($multiuser) && is_bool($wantReadWrite))
        {
            $this->multiuser = $multiuser;
            $this->multi_readwrite = $multiuser && $wantReadWrite;
            return true;
        }
        return false;
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

    abstract public function doUserAction(UserInfo $user, array $params = []);

    abstract public function getSettings();

}
