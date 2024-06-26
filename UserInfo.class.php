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

class UserInfo
{
    protected int $id;   // Unique user id
    protected string $sid;  // Some sort of session id (will be used as a CSRF token)
    protected string $name;
    protected string $avatarURL;
    protected bool $isBot;
    protected bool $isAnonymous;
    protected bool $isModeratorOrMore;

    public function __construct($id, $sid, $name, $avatarURL = '', $isBot = false, $isAnonymous = true, $isModeratorOrMore = false)
    {
        if (!is_int($id) || $id < 0)
        {
            die('User ID must be an unsigned integer');
        }
        if (!is_string($sid) || empty($sid))
        {
            die('Session ID must be a string');
        }
        if (!is_string($name))
        {
            die('User name must be a string');
        }

        $this->id = $id;
        $this->sid = $sid;
        $this->name = $name;
        $this->avatarURL = (string)$avatarURL;
        $this->isBot = (bool)$isBot;
        $this->isAnonymous = (bool)$isAnonymous;
        $this->isModeratorOrMore = (bool)$isModeratorOrMore;
    }

    public function getID() { return $this->id; }
    public function getSID() { return $this->sid; }
    public function getName() { return $this->name; }
    public function getAvatarURL() { return $this->avatarURL; }
    public function isBot() { return $this->isBot; }
    public function isAnonymous() { return $this->isAnonymous; }
    public function isModeratorOrMore() { return $this->isModeratorOrMore; }

    /*
     *  OPEN-SOURCE USERS: TODO HERE:
     *
     *  If you wish tu support multi-user live collaboration...
     *
     *  Implement your own logic for the function
     *  getOrGenerateFirebaseTokenForUID(int $userID, bool $forceRefresh)
     *  which returns the Firebase token as a string, or null on failure.
     *
     *  Here is a useful resource: https://github.com/firebase/firebase-token-generator-php
     *
     *  Your function will have to get/insert/replace the pb_tokens table in the database.
     */
    public function getOrGenerateFirebaseToken($forceRefresh = false)
    {
        require_once 'firebase/firebase.php';
        return getOrGenerateFirebaseTokenForUID($this->id, $forceRefresh);
    }

}
