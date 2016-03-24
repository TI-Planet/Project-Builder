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

require "ProjectManager.php";

$projectID = (isset($_GET['id']) && !empty($_GET['id'])) ? $_GET['id'] : null;
$fileName = (isset($_GET['file']) && !empty($_GET['file'])) ? $_GET['file'] : null;

$pb = new ProjectManager($projectID, ['id' => $projectID, 'file' => $fileName]);

// Until we decide what to do...
if ($pb->getCurrentUser()->isAnonymous())
{
    $url = 'https://tiplanet.org/forum/ucp.php?mode=login&redirect=' . urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . $url);
    die();
}

if ($projectID !== null)
{
    if ($pb->hasValidCurrentProject())
    {
        require "templates/existingProject.php";
    } else {
        require "templates/noSuchProject.php";
    }
} else {
    $wantNew = isset($_GET['new']) && $_GET['new'] == 1;

    if ($wantNew) {
        /******** CSRF Token stuff ********/
        require "nocsrf.php";
        try {
            // Run CSRF check, on POST data, in exception mode, no expiration, in one-time mode.
            \NoCSRF::check('csrf_token', $_GET, true, null, false);
        } catch ( \Exception $e ) {
            // CSRF attack detected
            header("HTTP/1.0 401 Unauthorized");
            die("[Error] Bad CSRF token. Please refresh.");
        }
        /******** CSRF Token stuff ********/

        require "templates/newOrLoadProject.php";
    } else
    {
        $userProjects = $pb->getUserProjectsFromDB(1);
        if (count($userProjects) > 0)
        {
            $uid = $pb->getCurrentUser()->getID();
            $projKey = "{$uid}_{$userProjects[0]->created}_{$userProjects[0]->randkey}";
            $url = 'https://tiplanet.org/pb/?id=' . $projKey;
            header('Location: ' . $url);
            die();
        } else {
            require "templates/newOrLoadProject.php";
        }
    }
}
