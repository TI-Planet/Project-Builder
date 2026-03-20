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

require_once __DIR__ . '/../../Project.class.php';

final class bbcodeProject extends Project
{
    const PROJECT_MODULE_NAME        = 'BBCode content editor';
    const PROJECT_MODULE_DESCRIPTION = 'TI-Planet BBCode editor with live preview';

    const REGEXP_GOOD_FILE_PATTERN = "/^([A-Za-z0-9_\-]{1,25})\\.bbcode$/";
    const TEMPLATE_FILE            = 'Article.bbcode';

    public function __construct($db_id, $pid, UserInfo $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime, $isReadWriteCustom = false, array $readWriteAllowedUserIDs = [])
    {
        parent::__construct($db_id, $pid, $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime, $isReadWriteCustom, $readWriteAllowedUserIDs);
        $this->initProjectBackend(__DIR__ . '/Backend.php', bbcodeProjectBackend::class);
    }

    public function getFileListHTML($allowRename = true)
    {
        return '';
    }

    public function setName(string $name)
    {
        if (strlen($name) > 25 || preg_match('~^[\w ._+\-*/<>,:()]{0,25}$~', $name) !== 1)
        {
            return false;
        }
        $this->name = $name;
        return true;
    }

    public function setInternalName(string $internalName)
    {
        if (strlen($internalName) <= 25 && preg_match('~^[\w ._+\-*/<>,:()]{0,25}$~', $internalName) === 1)
        {
            $this->internalName = $internalName;
            return true;
        }
        return false;
    }
}
