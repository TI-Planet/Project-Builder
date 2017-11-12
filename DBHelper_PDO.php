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

class DBHelper_PDO implements IDBHelper
{
    use DBHelper_Trait;

    public function execQuery($sql, array $params = null)
    {
        $this->lastErrCode = null;
        try
        {
            return $this->dbConn->prepare($sql)->execute($params);
        } catch (\PDOException $e)
        {
            $this->lastErrCode = $e->getCode();
            return false;
        }
    }

    public function getQueryResults($sql, array $params = null)
    {
        $this->lastErrCode = null;
        try
        {
            $req = $this->dbConn->prepare($sql);
            $req->execute($params);
            return $req->fetchAll(\PDO::FETCH_OBJ);
        } catch (\PDOException $e)
        {
            $this->lastErrCode = $e->getCode();
            return [];
        }
    }

    public function lastInsertId()
    {
        return $this->dbConn->lastInsertId();
    }
}
