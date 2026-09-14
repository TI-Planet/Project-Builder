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
    global $pm;

    $llvmGitSHA = htmlentities(exec('echo $(cd ' . __DIR__ . '/../../opt/llvm-project/ && git rev-parse --short HEAD)'), ENT_QUOTES);

    $currUser = $pm->getCurrentUser();
    $currProject = $pm->getCurrentProject();
    $currProjectAuthor = $currProject->getAuthor();
    $currProjectInternalNameHTML = htmlentities($currProject->getInternalName(), ENT_QUOTES);

    $isUserAuthorOfProject = $currProject->getAuthorID() === $currUser->getID();
    $isAnonymousViewer = $currUser->isAnonymous();
    $canWriteCurrentProject = $pm->currentUserCanWriteCurrentProject();

    $header = '<h2><a href="/forum/portal.php" title="TI-Planet" target="_blank"><img src="/forum/styles/prosilver/theme/images/tiplanet_header_logo.png" alt="TI-Planet" height="55"/></a>Project Builder<sup><small> β</small></sup></h2>';

    $userProjects = $isAnonymousViewer ? [] : $pm->getUserProjectsDataFromDB();

    $content = '';

    // Current project at the top
    if ($currProject !== null)
    {
        $content .= '<div class="sidebarListHeader"><b>Current project:</b></div>';
        $content .= '<div id="currentProject">';
        $iconTitle = $canWriteCurrentProject ? 'Drag\'n\'drop a 16x16 icon.png file to change the project icon' : 'Project icon';
        $content .= '<div id="prgmIconContainer"><img id="prgmIconImg" alt="" class="hasTooltip" title="' . $iconTitle . '" src="' . $currProject->getIconURL() . '" /></div>';
        if ($canWriteCurrentProject)
        {
            // Turns out name is Description and internal name is Name.
            $content .= '<div id="prgmNameContainer"><span class="fieldSubContainer" onclick="changePrgmName(); return false;" title="Edit name"><span id="prgmNameSpan">' . $currProjectInternalNameHTML . '</span><span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span> <span class="glyphicon glyphicon-pencil inlineEditPencil"></span></span></div>';
            $content .= '<u title="Description">Desc</u>: <span id="projectNameContainer" class="fieldSubContainer" onclick="changeProjectName(); return false;" title="Edit description"><span id="projectNameSpan">' . htmlentities($currProject->getName(), ENT_QUOTES) . '</span><span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span> <span class="glyphicon glyphicon-pencil inlineEditPencil"></span></span><br/>';
        }
        else
        {
            $content .= '<div id="prgmNameContainer"><span id="prgmNameSpan">' . $currProjectInternalNameHTML . '</span></div>';
            $content .= '<u title="Description">Desc</u>: <span id="projectNameSpan">' . htmlentities($currProject->getName(), ENT_QUOTES) . '</span><br/>';
        }

        if (!$isUserAuthorOfProject) {
            $authorNameHTML = htmlentities($currProjectAuthor->getName(), ENT_QUOTES);
            $content .= "<u>Author</u>: <a href='https://tiplanet.org/forum/memberlist.php?mode=viewprofile&amp;u={$currProjectAuthor->getID()}' target='_blank'>$authorNameHTML</a><br/>";
        }
        $content .= '<u>Type</u>: ' . $currProject->getType();
        $content .= '<br/><u>Created</u>: ' . "<script>var d = new Date({$currProject->getCreatedTstamp()}*1000); document.write(d.toLocaleDateString()+' '+d.toLocaleTimeString());</script>";
        $content .= '<br/><u>Updated</u>: ' . "<script>var d = new Date({$currProject->getUpdatedTstamp()}*1000); document.write(d.toLocaleDateString()+' '+d.toLocaleTimeString());</script>";
        $content .= '<br/><u>Shared</u>: ' . ($currProject->isMultiuser() ? ('Yes (' . ($currProject->isMulti_ReadWrite() ? ($currProject->isMulti_ReadWrite_CustomRestricted() ? 'Read/Write...' : 'Read/Write') : 'Read only') . ')') : 'No. Share: ');
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
        if ($isAnonymousViewer)
        {
            $content .= '<div style="margin-top:8px" class="alert alert-info">You are viewing this shared project as a guest. Editing and other account actions are disabled. <a href="/forum/ucp.php?mode=login&redirect=' . urlencode($_SERVER['REQUEST_URI']) . '"><u>Log in</u></a>?</div>';
        }

        $content .= '<div style="height: 5px;"></div>';
        if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore()) {
            $content .= '<button class="btn btn-primary btn-xs" onclick="$(\'#settingsModal\').modal()"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Settings...</button> ';
        }
        if (!$isAnonymousViewer)
        {
            $cloneLabel = $isUserAuthorOfProject ? 'Clone' : 'Fork';
            $content .= "<button class='btn btn-primary btn-xs' onclick='forkProject();' title='Duplicate this project'><span class='glyphicon glyphicon-duplicate' aria-hidden='true'></span> {$cloneLabel} project</button> ";
        }
        if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore()) {
            $content .= '<button class="btn btn-danger btn-xs" onclick="deleteProject();"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete</button>';
        }

        $content .= '</div>';
    }

    if (!$isAnonymousViewer)
    {
        $content .= '<div id="projectListHeader" class="sidebarListHeader">
                          <div class="btn-group" role="group" style="float:right">
                            <button type="button" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> New project
                              <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropbox-menu-right" style="min-width: 0;">
                              <li><a href="/pb/?new=1&amp;type=native_eZ80&amp;csrf_token=' . $currUser->getSID() . '">CE C/C++</a></li>
                              <li><a href="/pb/?new=1&amp;type=python_eZ80&amp;csrf_token=' . $currUser->getSID() . '">CE/Evo Python</a></li>
                              <li><a href="/pb/?new=1&amp;type=basic_eZ80&amp;csrf_token=' . $currUser->getSID() . '">CE/Evo TI-Basic</a></li>
                              <li><hr style="margin: 2px"></li>
                              <li><a href="/pb/?new=1&amp;type=lua_nspire&amp;csrf_token=' . $currUser->getSID() . '">TI-Nspire Lua</a></li>
                              <li><a href="/pb/?new=1&amp;type=python_nspire&amp;csrf_token=' . $currUser->getSID() . '">TI-Nspire CX II Python</a></li>';
        if ($currUser->isModeratorOrMore()) {
            $content .= '
                              <li><hr style="margin: 2px"></li>
                              <li title="Alpha version" data-toggle="tooltip" data-placement="right"><a href="/pb/?new=1&amp;type=bbcode&amp;csrf_token=' . $currUser->getSID() . '">BBCode</a></li>';
        }
        $content .= '
                            </ul>
                          </div>
                          <b>My projects:</b>
                     </div>';

        $content .= '<div id="projectList">';
        if (count($userProjects) > 0)
        {
            $content .= '<ul style="margin-top: 2px">';
            foreach ($userProjects as $project)
            {
                $projID = "{$currUser->getID()}_{$project->created}_{$project->randkey}";
                $projectInternalNameHTML = htmlentities($project->internal_name, ENT_QUOTES);
                $projectIconURL = IBackend::getWebPublicIconURL($projID);
                if ($currProject && (int)$project->id !== $currProject->getDBID())
                {
                    $content .= "<li><img src='{$projectIconURL}' alt=''/> <a href='/pb/?id={$projID}'>{$projectInternalNameHTML}</a> <small><i>({$project->type})</i></small></li>";
                } else {
                    $content .= "<li><img src='{$projectIconURL}' alt=''/> <span id='prgmNameSpanInList'>{$projectInternalNameHTML}</span> <small><i>({$project->type})</i></small></li>";
                }
            }
            $content .= '</ul>';
        } else {
            $content .= '<span style="margin-left: 1.75em">No project yet! Go create one :)</span>';
        }
        $content .= '</div>';
    } else {
        $content .= '<div id="projectListHeader" class="sidebarListHeader"><b>Guest access</b></div>';
        $content .= '<div id="projectList"><span style="margin-left: 1.75em"><a href="/forum/ucp.php?mode=login&redirect=' . urlencode($_SERVER['REQUEST_URI']) . '">Log in</a> to create or manage projects.</span></div>';
    }

    if ($currProject && $pm->currentUserHasLiveCollabEditAccess() && $currProject->isChatEnabled()) {
        $content .= '<div id="firechat-wrapper"></div>';
    }

    $content .= '<div id="statusbar_left">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary btn-xs" style="border-radius:0" onclick="toggleDarkTheme();"><span class="glyphicon glyphicon-eye-close"></span> Dark mode</button>
                        <button id="customExtraSBButton" type="button" class="btn btn-primary btn-xs" style="display:none; border-radius:0"></button>
                    </div>
                    <span class="copyright">PB &copy; 2015-' . date('Y') . ' "Adriweb"</span>
                </div>';

    $content .= '<div class="modal fade" id="keybindingsModal" tabindex="-1" role="dialog" aria-labelledby="myKeybindingsModalLabel">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header" style="border: 0; padding-bottom: 5px;">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="myKeybindingsModalLabel">Editor preferences and key bindings</h4>
                            </div>
                            <div class="modal-body" style="border-bottom: 1px #eee solid;">
            
                            </div>
                            <div class="modal-header" style="border: 0; padding-bottom: 5px;">
                                <h4 class="modal-title" id="myKeybindingsModalLabel">About</h4>
                            </div>
                            <div class="modal-body">
                                <div>
                                    <b style="margin-bottom: 4px; display: inline-block;">Project Builder &copy; 2015-' . date('Y') . ' "Adriweb"</b>.
                                    [ <a href="https://tiplanet.org/forum/viewtopic.php?t=18118" target="_blank">TI-Planet topic</a> ]<br/>
                                    <span style="margin-bottom: 4px; display: inline-block;">Many thanks to Matt "MateoC" Waltz, Jacob "Jacobly" Young, Zachary "Runer112" Wassall, TI-Planet colleagues, and others...</span><br/>
                                    The PB\'s source is available <a href="https://github.com/TI-Planet/Project-Builder" target="_blank">on GitHub</a>.
                                    It makes use of, among other things,
                                      <a href="https://codemirror.net/" target="_blank">CodeMirror</a>,
                                      the community\'s <a href="https://github.com/CE-Programming/toolchain" target="_blank">CE toolchain</a>,
                                      <a href="https://github.com/CE-Programming/CEmu" target="_blank">CEmu</a>,
                                      <a href="https://github.com/jacobly0/llvm-project" target="_blank" title="' . $llvmGitSHA . '">LLVM (e)z80</a>,
                                      <a href="https://github.com/adriweb/tivars_lib_cpp" target="_blank">tivars_lib_cpp</a>,
                                      <a href="https://github.com/TI-Toolkit/tokens" target="_blank">tokens</a>&amp;<a href="https://github.com/TI-Toolkit/tokens-wiki" target="_blank">tokens wiki</a>...
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>';

    return $header . $content;
}

echo genSidebar();
