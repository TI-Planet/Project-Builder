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
    $currProjectAuthor = $pb->getCurrentProject()->getAuthor();

    $isUserAuthorOfProject = $currProject->getAuthorID() === $currUser->getID();

    $header = '<h2><a href="/forum/portal.php" title="TI-Planet" target="_blank"><img src="/forum/styles/prosilver/theme/images/tiplanet_header_logo.png" alt="TI-Planet" height="55"/></a>Project Builder<sup><small> β</small></sup></h2>';

    $userProjects = $pb->getUserProjectsDataFromDB();

    $content = '';

    // Current project at the top
    if ($currProject !== null)
    {
        $content .= '<div class="sidebarListHeader"><b>Current project:</b></div>';
        $content .= '<div id="currentProject">';
        $content .= '<div id="prgmNameContainer"><span id="prgmNameSpan">' . $currProject->getInternalName() . '</span><span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span> (<a href="#" onclick="changePrgmName(); return false;">rename</a>)</div>';

        if (!$isUserAuthorOfProject) {
            $authorNameHTML = htmlentities($currProjectAuthor->getName(), ENT_QUOTES);
            $content .= "<u>Author</u>: <a href='https://tiplanet.org/forum/memberlist.php?mode=viewprofile&amp;u={$currProjectAuthor->getID()}' target='_blank'>$authorNameHTML</a><br/>";
        }
        $content .= '<u>Type</u>: ' . $currProject->getType();
        $content .= '<br/><u>Created</u>: ' . "<script>var d = new Date({$currProject->getCreatedTstamp()}*1000); document.write(d.toLocaleDateString()+' '+d.toLocaleTimeString());</script>";
        $content .= '<br/><u>Updated</u>: ' . "<script>var d = new Date({$currProject->getUpdatedTstamp()}*1000); document.write(d.toLocaleDateString()+' '+d.toLocaleTimeString());</script>";
        $content .= '<br/><u>Shared</u>: ' . ($currProject->isMultiuser() ? ('Yes (' . ($currProject->isMulti_ReadWrite() ? 'Read/Write' : 'Read only') . ')') : 'No. Share: ');
        if ($currProject->getAuthorID() === $currUser->getID())
        {
            if ($currProject->isMultiuser()) {
                $content .= " <button class='btn btn-warning btn-xs' onclick='disableMultiUser();'>Disable</button>";
            } else {
                $content .= " <button title='Other users will not be able to modify the project' class='btn btn-success btn-xs' onclick='enableMultiUserRO();'>Read</button>
                              <button title='Other users will be able to modify the project' class='btn btn-success btn-xs' onclick='enableMultiUserRW();'>Read+Write</button>";
            }
        }
        if ($currProject->isMultiuser())
        {
            $content .= '<br/><u>Online</u>: <div id="userlist"></div>';
        }

        $content .= '<div style="height: 5px;"></div>';
        if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore()) {
            $content .= '<button class="btn btn-primary btn-xs" disabled><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Settings...</button> ';
        }
        $cloneLabel = $isUserAuthorOfProject ? 'Clone' : 'Fork';
        $content .= "<button class='btn btn-primary btn-xs' onclick='forkProject();' title='Duplicate this project'><span class='glyphicon glyphicon-duplicate' aria-hidden='true'></span> {$cloneLabel} project</button> ";
        if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore()) {
            $content .= '<button class="btn btn-danger btn-xs" onclick="deleteProject();"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete</button>';
        }

        $content .= '</div>';
    }

    $content .= '<div id="projectListHeader" class="sidebarListHeader"><a href="/pb/?new=1&amp;csrf_token=' . $currUser->getSID() . '" id="newProjLink" class="btn btn-success btn-xs" style="float:right"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> New project</a><b>My projects:</b></div>';

    $content .= '<div id="projectList">';
    if (count($userProjects) > 0)
    {
        $content .= '<ul style="margin-top: 2px">';
        foreach ($userProjects as $project)
        {
            // TODO: Use name and icon
            if ($currProject && (int)$project->id !== $currProject->getDBID())
            {
                $content .= "<li><a href='/pb/?id={$currUser->getID()}_{$project->created}_{$project->randkey}'>{$project->internal_name}</a> <small><i>({$project->type})</i></small></li>";
            } else {
                $content .= "<li><span id='prgmNameSpanInList'>{$project->internal_name}</span> <small><i>({$project->type})</i></small></li>";
            }
        }
        $content .= '</ul>';
    } else {
        $content .= '<span style="margin-left: 1.75em">No project yet! Go create one :)</span>';
    }
    $content .= '</div>';

    if ($currProject && $currProject->isMultiuser() && $currProject->isMulti_ReadWrite() && $currProject->isChatEnabled()) {
        $content .= '<div id="firechat-wrapper"></div>';
    }

    $content .= '<div id="statusbar_left">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary btn-xs" style="border-radius:0" onclick="toggleDarkTheme();"><span class="glyphicon glyphicon-eye-close"></span> Dark mode</button>
                        <button id="customExtraSBButton" type="button" class="btn btn-primary btn-xs" style="display:none; border-radius:0"></button>
                    </div>
                    <span class="copyright">PB &copy; 2015-2017 "Adriweb"</span>
                </div>';

    return $header . $content;
}

echo genSidebar();
