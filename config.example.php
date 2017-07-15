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

require_once 'IDBHelper.php';

// Customize these lines to your setup.
require_once '/the/path/to/your/own/pdo_connection_maker.php';
global $PB_CONFIG, $pdo;
// You have to fill those two config values with your things.
$PB_CONFIG['DB_TYPE'] = DBHelper_Type::PDO;
$PB_CONFIG['DB_OBJ']  = $pdo;
$PB_CONFIG['USER_INFO_PROVIDER_CLASS'] = 'my_UserInfoProvider';

// Require your custom UserInfoProvider here
require_once '/the/path/to/your/own/my_UserInfoProvider.php';

