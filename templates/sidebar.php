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

    $isUserAuthorOfProject = $currProject->getAuthorID() === $currUser->getID();

    $header = '<h2><a href="/forum/portal.php" title="TI-Planet" target="_blank"><img src="/forum/styles/prosilver/theme/images/tiplanet_header_logo.png" alt="TI-Planet" height="55"/></a>Project Builder<sup><small> β</small></sup></h2>';

    $userProjects = $pm->getUserProjectsDataFromDB();

    $content = '';

    // Current project at the top
    if ($currProject !== null)
    {
        $content .= '<div class="sidebarListHeader"><b>Current project:</b></div>';
        $content .= '<div id="currentProject">';
        $content .= '<div id="prgmIconContainer"><img id="prgmIconImg" alt="" class="hasTooltip" title="Drag\'n\'drop a 16x16 icon.png file to change the project icon" src="' . $currProject->getIconURL() . '" /></div>';
        $content .= '<div id="prgmNameContainer"><span class="fieldSubContainer" onclick="changePrgmName(); return false;" title="Edit name"><span id="prgmNameSpan">' . $currProject->getInternalName() . '</span><span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span> <span class="glyphicon glyphicon-pencil inlineEditPencil"></span></span></div>';

        // Turns out name is Description and internal name is Name.
        $content .= '<u title="Description">Desc</u>: <span id="projectNameContainer" class="fieldSubContainer" onclick="changeProjectName(); return false;" title="Edit description"><span id="projectNameSpan">' . htmlentities($currProject->getName(), ENT_QUOTES) . '</span><span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span> <span class="glyphicon glyphicon-pencil inlineEditPencil"></span></span><br/>';
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
            $content .= '<button class="btn btn-primary btn-xs" onclick="$(\'#settingsModal\').modal()"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Settings...</button> ';
        }
        $cloneLabel = $isUserAuthorOfProject ? 'Clone' : 'Fork';
        $content .= "<button class='btn btn-primary btn-xs' onclick='forkProject();' title='Duplicate this project'><span class='glyphicon glyphicon-duplicate' aria-hidden='true'></span> {$cloneLabel} project</button> ";
        if ($currProject->getAuthorID() === $currUser->getID() || $currUser->isModeratorOrMore()) {
            $content .= '<button class="btn btn-danger btn-xs" onclick="deleteProject();"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete</button>';
        }

        $content .= '</div>';
    }

    $content .= '<div id="projectListHeader" class="sidebarListHeader">
                      <div class="btn-group" role="group" style="float:right">
                        <button type="button" class="btn btn-success btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> New project
                          <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropbox-menu-right" style="min-width: 0;">
                          <li><a href="/pb/?new=1&amp;type=native_eZ80&amp;csrf_token=' . $currUser->getSID() . '">CE C/C++</a></li>
                          <li><a href="/pb/?new=1&amp;type=python_eZ80&amp;csrf_token=' . $currUser->getSID() . '">CE Python</a></li>
                          <li title="Alpha version" data-toggle="tooltip" data-placement="right"><a href="/pb/?new=1&amp;type=basic_eZ80&amp;csrf_token=' . $currUser->getSID() . '">CE TI-Basic</a></li>
                          <li title="Alpha version" data-toggle="tooltip" data-placement="right"><a href="/pb/?new=1&amp;type=lua_nspire&amp;csrf_token=' . $currUser->getSID() . '">TI-Nspire Lua</a></li>
                          <li class="disabled" title="Soon!" data-toggle="tooltip" data-placement="right"><a href="#" class="disabled" disabled>TI-Nspire Python</a></li>
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
            if ($currProject && (int)$project->id !== $currProject->getDBID())
            {
                $content .= "<li><img src='/pb/projects/{$projID}/icon.png' alt=''/> <a href='/pb/?id={$projID}'>{$project->internal_name}</a> <small><i>({$project->type})</i></small></li>";
            } else {
                $content .= "<li><img src='/pb/projects/{$projID}/icon.png' alt=''/> <span id='prgmNameSpanInList'>{$project->internal_name}</span> <small><i>({$project->type})</i></small></li>";
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
                    <span class="copyright">PB &copy; 2015-' . date('Y') . ' "Adriweb"</span>
                </div>';

    $content .= '<div class="modal fade" id="keybindingsModal" tabindex="-1" role="dialog" aria-labelledby="myKeybindingsModalLabel">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header" style="border: 0; padding-bottom: 5px;">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="myKeybindingsModalLabel">Editor key bindings</h4>
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
                                      <a href="https://github.com/adriweb/tivars_lib_cpp" target="_blank">tivars_lib_cpp</a>...
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
