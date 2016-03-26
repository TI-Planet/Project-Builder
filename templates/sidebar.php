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
    global $pb;

    $currUser = $pb->getCurrentUser();
    $currProject = $pb->getCurrentProject();

    $currProjectID = ($currProject !== null) ? $currProject->getID() : -1;

    $header = '<h2><a href="/forum/portal.php" title="TI-Planet" target="_blank"><img src="/forum/styles/prosilver/theme/images/tiplanet_header_logo.png" alt="TI-Planet" height="55"/></a>Project Builder<sup><small> ß</small></sup></h2>';
    $content = '';

    $userProjects = $pb->getUserProjectsFromDB();

    $content .= '<div id="projectListHeader"><a href="/pb/?new=1&amp;csrf_token=' . $currUser->getSID() . '" id="newProjLink" class="btn btn-success btn-xs" style="float:right"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> New project</a><b>My projects:</b></div>';

    $content .= '<div id="projectList">';

    if ($currProject->getAuthorID() !== $currUser->getID()) {
        $content .= "<div>&nbsp;
                        <button class='btn btn-primary btn-xs pull-right' onclick='forkProject();'><span class='glyphicon glyphicon-duplicate' aria-hidden='true'></span> Fork this project</button>
                     </div>";
    }

    $content .= '<ul style="margin-top: 2px">';

    foreach ($userProjects as $project)
    {
        $projKey = "{$currUser->getID()}_{$project->created}_{$project->randkey}";
        $projTypeHTML = " <small><i>({$project->type})</i></small>"; // Replace by icon ?
        if ($projKey != $currProjectID)
        {
            $content .= '<li>';
            $content .= "<a href='/pb/?id={$projKey}'>{$project->internal_name}</a>"; // Use name, later
            $content .= $projTypeHTML;
        } else {
            $content .= '<li class="active">';
            $content .= "<b>{$project->internal_name}</b>"; // Use name, later
            $content .= $projTypeHTML;

            if ($currProject->isMultiuser()) {
                $sharedStyle = 'top: 2px;text-shadow: 3px 1px ' . ($currProject->isMulti_ReadWrite() ? "#4AA14A;" : "#9A9A9A;");
                $content .= " <span class='glyphicon glyphicon-user' style='{$sharedStyle}' aria-hidden='true' title='This project is shared (" . ($currProject->isMulti_ReadWrite() ? "Read/Write" : "Read only") . ")'></span> ";
            }

            $jsCreatedDate = "<script>var d = new Date({$project->created}*1000); document.write(d.toLocaleDateString()+' '+d.toLocaleTimeString());</script>";
            $jsUpdatedDate = "<script>var d = new Date({$project->updated}*1000); document.write(d.toLocaleDateString()+' '+d.toLocaleTimeString());</script>";
            $content .= '</br> Created: ' . $jsCreatedDate;
            $content .= '</br> Updated: ' . $jsUpdatedDate;

            $content .= '</br> <button class="btn btn-primary btn-xs" disabled><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Settings</button> ';
            $content .= "<button class='btn btn-primary btn-xs' onclick='forkProject();'><span class='glyphicon glyphicon-duplicate' aria-hidden='true'></span> Clone</button> ";
            $content .= '<button class="btn btn-danger btn-xs" onclick="deleteProject();"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete</button>';
        }
        $content .= '</li>';
    }
    $content .= '</ul></div>';

    return $header . $content;
}

echo genSidebar();
