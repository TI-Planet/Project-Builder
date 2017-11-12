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

    public function execQuery($sql, array $params = null)
    {
        $this->lastErrCode = null;
        try
        {
            $stmt = $this->dbConn->prepare($sql);
            if ($params !== null)
            {
                foreach ($params as $pName => $pValue)
                {
                    $stmt->bindParam(':' . $pName, $pValue);
                }
            }
            if (($res = $stmt->execute()) !== false)
            {
                @$res->finalize();
                return true;
            }
            return false;
        } catch (\SQLiteException $e)
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
            $stmt = $this->dbConn->prepare($sql);
            if ($params !== null)
            {
                foreach ($params as $pName => $pValue)
                {
                    $stmt->bindParam(':' . $pName, $pValue);
                }
            }
            if (($res = $stmt->execute()) === false)
            {
                return [];
            }
            $multiArray = [];
            if ($res->numColumns() > 0)
            {
                $idx = 0;
                while ($row = $res->fetchArray(SQLITE3_ASSOC))
                {
                    foreach ($row as $colName => $value)
                    {
                        $multiArray[$idx][$colName] = $value;
                    }
                    $multiArray[$idx] = (object)$multiArray[$idx];
                    $idx++;
                }
            }
            @$res->finalize();
            return $multiArray;
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
