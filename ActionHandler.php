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

require_once "ProjectManager.php";
require "nocsrf.php";

header('Content-Type: application/json');

if (isset($_POST['id']) && !empty($_POST['id']))
{
    if (isset($_POST['action']) && !empty($_POST['action']))
    {
        /******** CSRF Token stuff ********/
        if ($_POST['action'] !== 'download') // This is problematic with non-AJAX stuff. Don't require CSRF for a download
        {
            $badToken = false;
            try
            {
                // Run CSRF check, on POST data, in exception mode, no expiration, in one-time mode.
                \NoCSRF::check('csrf_token', $_POST, true, null, false);
            } catch (\Exception $e)
            {
                // CSRF attack detected
                $badToken = true;
            }
            if ($badToken)
            {
                header("HTTP/1.0 400 Bad request");
                die(json_encode("[Error] Bad CSRF token"));
            }
            else
            {
                header('pb-csrf-token: ' . \NoCSRF::generate('csrf_token'));
            }
        }
        /******** CSRF Token stuff ********/

        $pb = new ProjectManager($_POST['id']);
        if ($pb->hasValidCurrentProject())
        {
            $pmLastError = $pb->getLastError();
            if ($pmLastError !== null)
            {
                header("HTTP/1.0 400 Bad request");
                die(json_encode("[Error] " . $pmLastError));
            } else {
                $actionResult = $pb->doUserAction($_POST);
                if (is_string($actionResult) && strpos($actionResult, "[Error]") === 0)
                {
                    header("HTTP/1.0 400 Bad request");
                }
                die(json_encode($actionResult));
            }
        } else {
            header("HTTP/1.0 400 Bad request");
            die(json_encode("[Error] This project does not exist or you do not have access to it"));
        }
    } else {
        header("HTTP/1.0 400 Bad request");
        die(json_encode("[Error] No action given"));
    }
} else {
    header("HTTP/1.0 400 Bad request");
    die(json_encode("[Error] No project ID given"));
}
