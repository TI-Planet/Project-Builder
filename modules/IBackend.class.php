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
    const doUserAction_Unhandled_Action = -100;

    protected Project $project;
    protected string $projID;
    protected string $projPrgmName;
    protected string $projPrgmExtension;
    protected string $projFolder;
    protected bool $hasFolderinFS;
    protected \stdClass $settings;

    protected function __construct(Project $project, $projFolder)
    {
        $this->project = $project;
        // To avoid calling getters multiple times later... Note: currentFile isn't initialized correctly yet at this point.
        $this->projID = $project->getPID();
        $this->projPrgmName = $project->getInternalName();
        $this->projFolder = $projFolder;
        $this->hasFolderinFS = is_dir($projFolder);
        $this->settings = (object)[];
    }

    private function callFSHelperWithAction($action = '')
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

    protected function createProjectDirectory()
    {
        $ret = $this->callFSHelperWithAction('createProj ' . $this->project->getType());
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

    // This is just to delete some files at the top of the project dir. Not specific to a kind of project.
    final protected function deleteBaseProjectFile($file)
    {
        $this->hasFolderinFS = is_dir($this->projFolder);
        if ($this->hasFolderinFS) {
            $ret = $this->callFSHelperWithAction('deleteFile ' . $file);
            return ($ret === 0) ? PBStatus::OK : PBStatus::Error("Could not delete project file (ret = {$ret})");
        }
        return PBStatus::OK;
    }

    /**
     * @return string[]
     */
    abstract protected function getAvailableSrcFiles();
    protected function getAvailableBinFiles() { return []; }

    abstract protected function addFile($fileName, $content = '');
    abstract protected function addIconFile($icon);
    abstract protected function renameFile($oldName, $newName);
    abstract protected function deleteCurrentFile();

    final public function getSettings() { return $this->settings; }
    abstract protected function setSettings(array $params = []);

    /**
     * May die/exit in certain cases (download...)
     * @return mixed
     */
    abstract protected function doUserAction(UserInfo $user, array $params);

    final protected function handleGlobalProjectAction(UserInfo $user, array $params)
    {
        // Until we decide what to do...
        if ($user->isAnonymous())
        {
            die('Not yet open to non-logged-in TI-Planet members');
        }

        if (empty($params['action']))
        {
            return PBStatus::Error('No action parameter given');
        }

        switch ($params['action'])
        {
            case 'deleteProj':
                if (!($this->project->getAuthorID() === $user->getID() || $user->isModeratorOrMore()))
                {
                    return PBStatus::Error('Unauthorized');
                }
                return $this->deleteProjectDirectory();

            case 'fork':
                if (!isset($params['fork_newpid']))
                {
                    return PBStatus::Error('Internal error when trying to fork the project (no fork_newpid)');
                }
                if (preg_match('/^(\d+)_(\d{10})_([a-zA-Z0-9]{10})$/', $params['fork_newpid']) !== 1)
                {
                    return PBStatus::Error('Internal error when trying to fork the project (bad fork_newpid)');
                }
                return $this->forkProject($params['fork_newpid']);
        }

        return self::doUserAction_Unhandled_Action; // "continue" processing in child classes
    }

}
