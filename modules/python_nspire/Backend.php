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

require_once __DIR__ . '/../PHPBasedBackend.class.php';


final class python_nspireProjectBackend extends PHPBasedBackend
{
    private const TEMPLATE_PY_FILE_PATH = __DIR__ . '/../../projects/template_py/src/script.py';

    public function __construct(python_nspireProject $project, $projFolder)
    {
        parent::__construct($project, $projFolder);
        $this->projPrgmExtension = 'tns';
    }

    public function getAvailableSrcFiles()
    {
        $availableFiles = array_filter(array_map('basename', glob($this->projFolder . 'src/*.*')), '\ProjectBuilder\python_nspireProject::isFileNameOK');
        sort($availableFiles);
        return $availableFiles;
    }

    public function doUserAction(UserInfo $user, array $params)
    {
        /** @noinspection MissUsingParentKeywordInspection */
        $retParent = parent::handleGlobalProjectAction($user, $params);
        if ($retParent !== self::doUserAction_Unhandled_Action)
        {
            return $retParent;
        }

        /** @var python_nspireProject $thisProject */
        $thisProject = &$this->project;

        $action = $params['action'];

        // In this switch, we only check high-level permissions (for special actions) and parameters format etc.
        // The underlying validity of parameters (e.g if file exists, etc.) will have to be checked by the called functions.
        switch ($action)
        {
            case 'getCtags':
                $files = [ $this->project->getCurrentFile() ];
                if (isset($params['scope']) && !empty($params['scope']))
                {
                    $scope = $params['scope'];
                    if ($scope === 'all') {
                        $files = $thisProject->getAvailableSrcFiles();
                    } elseif (in_array($scope, $thisProject->getAvailableSrcFiles(), true)) {
                        $files = [ $scope ];
                    }
                }
                return $this->getCtags($files);

            case 'getSDKCtags':
                return $this->getSDKCtags();

            case 'renameFile':
                if (   (empty($params['oldName']))
                    && (empty($params['newName'])) )
                {
                    return PBStatus::Error('No file name(s) given');
                }
                if (!$this->project::isFileNameOK($params['oldName']) || !$this->project::isFileNameOK($params['newName']))
                {
                    return PBStatus::Error('Bad file name(s)');
                }
                return $this->renameFile($params['oldName'], $params['newName']);

            case 'addFile':
                if (empty($params['fileName']))
                {
                    return PBStatus::Error('No file name given');
                }
                if (!$this->project::isFileNameOK($params['fileName']))
                {
                    return PBStatus::Error('Bad file name');
                }
                return $this->addFile($params['fileName']);

            case 'deleteCurrentFile':
                if (!($thisProject->isMulti_ReadWrite() || $thisProject->getAuthorID() === $user->getID() || $user->isModeratorOrMore()))
                {
                    return PBStatus::Error('Unauthorized');
                }
                if (empty($params['file']))
                {
                    return PBStatus::Error('No file name given');
                }
                if (!($this->project::isFileNameOK($params['file']) || $this->project::isImageFileNameOK($params['file'])))
                {
                    return PBStatus::Error('Bad file name given');
                }
                if (count($thisProject->getAvailableSrcFiles()) === 1)
                {
                    return PBStatus::Error('Cannot delete the only remaining file');
                }
                if (!$this->project->isCurrentFileDeletable())
                {
                    return PBStatus::Error('Current file not deletable');
                }
                $thisProject->removeFromAvailableFilesList($params['file']);
                return $this->deleteCurrentFile();

            case 'save':
                if (!isset($params['file']))
                {
                    return PBStatus::Error('No file name given');
                }
                if (!$this->project::isFileNameOK($params['file']))
                {
                    return PBStatus::Error('Bad file name given');
                }
                if (!isset($params['source']))
                {
                    return PBStatus::Error('No source code given');
                }
                if (!$this->project->isCurrentFileEditable())
                {
                    return PBStatus::Error('Current file not editable');
                }
                return $this->saveSource($params['source']);

            case 'getAnalysis':
                if (empty($params['file']))
                {
                    return PBStatus::Error('No file name given');
                }

                if (!$this->project::isFileNameOK($params['file']))
                {
                    return PBStatus::Error('Bad file name given');
                }
                return $this->getAnalysis($params['file']);

            case 'reindent':
                if (empty($params['file']))
                {
                    return PBStatus::Error('No file name given');
                }
                if (!$this->project::isFileNameOK($params['file']))
                {
                    return PBStatus::Error('Bad file name given');
                }
                return $this->reindentFile($params['file']);

            case 'downloadZipExport':
                $this->downloadZipExport();
                return PBStatus::OK; // unreachable

            case 'setSettings':
                return $this->setSettings($params);

            default:
                return PBStatus::Error('Unknown action received by the backend - nothing done.');

        }
    }


    /**********************************************/
    // All the following methods assume security checks etc. have been made !
    /**********************************************/

    private function cleanCtag($tag)
    {
        // discard internal stuff
        if ($tag->kind === 'macro' && strpos($tag->name, '__') === 0)
        {
            return null;
        }

        // Remove the path (keep filename only)
        $tag->path = preg_replace('@.*?/internal/toolchain/(.*)$@', '\1', $tag->path);
        if (empty($tag->path)) {
            $tag->path = 'unknown';
        }

        // remove typename typeref stuff
        if (isset($tag->typeref))
        {
            $tag->typeref = str_replace('typename:', '', $tag->typeref);
        }

        // Save space
        unset($tag->_type, $tag->pattern);
        if (isset($tag->name)) { $tag->n = $tag->name; unset($tag->name); }
        if (isset($tag->kind)) { $tag->k = $tag->kind; unset($tag->kind); }
        if (isset($tag->line)) { $tag->l = $tag->line; unset($tag->line); }
        if (isset($tag->scope)) { $tag->s = $tag->scope; unset($tag->scope); }
        if (isset($tag->typeref)) { $tag->r = $tag->typeref; unset($tag->typeref); }
        if (isset($tag->signature)) { $tag->a = $tag->signature; unset($tag->signature); }
        $tag = (object)array_filter((array)$tag);

        return $tag;
    }

    private function cleanLog($log = '', $type = null)
    {
        $log = preg_replace('/\/projectbuilder\/projects\/(\d+)_(\d{10})_([a-zA-Z0-9]{10})\/src\//', '', $log);
        $log = preg_replace('/\/home\/pbbot\/pbprojects\/(\d+)_(\d{10})_([a-zA-Z0-9]{10})\/src\//', '', $log);

        return $log;
    }

    private function getCtags(array $files)
    {
        if (!$this->hasFolderinFS) {
            return '';
        }
        $fileList = implode(' ', $files);
        chdir($this->projFolder . 'src');
        exec("ctags -u --fields=FnNktSZ --c-kinds=+defgpstuvxml --language-force=Python --output-format=json {$fileList}", $tagList, $retval);
        if ($retval === 0 && is_array($tagList))
        {
            $tagsObject = [];
            foreach ($tagList as $key => $tag)
            {
                $tag = $this->cleanCtag(json_decode($tag));
                if ($tag !== null)
                {
                    $tagPath = $tag->path;
                    unset($tag->path);
                    $tagsObject[$tagPath][] = $tag;
                }
            }
            return $tagsObject;
        }
        return '';
    }

    private function getSDKCtags()
    {
        // TODO: https://docs.ctags.io/en/latest/man/ctags-lang-python.7.html?highlight=python
        return '';
    }

    protected function addFile($fileName, $content = '')
    {
        if (file_exists($this->projFolder . 'src/' . $fileName))
        {
            return PBStatus::Error('This file already exists');
        }

        $content = (!empty($content)) ? $content : '# Your code here';
        return parent::addFile($fileName, $content);
    }

    /**
     * Warning: dies.
     */
    private function downloadZipExport()
    {
        /** @var python_nspireProject $thisProject */
        $thisProject = &$this->project;

        chdir($this->projFolder);
        $zip = new \ZipArchive();
        $zipFileName = $this->projPrgmName;
        $zipFilePath = '/tmp/php_pb_export_' . $this->projID . '.zip';
        if (file_exists($zipFilePath))
        {
            unlink($zipFilePath);
        }
        if (!$zip->open($zipFilePath, \ZipArchive::CREATE)) {
            die(PBStatus::Error('Could not create the .zip file... Retry?'));
        }

        $zip->addEmptyDir($zipFileName);

        $zip->addEmptyDir($zipFileName . '/src');

        $files = $thisProject->getAvailableSrcFiles();
        foreach($files as $file)
        {
            if ($zip->addFromString($zipFileName . '/src/' . $file, file_get_contents('src/' . $file)) === false)
            {
                die(PBStatus::Error('Could not add source files to the .zip file... Retry?'));
            }
        }

        $zip->close();
        if (file_exists($zipFilePath))
        {
            // delete after 1 minute. For some reason, shell commands directly won't go in the background.
            shell_exec("php -r \"sleep(60); unlink('{$zipFilePath}');\" > /dev/null 2>/dev/null &");

            header('Content-Description: File Transfer');
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $zipFileName . '.zip"');
            header('Content-Length: ' . filesize($zipFilePath));
            readfile($zipFilePath);

            die(); // meh
        }
        die('Internal error creating the .zip file... Retry?');
    }

    private function saveSource($source)
    {
        if (mb_strlen($source) > 1024*1024)
        {
            return PBStatus::Error("Couldn't save such a big content (Max = 1 MB)");
        }
        $this->createProjectDirectoryIfNeeded();
        $ok = file_put_contents($this->projFolder . 'src/' . $this->project->getCurrentFile(), $source);
        return ($ok !== false) ? PBStatus::OK : PBStatus::Error("Couldn't save source to current file");
    }

    private function getAnalysis($src_file)
    {
        if (!$this->hasFolderinFS)
        {
            return [];
        }

        chdir($this->projFolder . 'src');
        exec("pylint -j0 --disable=I,R,C --output-format=json {$src_file}", $analysis, $retval);
        if (is_array($analysis))
        {
            array_map('trim', $analysis);
            return json_decode($this->cleanLog(implode(' ', $analysis)));
        }

        return PBStatus::Error('Could not get analysis');
    }

    private function reindentFile($src_file)
    {
        if (preg_match('/\.py$/i', $src_file) !== 1)
        {
            return PBStatus::Error('Re-indenting only works on python code');
        }
        $this->createProjectDirectoryIfNeeded();

        if (!(chdir($this->projFolder) && is_readable('src/' . $src_file)))
        {
            return PBStatus::Error("Couldn't read the input file");
        }
        //exec("TODO", $out, $retval);
        $retval = 0;
        if ($retval === 0)
        {
            $output = @file_get_contents('src/' . $src_file);
            return ($output !== false) ? $output : null;
        }
        return PBStatus::Error("Error while re-indenting the file ($retval)");
    }

    public function setSettings(array $params = [])
    {
        if (empty($params)) { return PBStatus::Error('No settings given'); }
        // nothing to do...
        return PBStatus::OK;
    }

    /**
     * @return string
     */
    public function getCurrentFileSourceHTML()
    {
        $currFile = $this->project->getCurrentFile();
        $sourceFile = $this->projFolder . 'src/' . $currFile;
        $templateFile = self::TEMPLATE_PY_FILE_PATH;
        $whichSource = file_exists($sourceFile) ? $sourceFile : $templateFile;
        return htmlentities(file_get_contents($whichSource), ENT_QUOTES);
    }

    /**
     * @return int
     */
    public function getCurrentFileMtime()
    {
        $currFile = $this->project->getCurrentFile();
        $sourceFile = $this->projFolder . 'src/' . $currFile;
        $templateFile = self::TEMPLATE_PY_FILE_PATH;
        $whichSource = file_exists($sourceFile) ? $sourceFile : $templateFile;
        return (int)filemtime($whichSource);
    }

    /**
     * @return boolean
     */
    public function hasIconFile()
    {
        return file_exists($this->projFolder . 'icon.png');
    }

    final protected function addIconFile($icon)
    {
        // nothing to do
        return PBStatus::OK;
    }
}
