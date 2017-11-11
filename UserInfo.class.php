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
    protected $id;   // Unique user id
    protected $sid;  // Some sort of session id (will be used as a CSRF token)
    protected $name;
    protected $avatarURL;
    protected $isBot;
    protected $isAnonymous;
    protected $isModeratorOrMore;

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

}
