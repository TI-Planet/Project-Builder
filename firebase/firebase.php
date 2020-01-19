<?php
/*
 * Part of TI-Planet's Project Builder
 * (C) Adrien "Adriweb" Bertrand
 *
 * Implement the missing parts of this function as per your architecture
 *
 */

define('FIREBASE_SECRET', '*** YOUR SECRET HERE ***');

function getOrGenerateFirebaseTokenForUID($userID = 0, $forceRefresh = false)
{
    $userID = (int)$userID;
    if ($userID < 1)
    {
        return null;
    }

    // Here: implement the code that gets the existing and currently valid token from your DB/persistent storage
    // ......
    // ......

    // expired, not in the DB, or forceRefresh
    require_once 'JWT/JWT.php';
    require_once 'Token/TokenException.php';
    require_once 'Token/TokenGenerator.php';
    try {
        $generator = new Firebase\Token\TokenGenerator(FIREBASE_SECRET);
        $token = $generator->setOption('expires', $expires)->setData( ['uid' => (string)$userID] )->create();

        // Here: implement saving the new token in your DB/persistent storage
        // ......

    } catch (Exception $e) {
        // echo "Error: ".$e->getMessage();
        return null;
    }

    return $token;
}
