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

trait DBHelper_Trait
{
    protected static $instance;

    /** @var $dbConn \PDO|\SQLite3 */
    protected $dbConn;

    /**
     * @var int|null
     */
    private $lastErrCode;

    /**
     * @param \PDO|\SQLite3 $dbObj
     * @return mixed
     */
    final public static function get($dbObj)
    {
        return static::$instance !== null ? static::$instance : (static::$instance = new self($dbObj));
    }

    /**
     * @param \PDO|\SQLite3 $dbObj
     */
    final private function __construct($dbObj)
    {
        $this->dbConn = $dbObj;
    }

    final public  function __wakeup() {}
    final private function __clone() {}

    /**
     * @return int|null
     */
    public function getLastErrCode()
    {
        return $this->lastErrCode;
    }
}

abstract class DBHelper_Type {
    const PDO    = 'pdo';
    const SQLite = 'sqlite';
}

interface IDBHelper
{
    /**
     * @param   string          $sql        The query
     * @param   string[]|null   $params     For prepared statements
     * @return  bool                        Whether it succeeded
     */
    public function execQuery($sql, array $params = null);

    /**
     * @param   string          $sql        The query
     * @param   string[]|null   $params     For prepared statements
     * @return  \stdClass[]                 The results (array of objects)
     */
    public function getQueryResults($sql, array $params = null);

    /**
     * @return string|int
     */
    public function lastInsertId();

    /**
     * @return int|null
     */
    public function getLastErrCode();
}

/**
 * @param   string          $type
 * @param   \PDO|\SQLite3   $dbObj
 * @return  IDBHelper
 * @throws  \InvalidArgumentException
 */
function DBHelper_Create($type, $dbObj)
{
    switch ($type)
    {
        case DBHelper_Type::PDO:
            require_once 'DBHelper_PDO.php';
            return DBHelper_PDO::get($dbObj);

        case DBHelper_Type::SQLite:
            require_once 'DBHelper_SQLite.php';
            return DBHelper_SQLite::get($dbObj);

        default:
            throw new \InvalidArgumentException('Invalid type given to DBHelper_Create');
    }
}