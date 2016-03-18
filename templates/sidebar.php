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

function genSidebar()
{
    global $pb, $initialCSRFToken; // $initialCSRFToken is created in existingProject.php

    $currUser = $pb->getCurrentUser();
    $currProject = $pb->getCurrentProject();

    $currProjectID = ($currProject !== null) ? $currProject->getID() : -1;
    $uid = $currUser->getID();

    $header = '<h2><a href="/forum/portal.php" title="TI-Planet" target="_blank"><img src="/forum/styles/prosilver/theme/images/tiplanet_header_logo.png" alt="TI-Planet" height="55"/></a>Project Builder<sup><small> ß</small></sup></h2>';
    $content = '';

    $userProjects = $pb->getUserProjectsFromDB();

    $content .= '<div id="projectListHeader"><a href="/pb/?new=1&amp;csrf_token=' . $initialCSRFToken . '" class="btn btn-success btn-xs" style="float:right"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> New project</a><b>My projects:</b></div>';

    $content .= '<div id="projectList"><ul>';
    foreach ($userProjects as $project)
    {
        $projKey = "{$uid}_{$project->created}_{$project->randkey}";
        $projTypeHTML = " <small><i>({$project->type})</i></small>"; // Replace by icon ?
        $content .= '<li>';
        if ($projKey != $currProjectID)
        {
            $content .= "<a href='/pb/?id={$projKey}'>{$project->internal_name}</a>"; // Use name, later
            $content .= $projTypeHTML;
        } else {
            $content .= "<b>{$project->internal_name}</b>"; // Use name, later
            $content .= $projTypeHTML;

            $jsCreatedDate = "<script>var d = new Date({$project->created}*1000); document.write(d.toLocaleDateString()+' '+d.toLocaleTimeString());</script>";
            $jsUpdatedDate = "<script>var d = new Date({$project->updated}*1000); document.write(d.toLocaleDateString()+' '+d.toLocaleTimeString());</script>";
            $content .= '</br> Created: ' . $jsCreatedDate;
            $content .= '</br> Updated: ' . $jsUpdatedDate;

            $content .= '</br> <button class="btn btn-primary btn-xs" disabled><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Settings</button>
                               <button class="btn btn-danger btn-xs" disabled><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete...</button>';
        }
        $content .= '</li>';
    }
    $content .= '</ul></div>';

    return $header . $content;
}

echo genSidebar();
