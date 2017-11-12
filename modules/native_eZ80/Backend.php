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

require_once __DIR__ . '/../NativeBasedBackend.class.php';


final class native_eZ80ProjectBackend extends NativeBasedBackend
{
    const TEMPLATE_FILE_PATH = '/../../projects/template/main.c';

    public function __construct(native_eZ80Project $project, $projFolder)
    {
        parent::__construct($project, $projFolder, __DIR__ . '/internal/builder.sh');

        $settingsFromJsonOK = is_readable($this->projFolder . 'config.json') ? json_decode(file_get_contents($this->projFolder . 'config.json')) : [];
        $this->settings = (!empty((array)$settingsFromJsonOK)) ? $settingsFromJsonOK : (object)[
            'outputFormat' => 'program',
            'outputLoc'    => 'ram',
            'optFor'       => 'speed',
            'flashFuncs'   => 'YES',
            'clangArgs'    => '-W -Wall -Wwrite-strings -Wno-incompatible-library-redeclaration -Wno-main-return-type -Ddouble=float -Dreentrant='
        ];

        $this->projPrgmExtension = $this->settings->outputFormat === 'program' ? '8xp' : '8xv';
    }

    public function getAvailableFiles()
    {
        $availableFiles = array_filter(array_map('basename', glob($this->projFolder . '*.*')), '\ProjectBuilder\native_eZ80Project::isFileNameOK');
        sort($availableFiles); // TODO: custom sort so that header files appear just before their implementation file
        return $availableFiles;
    }

    public function doUserAction(UserInfo $user, array $params)
    {
        $retParent = parent::handleGlobalProjectAction($user, $params);
        if ($retParent !== self::doUserAction_Unhandled_Action)
        {
            return $retParent;
        }

        /** @var native_eZ80Project $thisProject */
        $thisProject = &$this->project;

        $action = $params['action'];

        // Ready now.
        $this->projCurrFile = $thisProject->getCurrentFile();

        // In this switch, we only check high-level permissions (for special actions) and parameters format etc.
        // The underlying validity of parameters (e.g if file exists, etc.) will have to be checked by the called functions.
        switch ($action)
        {
            case 'getBuildLog':
                return $this->getBuildLog();

            case 'getCheckLog':
                return $this->getCheckLog();

            case 'getCtags':
                $files = [ $this->projCurrFile ];
                if (isset($params['scope']) && !empty($params['scope']))
                {
                    $scope = $params['scope'];
                    if ($scope === 'all') {
                        $files = $thisProject->getAvailableFiles();
                    } elseif (in_array($scope, $thisProject->getAvailableFiles(), true)) {
                        $files = [ $scope ];
                    }
                }
                return $this->getCtags($files);

            case 'getSDKCtags':
                return $this->getSDKCtags();

            case 'getCurrentSrc':
                return $this->getCurrentSrc();

            case 'renameFile':
                if (   (!isset($params['oldName']) || empty($params['oldName']))
                    && (!isset($params['newName']) || empty($params['newName'])) )
                {
                    return PBStatus::Error('No file name(s) given');
                }
                if (!native_eZ80Project::isFileNameOK($params['oldName']) || !native_eZ80Project::isFileNameOK($params['newName']))
                {
                    return PBStatus::Error('Bad file name(s)');
                }
                return $this->renameFile($params['oldName'], $params['newName']);

            case 'addFile':
                if (!isset($params['fileName']) || empty($params['fileName']))
                {
                    return PBStatus::Error('No file name given');
                }
                if (!native_eZ80Project::isFileNameOK($params['fileName']))
                {
                    return PBStatus::Error('Bad file name');
                }
                return $this->addFile($params['fileName']);

            case 'deleteCurrentFile':
                if (!($thisProject->isMulti_ReadWrite() || $thisProject->getAuthorID() === $user->getID() || $user->isModeratorOrMore()))
                {
                    return PBStatus::Error('Unauthorized');
                }
                if (!isset($params['file']) || empty($params['file']))
                {
                    return PBStatus::Error('No file name given');
                }
                if (!native_eZ80Project::isFileNameOK($params['file']))
                {
                    return PBStatus::Error('Bad file name given');
                }
                if (count($thisProject->getAvailableFiles()) === 1)
                {
                    return PBStatus::Error('Cannot delete the only remaining file');
                }
                $thisProject->removeFromAvailableFilesList($params['file']);
                return $this->deleteCurrentFile();

            case 'download':
                $this->download();
                return PBStatus::OK; // unreachable

            case 'save':
                if (!isset($params['file']))
                {
                    return PBStatus::Error('No file name given');
                }
                if (!native_eZ80Project::isFileNameOK($params['file']))
                {
                    return PBStatus::Error('Bad file name given');
                }
                if (!isset($params['source']))
                {
                    return PBStatus::Error('No source code given');
                }
                return $this->saveSource($params['source']);

            case 'clean':
                return $this->clean();

            case 'build':
                return $this->build(false);

            case 'llvmbuild':
                return $this->build(true);

            case 'llvm':
                if (!isset($params['file']) || empty($params['file']))
                {
                    return PBStatus::Error('No file name given');
                } else {
                    if (!native_eZ80Project::isFileNameOK($params['file']))
                    {
                        return PBStatus::Error('Bad file name given');
                    }
                }
                return $this->llvm($params['file']);

            case 'getAnalysis':
                if (!isset($params['file']) || empty($params['file']))
                {
                    return PBStatus::Error('No file name given');
                } else {
                    if (!native_eZ80Project::isFileNameOK($params['file']))
                    {
                        return PBStatus::Error('Bad file name given');
                    }
                }
                return $this->getAnalysis($params['file']);

            case 'reindent':
                if (!isset($params['file']) || empty($params['file']))
                {
                    return PBStatus::Error('No file name given');
                }
                if (!native_eZ80Project::isFileNameOK($params['file']))
                {
                    return PBStatus::Error('Bad file name given');
                }
                return $this->reindentFile($params['file']);

            case 'build_dl':
                $this->build();
                $this->download();
                return PBStatus::OK; // unreachable

            case 'downloadZipExport':
                $this->downloadZipExport();
                return PBStatus::OK; // unreachable

            case 'downloadHexFile':
                $this->downloadHexFile();
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

    private function getRawBuildLog($ofLLVM = false)
    {
        if (!$this->hasFolderinFS) {
            return '';
        }
        $output = @file_get_contents($this->projFolder . ($ofLLVM ? 'output_llvm_build.txt' : 'output.txt'));
        return ($output !== false) ? $output : 'No Build Log! Have you built yet?';
    }

    private function getBuildLog($ofLLVM = false)
    {
        return explode($ofLLVM ? "\n" : "\r\n", $this->cleanLog($this->getRawBuildLog($ofLLVM), $ofLLVM ? 'build_clang' : 'build_zds'));
    }

    private function getRawAnalysisLog()
    {
        if (!$this->hasFolderinFS) {
            return '';
        }
        $output = @file_get_contents($this->projFolder . 'output_llvm_syntax.txt');
        return ($output !== false) ? $output : 'No LLVM Analysis Log! Hmm?';
    }

    private function getAnalysisLog()
    {
        return explode("\n", $this->cleanLog($this->getRawAnalysisLog(), 'analysis'));
    }

    private function getRawLLVMCompileOutput()
    {
        if (!$this->hasFolderinFS) {
            return '';
        }
        $output = @file_get_contents($this->projFolder . 'output_llvm.txt');
        return ($output !== false) ? $output : 'No LLVM compile log! Hmm?';
    }

    private function getLLVMCompileOutput()
    {
        return explode("\n", $this->cleanLog($this->getRawLLVMCompileOutput(), 'clang'));
    }

    private function getRawCheckLog()
    {
        if (!$this->hasFolderinFS) {
            return '';
        }
        $output = @file_get_contents($this->projFolder . 'cppcheck.txt');
        return ($output !== false) ? $output : 'No cppcheck analysis! Have you built yet?';
    }

    private function getCheckLog()
    {
        return explode("\n", $this->cleanLog($this->getRawCheckLog(), 'cppcheck'));
    }

    private function cleanCtag($tag)
    {
        $isASM = preg_match('/(inc|asm)$/', $tag->path) === 1;

        // Discard some unknown (e)Z80 ASM stuff
        if ($isASM)
        {
            if (  $tag->name === '_' ||
                ( $tag->kind === 'define' && in_array(strtoupper($tag->name), [
                        'ADC', 'ADD', 'AND', 'BIT', 'CALL', 'CCF', 'CP', 'CPD', 'CPDR', 'CPI', 'CPIR', 'CPL', 'DAA',
                        'DEC', 'DI', 'DJNZ', 'EI', 'EX', 'EXX', 'HALT', 'IM', 'IN', 'IN0', 'INC', 'IND', 'IND2', 'IND2R', 'INDM', 'INDMR', 'INDR', 'INI',
                        'INI2', 'INI2R', 'INIM', 'INIMR', 'INIR', 'JP', 'JR', 'LD', 'LDD', 'LDDR', 'LDI', 'LDIR', 'LEA', 'MLT', 'NEG', 'NOP', 'OR', 'ORI2R',
                        'OTD', 'OTD2', 'OTD2R', 'OTDM', 'OTDMR', 'OTDR', 'OTI', 'OTI2', 'OTIM', 'OTIMR', 'OTIR', 'OUT', 'OUT0', 'PEA', 'POP', 'PUSH', 'RES',
                        'RET', 'RETI', 'RETN', 'RL', 'RLA', 'RLC', 'RLCA', 'RR', 'RRA', 'RRC', 'RRCA', 'RRD', 'RSMIX', 'RST', 'SBC', 'SCF', 'SET', 'SLA', 'SLP',
                        'SRA', 'SRL', 'STMIX', 'SUB', 'TSR', 'TST', 'XOR' ], true) ) )
            {
                return null;
            }
        }

        // discard internal stuff
        if ($tag->kind === 'macro' && strpos($tag->name, '__') === 0)
        {
            return null;
        }

        // Remove the path (keep filename only)
        $tag->path = @pathinfo($tag->path)['basename'];
        if (!$tag->path) {
            $tag->path = 'unknown';
        }

        // Clean up some things...
        if ($tag->path === 'tice.h')
        {
            static $remove_tice_tags_after = false;
            if ($remove_tice_tags_after && (0 !== strpos($tag->name, 'sk_'))) {
                return null;
            }
            if ($tag->name === 'os_uXMin') // bleh
            {
                $remove_tice_tags_after = true;
                return null;
            }
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
        if ($type === 'build_zds' || $type === 'build_clang')
        {
            // Wine stuff
            $log = str_replace("Application tried to create a window, but no driver could be loaded.\n", '', $log);
            $log = str_replace("Make sure that your X server is running and that \$DISPLAY is set correctly.\n", '', $log);
            $log = str_replace("err:module:load_builtin_dll failed to load .so lib for builtin L\"winex11.drv\": libXext.so.6: cannot open shared object file: No such file or directory\n", '', $log);
            $log = str_replace("Unknown error (127).\n", '', $log);
        }

        if ($type === 'analysis')
        {
            $log = preg_replace('/^\s+.*\n/m', '', $log);
        }

        return $log;
    }

    private function getCtags(array $files)
    {
        if (!$this->hasFolderinFS) {
            return '';
        }
        $fileList = implode(' ', $files);
        chdir($this->projFolder);
        exec("ctags -u --fields=nktSZ --c-kinds=+defgpstuvxml --langmap=ASM:+.inc --output-format=json {$fileList}", $tagList, $retval);
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
        $cacheFile = __DIR__ . '/internal/sdk_ctags.json';
        if (is_readable($cacheFile) && filemtime($cacheFile) > time()-24*60*60) {
            return json_decode(file_get_contents($cacheFile));
        }
        chdir($this->projFolder);
        $fileList = '$(find ' . __DIR__ . '/internal/toolchain/include/ -name \'*.c\' -o -name \'*.h\' | grep -v TINYSTL)';
        exec("ctags -u --fields=nktSZ --c-kinds=+defgpstuvxm --output-format=json {$fileList}", $tagList, $retval);
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
            file_put_contents($cacheFile, json_encode($tagsObject));
            return $tagsObject;
        }
        return '';
    }

    private function getCurrentSrc()
    {
        $srcFile = $this->projFolder . 'obj/' . @pathinfo($this->projCurrFile, PATHINFO_FILENAME) . '.src';
        if (file_exists($srcFile))
        {
            $output = @file_get_contents($srcFile);
            return ($output !== false) ? $output : null;
        }
        return null;
    }

    protected function addFile($fileName, $content = '')
    {
        if (file_exists($this->projFolder . $fileName))
        {
            return PBStatus::Error('This file already exists');
        }

        $implExts = [ 'c', 'cpp', 'asm' ];
        $fNameNoExt = pathinfo($fileName)['filename'];
        $fExt = strtolower(pathinfo($fileName)['extension']);
        if (in_array($fExt, $implExts, true)) // if new file is implementation
        {
            /** @var native_eZ80Project $thisProject */
            $thisProject = &$this->project;

            $availFiles = $thisProject->getAvailableFiles();
            $availFiles = array_map('strtolower', $availFiles);
            foreach ($implExts as $implExt)
            {
                $fullOtherFileName = $fNameNoExt . '.' . $implExt;
                if (in_array(strtolower($fullOtherFileName), $availFiles, true))
                {
                    return PBStatus::Error('This common name is already in use for an implementation');
                }
            }
        }

        $content = (!empty($content)) ? $content : ((($fExt === 'asm' || $fExt === 'inc') ? ';' : '//') . ' Your code here');
        return parent::addFile($fileName, $content);
    }

    /**
     * Warning: dies.
     */
    private function download()
    {
        $target = $this->projPrgmName . '.' . $this->projPrgmExtension;
        $targetPath = $this->projFolder . 'bin/' . $target;

        if (file_exists($targetPath))
        {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $target . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($targetPath));
            readfile($targetPath);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<b>Error: There is no target file to download. Did you build succesfully?</b><br/>';
            echo '<pre>' . htmlentities($this->getRawBuildLog(), ENT_QUOTES) . '</pre>';
        }
        die(); // meh
    }

    /**
     * Warning: dies.
     */
    private function downloadZipExport()
    {
        /** @var native_eZ80Project $thisProject */
        $thisProject = &$this->project;

        chdir($this->projFolder);
        $zip = new \ZipArchive();
        $zipFileName = $this->projPrgmName . '_source_' . time();
        $zipFilePath = '/tmp/php_pb_export_' . $this->projID . '.zip';
        if (file_exists($zipFilePath))
        {
            unlink($zipFilePath);
        }
        if (!$zip->open($zipFilePath, \ZipArchive::CREATE)) {
            die(PBStatus::Error('Could not create the .zip file... Retry?'));
        }
        $zip->addEmptyDir($zipFileName);
        $files = $thisProject->getAvailableFiles();
        foreach($files as $file)
        {
            if ($zip->addFromString($zipFileName . '/' . basename($file), file_get_contents($file)) === false)
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


    /**
     * Warning: dies.
     */
    private function downloadHexFile()
    {
        $target = $this->projPrgmName . '.hex';
        $targetPath = $this->projFolder . 'bin/' . $target;

        if (file_exists($targetPath))
        {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $target . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($targetPath));
            readfile($targetPath);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<b>Error: There is no hex file to download. Did you build succesfully?</b><br/>';
            echo '<pre>' . htmlentities($this->getRawBuildLog(), ENT_QUOTES) . '</pre>';
        }
        die(); // meh
    }

    private function saveSource($source)
    {
        if (mb_strlen($source) > 1024*1024)
        {
            return PBStatus::Error("Couldn't save such a big content (Max = 1 MB)");
        }
        $this->createProjectDirectoryIfNeeded();
        $ok = file_put_contents($this->projFolder . $this->projCurrFile, $source);
        if ($ok !== false)
        {
            return $this->setDirty();
        }
        return PBStatus::Error("Couldn't save source to current file");
    }

    private function llvm($src_file)
    {
        $this->createProjectDirectoryIfNeeded();

        $this->callNativeHelperWithAction('llvm ' . $src_file);

        return $this->getLLVMCompileOutput();
    }

    private function getAnalysis($src_file)
    {
        if (!$this->hasFolderinFS)
        {
            return [];
        }

        $this->callNativeHelperWithAction('llvmsyntax ' . $src_file);

        return $this->getAnalysisLog();
    }

    private function reindentFile($src_file)
    {
        if (preg_match('/\.[chp]+$/i', $src_file) !== 1)
        {
            return PBStatus::Error('Re-indenting only works on C/C++ code');
        }
        $this->createProjectDirectoryIfNeeded();

        if (!(chdir($this->projFolder) && is_readable($src_file)))
        {
            return PBStatus::Error("Couldn't read the input file");
        }
        exec("astyle -q --options=none -n -s4 --keep-one-line-statements --keep-one-line-blocks {$src_file}", $out, $retval);
        if ($retval === 0)
        {
            $output = @file_get_contents($src_file);
            return ($output !== false) ? $output : null;
        }
        return PBStatus::Error("Error while re-indenting the file ($retval)");
    }

    private function build($withLLVM = false)
    {
        $this->createProjectDirectoryIfNeeded();

        if ($withLLVM)
        {
            $this->clean();
        }

        $targetPath = $this->projFolder . 'bin/' . $this->projPrgmName . '.' . $this->projPrgmExtension;
        $dirtyPath  = $this->projFolder . '.dirty';
        if (!file_exists($targetPath) || file_exists($dirtyPath))
        {
            $this->callNativeHelperWithAction(($withLLVM ? 'llvmbuild ' : 'build ') . $this->projPrgmName);
        }

        return $this->getBuildLog($withLLVM);
    }

    protected function setSettings(array $params = [])
    {
        if (empty($params)) { return PBStatus::Error('No settings given'); }

        $configPatterns = [
            'outputFormat' => '/^(program|appvar)$/',
            'outputLoc'    => '/^(ram|archive)$/',
            'optFor'       => '/^(speed|size)$/',
            'flashFuncs'   => '/^(YES|NO)$/',
            'clangArgs'    => '/^(?:(?:-(?:[wWDO]|std))[\w=+-]* *)*$/',
        ];

        foreach ($params as $name => $value)
        {
            if ($name === 'action') { continue; }
            if (!array_key_exists($name, $configPatterns))
            {
                return PBStatus::Error("Unknown setting: '$name'");
            }
            if (preg_match($configPatterns[$name], $value) !== 1)
            {
                return PBStatus::Error("Invalid setting value for '$name'.");
            }
            $this->settings->$name = $value;
        }

        $this->createProjectDirectoryIfNeeded();
        file_put_contents($this->projFolder . 'config.json', json_encode($this->settings, JSON_PRETTY_PRINT));

        return PBStatus::OK;
    }

    /**
     * @return string
     */
    public function getCurrentFileSourceHTML()
    {
        $sourceFile = $this->projFolder . $this->project->getCurrentFile();
        $whichSource = file_exists($sourceFile) ? $sourceFile : (__DIR__ . self::TEMPLATE_FILE_PATH);
        return htmlentities(file_get_contents($whichSource), ENT_QUOTES);
    }

    /**
     * @return int
     */
    public function getCurrentFileMtime()
    {
        $sourceFile = $this->projFolder . $this->project->getCurrentFile();
        $whichSource = file_exists($sourceFile) ? $sourceFile : (__DIR__ . self::TEMPLATE_FILE_PATH);
        return (int)filemtime($whichSource);
    }

}
