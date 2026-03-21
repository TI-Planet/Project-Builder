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
    private const STORED_FS_ROOT = '/home/pbbot/pbprojects';
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

    public static function getWebPublicIconURL(string $projID)
    {
        return is_readable(self::STORED_FS_ROOT . "/{$projID}/icon.png") ? "/pb/projects/{$projID}/icon.png"
                                                                         : Project::PROJECT_ICON_URL_FALLBACK;
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

    final protected function readFileIfReadable(string $path)
    {
        if (!is_readable($path))
        {
            return null;
        }
        $content = @file_get_contents($path);
        return ($content !== false) ? $content : null;
    }

    final protected function getFileContentsWithFallback(string $path, string $fallbackPath = '')
    {
        $content = $this->readFileIfReadable($path);
        if ($content !== null)
        {
            return $content;
        }
        if ($fallbackPath !== '')
        {
            return $this->readFileIfReadable($fallbackPath);
        }
        return null;
    }

    final protected function getCurrentProjectSourceFilePath()
    {
        return $this->projFolder . 'src/' . $this->project->getCurrentFile();
    }

    final protected function getExistingFilePathWithFallback(string $path, string $fallbackPath = '')
    {
        return file_exists($path) ? $path : $fallbackPath;
    }

    final protected function getFileHashWithFallback(string $path, string $fallbackPath = '')
    {
        $content = $this->getFileContentsWithFallback($path, $fallbackPath);
        return ($content !== null) ? hash('sha256', $content) : null;
    }

    final protected function validateExpectedFileHash(string $expectedHash, string $nextContent, string $path, string $fallbackPath)
    {
        if (empty($expectedHash))
        {
            return true;
        }

        $expectedHash = strtolower(trim($expectedHash));
        if (preg_match('/^[a-f0-9]{64}$/', $expectedHash) !== 1)
        {
            return PBStatus::Error('Bad base source hash');
        }

        $currentHash = $this->getFileHashWithFallback($path, $fallbackPath);

        if (empty($currentHash))
        {
            return PBStatus::Error("Couldn't validate the current server file before saving");
        }

        if (hash_equals($currentHash, $expectedHash) ||
            hash_equals($currentHash, hash('sha256', $nextContent)))
        {
            return true;
        }

        return PBStatus::Error('This file changed on the server since you opened it. Reload and merge before saving again.');
    }

    final protected function atomicWriteFile(string $path, string $content)
    {
        $dir = dirname($path);
        if (!is_dir($dir))
        {
            return false;
        }

        $tmpPath = @tempnam($dir, basename($path) . '.tmp.');
        if ($tmpPath !== false)
        {
            if (@file_put_contents($tmpPath, $content, LOCK_EX) === false)
            {
                @unlink($tmpPath);
                return false;
            }

            if (file_exists($path))
            {
                @chmod($tmpPath, fileperms($path) & 0777);
            }

            if (@rename($tmpPath, $path))
            {
                clearstatcache(true, $path);
                return true;
            }

            @unlink($tmpPath);
            // fallthrough
        }

        // Fallback: direct write (maybe the directory not writable by PHP but the target file is)
        $ok = @file_put_contents($path, $content, LOCK_EX);
        if ($ok !== false)
        {
            clearstatcache(true, $path);
        }

        return $ok !== false;
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
    abstract public function getCurrentFileSourceHTML();
    abstract public function getCurrentFileMtime();
    abstract public function getCurrentFileSourceHash();

    final public function getSettings() { return $this->settings; }
    abstract protected function setSettings(array $params = []);

    protected function getCtags(array $files) { return ''; }
    protected function getSDKCtags() { return ''; }
    protected function getAnalysis($src_file) { return []; }

    /**
     * May die/exit in certain cases (download...)
     * @return mixed
     */
    abstract protected function doUserAction(UserInfo $user, array $params);

    final protected function handleGlobalProjectAction(UserInfo $user, array $params)
    {
        if (empty($params['action']))
        {
            return PBStatus::Error('No action parameter given');
        }

        switch ($params['action'])
        {
            case 'getSrcFileContent':
                if (!($this->project->getAuthorID() === $user->getID() || $user->isModeratorOrMore() || $this->project->isMultiuser()))
                {
                    return PBStatus::Error('Unauthorized');
                }
                if (empty($params['fileName']) || !$this->project::isFileNameOK($params['fileName']))
                {
                    return PBStatus::Error('No/Bad fileName parameter given');
                }
                if (!in_array($params['fileName'], $this->getAvailableSrcFiles()))
                {
                    return PBStatus::Error('No such source file');
                }
                $filePath = $this->projFolder . 'src/' . $params['fileName'];
                return file_get_contents($filePath);

            case 'getAllSrcFilesContent':
                if (!($this->project->getAuthorID() === $user->getID() || $user->isModeratorOrMore() || $this->project->isMultiuser()))
                {
                    return PBStatus::Error('Unauthorized');
                }
                $data = [];
                $ignoredFile = $params['except'] ?? '';
                foreach ($this->getAvailableSrcFiles() as $srcFileName)
                {
                    if ($srcFileName === $ignoredFile) { continue; }
                    $filePath = $this->projFolder . 'src/' . $srcFileName;
                    $data[$srcFileName] = @file_get_contents($filePath);
                }
                return $data;

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
