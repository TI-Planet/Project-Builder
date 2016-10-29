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

require_once 'ProjectManager.php';

header('Content-Type: application/json');

if (isset($_POST['id']) && !empty($_POST['id']))
{
    if (isset($_POST['action']) && !empty($_POST['action']))
    {
        $pb = new ProjectManager($_POST['id']);

        /******** CSRF Token stuff ********/
        if (isset($_POST['csrf_token']) && !empty($_POST['csrf_token']))
        {
            if ($_POST['csrf_token'] !== $pb->getCurrentUser()->getSID())
            {
                header('HTTP/1.0 401 Unauthorized');
                die(json_encode('[Error] Your session has expired - please re-login.'));
            }
        } else {
            header('HTTP/1.0 401 Unauthorized');
            die(json_encode("[Error] Your session isn't recognized - please [re]login."));
        }
        /******** CSRF Token stuff ********/

        if ($pb->hasValidCurrentProject())
        {
            $pmLastError = $pb->getLastError();
            if ($pmLastError !== null)
            {
                header('HTTP/1.0 400 Bad request');
                die(json_encode('[Error] ' . $pmLastError));
            } else {
                $actionResult = $pb->doUserAction($_POST);
                if (is_string($actionResult) && strpos($actionResult, '[Error]') === 0)
                {
                    header('HTTP/1.0 400 Bad request');
                }
                die(json_encode($actionResult));
            }
        } else {
            header('HTTP/1.0 400 Bad request');
            die(json_encode('[Error] This project does not exist or you do not have access to it'));
        }
    } else {
        header('HTTP/1.0 400 Bad request');
        die(json_encode('[Error] No action given'));
    }
} else {
    header('HTTP/1.0 400 Bad request');
    die(json_encode('[Error] No project ID given'));
}
