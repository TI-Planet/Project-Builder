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
ini_set('display_errors', 0);

require_once 'PBStatus.php';
require_once 'ProjectFactory.php';

abstract class Project
{
    // To override
    const MODULE_NAME = 'Module Name here';
    const MODULE_DESCRIPTION = 'Module Description here';

    protected $id;
    protected $db_id;
    protected $randKey;
    protected $author;
    protected $type;
    protected $name;
    protected $internalName;
    protected $multiuser;
    protected $multi_readwrite;
    protected $chatEnabled;
    protected $createdTstamp;
    protected $updatedTstamp;

    protected $projDirectory;
    protected $currentFile;

    public function __construct($db_id, $randKey, UserInfo $author, $type, $name, $internalName, $multiuser, $wantReadWrite, $chatEnabled, $cTime, $uTime)
    {
        if (!is_int($db_id))
        {
            throw new \InvalidArgumentException("db_id isn't an int");
        }
        if (!is_string($randKey) || preg_match("/^[a-zA-Z0-9]{10}$/", $randKey) !== 1)
        {
            throw new \InvalidArgumentException('Rand key is invalid');
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
        $this->id = $author->getID() . '_' . $cTime . '_' . $randKey;
        $this->randKey = $randKey;
        $this->type = $type;
        $this->name = $name;
        $this->internalName = $internalName;
        $this->multiuser = $multiuser;
        $this->multi_readwrite = $multiuser && $wantReadWrite;
        $this->chatEnabled = $chatEnabled;
        $this->createdTstamp = $cTime;
        $this->updatedTstamp = $uTime;
        $this->projDirectory = __DIR__ . "/projects/{$this->id}/";
    }

    /****************************************************/
    /* Getters
    /****************************************************/

    /**
     * @return int
     */
    public function getDBID()
    {
        return $this->db_id;
    }

    /**
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRandKey()
    {
        return $this->randKey;
    }

    /**
     * @return int
     */
    public function getAuthorID()
    {
        return $this->author->getID();
    }

    /**
     * @return UserInfo
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getInternalName()
    {
        return $this->internalName;
    }

    /**
     * @return boolean
     */
    public function isMultiuser()
    {
        return $this->multiuser;
    }

    /**
     * @return boolean
     */
    public function isMulti_ReadWrite()
    {
        return $this->multi_readwrite;
    }

    public function isChatEnabled()
    {
        return $this->chatEnabled;
    }

    /**
     * @return int
     */
    public function getCreatedTstamp()
    {
        return $this->createdTstamp;
    }

    /**
     * @return int
     */
    public function getUpdatedTstamp()
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
        if (is_string($name))
        {
            $this->name = $name;
            return true;
        }
        return false;
    }

    abstract public function setInternalName($internalName);
    abstract public function setCurrentFile($name);

    /**
     * @param boolean $multiuser
     * @param boolean $wantReadWrite
     * @return bool
     */
    public function setMultiuser($multiuser, $wantReadWrite)
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
    public function setUpdatedTstamp($updatedTstamp)
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
        return PBStatus::Error('Nothing to see here...');
    }

}
