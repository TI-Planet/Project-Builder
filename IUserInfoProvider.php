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
    protected $id;
    protected $name;
    protected $avatarURL;
    protected $isAnonymous;
    protected $isModeratorOrMore;

    public function __construct($id, $name, $avatarURL = '', $isAnonymous = true, $isModeratorOrMore = false)
    {
        if (!isset($id) || !is_int($id) || $id < 0)
        {
            die("User ID must be an unsigned integer");
        }
        if (!isset($name) || !is_string($name))
        {
            die("User name must be a string");
        }

        $this->id = $id;
        $this->name = $name;
        $this->avatarURL = (string)$avatarURL;
        $this->isAnonymous = (bool)$isAnonymous;
        $this->isModeratorOrMore = (bool)$isModeratorOrMore;
    }

    public function getID() { return $this->id; }
    public function getName() { return $this->name; }
    public function getAvatarURL() { return $this->avatarURL; }
    public function isAnonymous() { return $this->isAnonymous; }
    public function isModeratorOrMore() { return $this->isModeratorOrMore; }

}

interface IUserInfoProvider
{
    /**
     * @return UserInfo
     */
    public static function getConnectedUserInfo();
}
