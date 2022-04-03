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

require_once 'PBStatus.class.php';
require_once 'ProjectManager.php';

header('Content-Type: application/json');

if (isset($_POST['id']) && !empty($_POST['id']))
{
    if (isset($_POST['action']) && !empty($_POST['action']))
    {
        $pm = new ProjectManager($_POST['id']);
        /*
        if (!in_array($pm->getCurrentUser()->getID(), [ 1381 ], true)) {
            header( "HTTP/1.1 503 Service Unavailable", true, 503 );
            header( "Retry-After: 3600" );
            die("Maintenance in progress, please come back soon!");
        }
        */

        /******** CSRF Token stuff ********/
        if (isset($_POST['csrf_token']) && !empty($_POST['csrf_token']))
        {
            if ($_POST['csrf_token'] !== $pm->getCurrentUser()->getSID())
            {
                header('HTTP/1.0 401 Unauthorized');
                die(json_encode(PBStatus::Error('Your session has expired - please re-login.')));
            }
        } else {
            header('HTTP/1.0 401 Unauthorized');
            die(json_encode(PBStatus::Error("Your session isn't recognized - please [re]login.")));
        }
        /******** CSRF Token stuff ********/


        /************* Logging ************/
        $log_action = static function($ok) use ($pm)
        {
            // We don't want to log all actions like fetching data. Only modifying actions are worth logging.
            $act = $_POST['action'];
            if (strpos($act, 'get') === 0) { return; }
            try
            {
                // Remove useless stuff to log...
                $paramsCopy = $_POST;
                unset($paramsCopy['action'], $paramsCopy['id'], $paramsCopy['csrf_token'], $paramsCopy['prgmName']);
                // Just log the length of the saved content
                if (isset($paramsCopy['source']))
                {
                    $paramsCopy['_len_'] = mb_strlen($paramsCopy['source']);
                    unset($paramsCopy['source']);
                }
                if (isset($paramsCopy['icon']))
                {
                    $paramsCopy['_len_'] = mb_strlen($paramsCopy['icon']);
                    unset($paramsCopy['icon']);
                }
                $paramsStr = count($paramsCopy) > 0 ? substr(json_encode($paramsCopy), 0, 49) : '';

                $pm->logActionInDB($act, $paramsStr, $ok === true);
            } catch (\Exception $e)
            {}
        };
        /************* Logging ************/


        if ($pm->hasValidCurrentProject())
        {
            $pmLastError = $pm->getLastError();
            if ($pmLastError !== null)
            {
                $log_action(false);
                header('HTTP/1.0 400 Bad request');
                die(json_encode(PBStatus::Error($pmLastError)));
            } else {
                $actionResult = $pm->doUserAction($_POST);
                $isError = PBStatus::isError($actionResult);
                $log_action($isError === false);
                if ($isError)
                {
                    header('HTTP/1.0 400 Bad request');
                }
                die(json_encode($actionResult));
            }
        } else {
            $log_action(false);
            header('HTTP/1.0 400 Bad request');
            die(json_encode(PBStatus::Error('This project does not exist or you do not have access to it')));
        }
    } else {
        header('HTTP/1.0 400 Bad request');
        die(json_encode(PBStatus::Error('No action given')));
    }
} else {
    header('HTTP/1.0 400 Bad request');
    die(json_encode(PBStatus::Error('No project ID given')));
}
