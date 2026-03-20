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

final class lua_nspireProject extends Project
{
    const PROJECT_MODULE_NAME        = 'Nspire-Lua IDE for the TI-Nspire calculators';
    const PROJECT_MODULE_DESCRIPTION = 'Nspire-Lua IDE for the TI-Nspire / CX / CX II calculators';

    const REGEXP_GOOD_FILE_PATTERN = "/^([A-Z0-9]+)\\.lua$/i";
    const TEMPLATE_FILE            = 'PRGM.lua';

    public function __construct($db_id, $pid, UserInfo $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime, $isReadWriteCustom = false, array $readWriteAllowedUserIDs = [])
    {
        parent::__construct($db_id, $pid, $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime, $isReadWriteCustom, $readWriteAllowedUserIDs);
        $this->initProjectBackend(__DIR__ . '/Backend.php', lua_nspireProjectBackend::class);
    }

}
