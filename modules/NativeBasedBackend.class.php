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

require_once 'IBackend.class.php';


/**
 * This class handles the default (and sometimes final) implementations of the generic methods
 * related to project file management. It calls a native helper (e.g. bash script) to do the actual actions.
 */
abstract class NativeBasedBackend extends IBackend
{
    /**
     * @var string
     */
    private $nativeHelperPath;

    protected function __construct(Project $project, $projFolder, $nativeHelperPath)
    {
        parent::__construct($project, $projFolder);
        $this->nativeHelperPath = $nativeHelperPath;
    }

    final protected function callNativeHelperWithAction($action = '')
    {
        if ($action === '')
        {
            return -1;
        }
        $cmd = escapeshellcmd('sudo -u pbbot ' . $this->nativeHelperPath . ' ' . $this->projID . ' ' . $action);
        ob_start(); system($cmd, $code); ob_clean();
        clearstatcache();

        return $code;
    }

    final protected function clean()
    {
        if ($this->hasFolderinFS)
        {
            $ret = $this->callNativeHelperWithAction('clean');
            return ($ret === 0) ? PBStatus::OK : PBStatus::Error("Could not clean project (ret = {$ret})");
        }
        return PBStatus::OK;
    }

    // This one isn't final in case the backend needs to do some other things before
    protected function addFile($fileName, $content = '')
    {
        $status = $this->createProjectDirectoryIfNeeded();
        if ($status !== PBStatus::OK)
        {
            return $status;
        }
        if (file_exists($this->projFolder . 'src/' . $fileName))
        {
            return PBStatus::Error('This file already exists');
        }
        $ret = $this->callNativeHelperWithAction('addfile src/' . $fileName);
        $ok = file_put_contents($this->projFolder . 'src/' . $fileName, $content);
        return ($ok !== false) ? PBStatus::OK : PBStatus::Error("File couldn't be created (ret = {$ret})");
    }

    protected function addIconFile($iconB64)
    {
        if (mb_strlen($iconB64) > 1024*1024)
        {
            return PBStatus::Error("Couldn't save such a big content (Max = 1 MB)");
        }
        $status = $this->createProjectDirectoryIfNeeded();
        if ($status !== PBStatus::OK)
        {
            return $status;
        }
        $decoded = base64_decode($iconB64);
        if ($decoded === false) {
            return PBStatus::Error("Couldn't decode icon Base64");
        }
        $ret = $this->callNativeHelperWithAction('addfile icon.png');
        $ok = file_put_contents($this->projFolder . 'icon.png', $decoded);
        return ($ok !== false) ? PBStatus::OK : PBStatus::Error("Icon couldn't be saved (ret = {$ret})");
    }

    final protected function renameFile($oldName, $newName)
    {
        if (!file_exists($this->projFolder . 'src/' . $oldName))
        {
            return PBStatus::Error("This old file doesn't exist");
        }
        if (file_exists($this->projFolder . 'src/' . $newName))
        {
            return PBStatus::Error('This new file already exists');
        }
        $ret = $this->callNativeHelperWithAction('renamefile src/' . $oldName . ' src/' . $newName);
        $ok = (!file_exists($this->projFolder . 'src/' . $oldName)) && is_writable($this->projFolder . 'src/' . $newName);
        return $ok ? PBStatus::OK : PBStatus::Error("File couldn't be renamed (ret = {$ret})");
    }

    final protected function deleteCurrentFile()
    {
        if ($this->hasFolderinFS && file_exists($this->projFolder . 'src/' . $this->project->getCurrentFile()))
        {
            $ret = $this->callNativeHelperWithAction('deletefile src/' . $this->project->getCurrentFile());
            $ok = !file_exists($this->projFolder . 'src/' . $this->project->getCurrentFile());
            return $ok ? PBStatus::OK : PBStatus::Error("File couldn't be deleted (ret = {$ret})");
        }
        return PBStatus::OK;
    }

}
