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

/* This content will be included and displayed.
   This page should not be called directly. */
if (!isset($pm))
{
    die('Ahem ahem');
}

    // TODO : create (from modules) or load (get user's projects list from DB)
    // echo "new or load page";

    // For now, hardcode the native_eZ80 project type
    $proj = $pm->createNewProject('native_eZ80', '', 'CPRGMCE');
    if ($proj !== null)
    {
        $url = '/pb/?id=' . $proj->getPID();
        header('Location: ' . $url);
        die();
    }

?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>TI-Planet | Project Builder</title>

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="<?= cacheBusterPath('css/main.css') ?>">

    <script src="js/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</head>
<body>

<h1>TODO: New/Load Project page - You should not see this ; something must have been wrong</h1>

<script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-25340424-5', 'auto');
    ga('send', 'pageview');
</script>

</body>
</html>
