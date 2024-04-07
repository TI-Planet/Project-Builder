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

abstract class PHPBasedBackend extends IBackend
{
    protected function addFile($fileName, $content = '')
    {
        $status = $this->createProjectDirectoryIfNeeded();
        if ($status !== PBStatus::OK)
        {
            return $status;
        }
        $filePath = $this->projFolder . 'src/' . $fileName;
        if (file_exists($filePath))
        {
            return PBStatus::Error('This file already exists');
        }
        $ok = file_put_contents($filePath, $content);
        return ($ok !== false) ? PBStatus::OK : PBStatus::Error("File couldn't be created");
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
        $ret = rename($this->projFolder . 'src/' . $oldName, $this->projFolder . 'src/' . $newName);
        $ok = (!file_exists($this->projFolder . 'src/' . $oldName)) && is_writable($this->projFolder . 'src/' . $newName);
        return $ok ? PBStatus::OK : PBStatus::Error("File couldn't be renamed (ret = {$ret})");
    }

    final protected function deleteCurrentFile()
    {
        $filePath = $this->projFolder . 'src/' . $this->project->getCurrentFile();
        if ($this->hasFolderinFS && file_exists($filePath))
        {
            $ret = unlink($filePath);
            $ok = !file_exists($filePath);
            return $ok ? PBStatus::OK : PBStatus::Error("File couldn't be deleted (ret = {$ret})");
        }
        return PBStatus::OK;
    }
}
