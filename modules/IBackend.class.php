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

/* Despite the name, it's not actually really an interface, but oh well... */

/**
 * This abstract class handles the default and final implementations of the generic methods related to
 * project folder management. It calls a native helper (e.g. bash script) to do the actual actions.
 * It also defines the prototypes to be implemented in child classes.
 */
abstract class IBackend
{
    /**
     * @var Project
     */
    protected $project;
    /**
     * @var int
     */
    protected $projID;
    /**
     * @var string
     */
    protected $projPrgmName;
    /**
     * @var string
     */
    protected $projPrgmExtension;
    /**
     * @var string
     */
    protected $projCurrFile;
    /**
     * @var string
     */
    protected $projFolder;
    /**
     * @var boolean
     */
    protected $hasFolderinFS;
    /**
     * @var \stdClass
     */
    protected $settings;

    protected function __construct(Project $project, $projFolder)
    {
        $this->project = $project;
        // To avoid calling getters multiple times later... Note: currentFile isn't initialized correctly yet at this point.
        $this->projID = $project->getID();
        $this->projPrgmName = $project->getInternalName();
        $this->projFolder = $projFolder;
        $this->hasFolderinFS = is_dir($projFolder);
    }

    final private function callFSHelperWithAction($action = '')
    {
        if ($action === '')
        {
            return -1;
        }
        $cmd = escapeshellcmd('sudo -u pbbot ' . (__DIR__ . '/_shared/fs_helper.sh') . ' ' . $this->projID . ' ' . $action);
        ob_start(); system($cmd, $code); ob_clean();
        clearstatcache();

        return $code;
    }

    final protected function createProjectDirectory()
    {
        $ret = $this->callFSHelperWithAction('createproj');
        $this->hasFolderinFS = is_dir($this->projFolder);
        return $this->hasFolderinFS ? PBStatus::OK : PBStatus::Error("Could not create project folder (ret = {$ret})");
    }

    final protected function createProjectDirectoryIfNeeded()
    {
        if (!$this->hasFolderinFS)
        {
            return $this->createProjectDirectory();
        }
        return PBStatus::OK;
    }

    final protected function forkProject($newID)
    {
        $this->createProjectDirectoryIfNeeded();
        $ret = $this->callFSHelperWithAction('clone ' . $newID);
        $this->hasFolderinFS = is_dir($this->projFolder . '../' . $newID);
        return $this->hasFolderinFS ? PBStatus::OK : PBStatus::Error("Could not create cloned project folder (ret = {$ret})");
    }

    final protected function setDirty()
    {
        $this->createProjectDirectoryIfNeeded();
        $ret = $this->callFSHelperWithAction('setdirty');
        return ($ret === 0) ? PBStatus::OK : PBStatus::Error("Could not setDirty project folder (ret = {$ret})");
    }

    final protected function deleteProjectDirectory()
    {
        $this->hasFolderinFS = is_dir($this->projFolder);
        if ($this->hasFolderinFS) {
            $ret = $this->callFSHelperWithAction('deleteProj');
            $this->hasFolderinFS = is_dir($this->projFolder);
            return (!$this->hasFolderinFS) ? PBStatus::OK : PBStatus::Error("Could not delete project folder (ret = {$ret})");
        }
        return PBStatus::OK;
    }

    /**
     * @return string[]
     */
    abstract protected function getAvailableFiles();

    abstract protected function addFile($fileName, $content = '');
    abstract protected function renameFile($oldName, $newName);
    abstract protected function deleteCurrentFile();

    final public function getSettings() { return $this->settings; }
    abstract protected function setSettings(array $params = []);

    /**
     * @param   UserInfo    $user
     * @param   array       $params
     * Note: Security checks should be good now.
     * @return  mixed       Depends on the action. Or nothing (die) if download.
     */
    abstract protected function doUserAction(UserInfo $user, array $params);

}
