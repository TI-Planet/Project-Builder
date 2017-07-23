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

require_once 'utils.php';
require_once 'PBStatus.php';
require_once 'ProjectManager.php';

$projectID = (isset($_GET['id']) && !empty($_GET['id'])) ? $_GET['id'] : null;
$fileName = (isset($_GET['file']) && !empty($_GET['file'])) ? $_GET['file'] : null;

$pm = new ProjectManager($projectID, ['id' => $projectID, 'file' => $fileName]);

/*
if ($pm->getCurrentUser()->getID() !== 1381) {
    header( "HTTP/1.1 503 Service Unavailable", true, 503 );
    header( "Retry-After: 3600" );
    die("Maintenance in progress, please come back soon!");
}
*/

// The PB needs a reasonable screen size, warn the mobile users
$isMobile = preg_match('/(android|avantgo|iphone|ipod|blackberry|iemobile|bolt|bo‌​ost|cricket|docomo|fone|hiptop|mini|opera mini|kitkat|mobi|palm|phone|pie|webos|wos)/i', $_SERVER['HTTP_USER_AGENT']);
if ($isMobile === 1)
{
    echo "<!DOCTYPE html>
    <head>
<meta charset=\"utf-8\">
    <title>TI-Planet | Online Project Builder</title>
    <style>
        html{height:100%;overflow:hidden;}
        body{height:100%;margin:8px;font-family:\"Helvetica Neue\",Helvetica,Arial,sans-serif;background-color:#ededed;}
    </style>
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no\" />
</head>
<body style='height:100%;margin:8px;font-family:\"Helvetica Neue\",Helvetica,Arial,sans-serif;background-color:#ededed;'>
    <div style='display:flex;justify-content:center;align-items:center;width:100%;height:100%;text-align:center;'>
        <span style='margin:8px;font-size:1.4em;color:#444;'>Aww, TI-Planet's Project Builder is only compatible with devices with larger displays.<br><br>Sorry :(</span>
    </div>
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
        ga('create', 'UA-25340424-5', 'auto');
        ga('send', 'pageview');
    </script>
</body>
</html>";
    die();
}

// Don't do much with bots for now...
if ($pm->getCurrentUser()->isBot())
{
    $content = "<!DOCTYPE html>
    <head>
<meta charset=\"utf-8\">
    <title>TI-Planet | Online Project Builder</title>
    <style>
        html{height:100%;overflow:hidden;}
        body{height:100%;margin:8px;font-family:\"Helvetica Neue\",Helvetica,Arial,sans-serif;background-color:#ededed;}
    </style>
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no\" />
</head>
<body style='height:100%;margin:8px;font-family:\"Helvetica Neue\",Helvetica,Arial,sans-serif;background-color:#ededed;'>
Since its beginning, TI-Planet has promoted programming, especially on TI calculators, through many news, program features and reviews, tutorials, contests etc.<br/>
We are now proud to launch, in beta, a new online platform (online so as to be more easily accessible), to push even further this programming promotional effort. This online tool is called the \"Project Builder\" (PB).<br/>
<br/>
<h2>What's the \"Project Builder\"?</h2>
Simply put, it's a \"subsite\" of TI-Planet, that offers a simplified interface through a set of tools (\"modules\"), such as an IDE, for creating, by oneself or with other people, content like programs, for calculators.</br>
The main module right now is the C IDE (editor, compiler, emulator...) targetting the TI-83 Premium CE and 84 Plus CE calculators.<br><br/>
More info: <a href='https://tiplanet.org/forum/viewtopic.php?f=41&amp;t=18118'>https://tiplanet.org/forum/viewtopic.php?f=41&amp;t=18118</a>
</body>
</html>";
    die($content);
}

// Until we decide what to do...
if ($pm->getCurrentUser()->isAnonymous())
{
    $url = 'https://tiplanet.org/forum/ucp.php?mode=login&redirect=' . urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . $url);
    die();
}

if ($projectID !== null)
{
    if ($pm->hasValidCurrentProject())
    {
        require 'templates/existingProject.php';
    } else {
        require 'templates/noSuchProject.php';
    }
} else {
    $wantNew = isset($_GET['new']) && (int)$_GET['new'] === 1;

    if ($wantNew) {
        /******** CSRF Token stuff ********/
        if (isset($_GET['csrf_token']) && !empty($_GET['csrf_token']))
        {
            if ($_GET['csrf_token'] !== $pm->getCurrentUser()->getSID())
            {
                header('HTTP/1.0 401 Unauthorized');
                die(json_encode(PBStatus::Error('Your session has expired - please re-login.')));
            }
        } else {
            header('HTTP/1.0 401 Unauthorized');
            die(json_encode(PBStatus::Error("Your session isn't recognized - please [re]login.")));
        }
        /******** CSRF Token stuff ********/

        require 'templates/newOrLoadProject.php';
    } else
    {
        $userProjects = $pm->getUserProjectsDataFromDB(1);
        if (count($userProjects) > 0)
        {
            $uid = $pm->getCurrentUser()->getID();
            $projKey = "{$uid}_{$userProjects[0]->created}_{$userProjects[0]->randkey}";
            $url = 'https://tiplanet.org/pb/?id=' . $projKey;
            header('Location: ' . $url);
            die();
        } else {
            require 'templates/newOrLoadProject.php';
        }
    }
}
