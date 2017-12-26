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

require_once 'IUserInfoProvider.php';

final class example_UserInfoProvider implements IUserInfoProvider
{

    public static function getConnectedUserInfo()
    {
        // write your code here
    }

    /**
     * @param int $userID
     * @return UserInfo
     */
    public static function getUserInfoFromID($userID = 1)
    {
        // write your code here, it should return a UserInfo
    }
}
