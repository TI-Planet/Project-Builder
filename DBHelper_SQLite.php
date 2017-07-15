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

class DBHelper_SQLite implements IDBHelper
{
    use DBHelper_Trait;

    public function execQuery($sql, $params = null)
    {
        $this->lastErrCode = null;
        try
        {
            //return $this->db->prepare($sql)->execute($params);
        } catch (\SQLiteException $e)
        {
            $this->lastErrCode = $e->getCode();
            return false;
        }
    }

    public function getQueryResults($sql, array $params = null)
    {
        try
        {
            //$req = $this->db->prepare($sql);
            //$req->execute($params);
            //$res = $req->fetchAll(\PDO::FETCH_OBJ);
            //$this->lastErrCode = null;
            //return $res;
        } catch (\SQLiteException $e)
        {
            $this->lastErrCode = $e->getCode();
            return [];
        }
    }

    public function lastInsertId()
    {
        return $this->dbConn->lastInsertRowID();
    }
}
