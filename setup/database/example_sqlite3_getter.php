<?php

// comment if needed
//error_reporting(0);
ini_set('display_errors', 0);

try
{
    $sqlite3 = new SQLite3(__DIR__ . '/projectbuilder.db');
}
catch(Exception $e)
{
    // print_r($e);
    die('An error occurred (SQLite3 constructor) !');
}

//debug_print_backtrace();
