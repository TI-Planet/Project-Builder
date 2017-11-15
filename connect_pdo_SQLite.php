<?php

// comment if needed
//error_reporting(0);
ini_set('display_errors', 0);

require "dbconfig_SQLite.php";

try
{
    $pdo = new PDO("", $dbuser, $dbpasswd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
}
catch(Exception $e)
{
    // print_r($e);
    die('An error occurred (PDO connect) !');
}

//debug_print_backtrace();
