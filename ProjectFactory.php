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

require_once "Project.php";

class ProjectFactory
{
    // TODO : probably build that list from the DB
    public static $projectTypes = ['basic_z80','native_z80','native_eZ80','lua_nspire','sprite','var_z80'];

    /* TODO : Also move the load/create from DB stuff into this class ? */

    /**
     * @param          $db_id
     * @param          $randKey
     * @param UserInfo $author
     * @param          $type
     * @param          $name
     * @param          $internalName
     * @param          $multiuser
     * @param          $readonly
     * @param          $chatEnabled
     * @param          $cTime
     * @param          $uTime
     * @return null|Project
     * @throws \Exception
     */
    public static function create($db_id, $randKey, UserInfo $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime)
    {
        if (!in_array($type, self::$projectTypes))
        {
            throw new \InvalidArgumentException("Project type must be one of " . json_encode(self::$projectTypes));
        }

        $customClassName = "{$type}Project";
        $customFullClassName = "ProjectBuilder\\{$type}Project";
        $customIncludePath = __DIR__ . "/modules/{$type}/{$customClassName}.php";
        if (file_exists($customIncludePath))
        {
            // There's a custom class (server-side)
            require_once $customIncludePath;
            try {
                return new $customFullClassName($db_id, $randKey, $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime);
            } catch (\Exception $e)
            {
                echo $e->getMessage();
                return null;
            }
        } else {
            throw new \Exception("Internal error: couldn't find the module class");
        }
    }
}
