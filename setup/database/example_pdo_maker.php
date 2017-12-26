<?php

// comment if needed
//error_reporting(0);
ini_set('display_errors', 0);

require_once 'pdo_db_config.php';

try {
    $pdo = new PDO('mysql:host=' . $dbhost . ';dbname=' . $dbname, $dbuser, $dbpasswd);
    $pdo->exec('SET NAMES utf8');
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
} catch (Exception $e) {
    echo 'Une erreur est survenue !';
    die();
}

//debug_print_backtrace();
