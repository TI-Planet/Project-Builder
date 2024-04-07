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

// TODO: constify projects folder somewhere up the class hierarchy

final class native_eZ80Project extends Project
{
    const PROJECT_MODULE_NAME        = 'C/C++ IDE for the TI CE calculators';
    const PROJECT_MODULE_DESCRIPTION = 'C/C++ IDE for the TI-84 Plus CE / TI-83 Premium CE';

    const REGEXP_GOOD_FILE_PATTERN = "/^([a-z0-9_]+)\\.(c|cpp|h|hpp|asm|inc|yaml)$/i";
    const TEMPLATE_FILE            = 'main.c';

    const REGEXP_GOOD_IMAGE_FILE_PATTERN = "/^([a-z0-9_]+)\\.(png|bmp)$/i";

    private native_eZ80ProjectBackend $backend;

    private array $availableSrcFiles;
    private array $availableBinFiles;
    private array $availableGfxImageFiles;

    public function __construct($db_id, $pid, UserInfo $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime)
    {
        parent::__construct($db_id, $pid, $author, $type, $name, $internalName, $multiuser, $readonly, $chatEnabled, $cTime, $uTime);

        require_once 'Backend.php';
        $this->backend = new native_eZ80ProjectBackend($this, $this->projDirectory);

        $this->availableBinFiles = $this->backend->getAvailableBinFiles();
        $this->availableSrcFiles = $this->backend->getAvailableSrcFiles();
        $this->availableGfxImageFiles = $this->backend->getAvailableGfxImageFiles();
        if (count($this->availableSrcFiles) === 0)
        {
            // just to correctly handle things at template creation (ie, there's no directory in the FS until a first save/build)
            $this->availableSrcFiles = [ self::TEMPLATE_FILE ];
        }
        $this->currentFile = $this->availableSrcFiles[0];
    }

    public static function isPrgmNameOK($fileName = '')
    {
        return preg_match('/^[A-Z][A-Z0-9]{0,7}$/', $fileName);
    }

    public static function isFileNameOK($fileName = '')
    {
        return preg_match(self::REGEXP_GOOD_FILE_PATTERN, $fileName)
            || (strpos($fileName, 'gfx/') === 0 && preg_match(self::REGEXP_GOOD_FILE_PATTERN, substr($fileName, 4)));
    }

    public static function isImageFileNameOK($fileName = '')
    {
        return preg_match(self::REGEXP_GOOD_IMAGE_FILE_PATTERN, $fileName)
            || (strpos($fileName, 'gfx/') === 0 && preg_match(self::REGEXP_GOOD_IMAGE_FILE_PATTERN, substr($fileName, 4)));
    }

    public function isCurrentFileEditable()
    {
        return preg_match('/^gfx\/.*\.[ch]$/i', $this->currentFile) !== 1;
    }

    // Not allowed to rename gfx/ files
    public function isCurrentFileRenamable()
    {
        return strpos($this->currentFile, 'gfx/') === false;
    }

    // We don't allow deleting anything in gfx/ unless it's image files.
    public function isCurrentFileDeletable()
    {
        $gfxPos = strpos($this->currentFile, 'gfx/');
        return $gfxPos === false || ($gfxPos === 0 && preg_match(self::REGEXP_GOOD_IMAGE_FILE_PATTERN, substr($this->currentFile, 4)));
    }

    public function hasGfxFiles()
    {
        return $this->backend->hasGfxFiles();
    }

    /****************************************************/
    /* Getters
    /****************************************************/

    /**
     * @return string
     */
    public function getCurrentFile()
    {
        return $this->currentFile;
    }

    /**
     * @return string
     */
    public function getIconURL()
    {
        return $this->backend->hasIconFile() ? ('/pb/projects/' . $this->getPID() . '/icon.png')
                                             : Project::PROJECT_ICON_URL_FALLBACK;
    }

    /**
     * @return string[]
     */
    public function getAvailableSrcFiles()
    {
        return $this->availableSrcFiles;
    }

    /**
     * @return string[]
     */
    public function getAvailableBinFiles()
    {
        return $this->availableBinFiles;
    }

    /**
     * @return string[]
     */
    public function getAvailableGfxImageFiles()
    {
        return $this->availableGfxImageFiles;
    }

    /**
     * @return string
     */
    public function getFileListHTML()
    {
        $fileListHTML = '';
        $filesCount = count($this->availableSrcFiles);
        $gfxTakenCareOk = false;

        foreach ($this->availableSrcFiles as $i => $file)
        {
            if (!$gfxTakenCareOk)
            {
                $maybeBoldStyle = $this->currentFile === $file ? 'style="font-weight:bold;"' : '';
                $currentGfxFileDisplay = '';
                $gfxButtonBorder = 1;
                if (strpos($this->currentFile, 'gfx/') === 0)
                {
                    $gfxButtonBorder = 2;
                    $currentGfxFileDisplay = '/<b>' . substr($this->currentFile, 4) . '</b>';
                }
                ?>
                <li class="active tabover">
                    <div class="btn-group">
                        <button class="btn btn-default btn-xs dropdown-toggle" style="padding: 2px 10px; border-bottom: #e0e0e0 <?= $gfxButtonBorder ?>px solid; border-radius: 4px 4px 0 0;" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="glyphicon glyphicon-picture" style="top: 2px; left: -1px;"></span> gfx<?= $currentGfxFileDisplay ?> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                        <?php
                        if ($this->hasGfxFiles())
                        {
                        ?>
                            <li class="menu-item dropdown dropdown-submenu">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Image management</a>
                                <ul class="dropdown-menu">
                                    <?php
                                    foreach ($this->availableGfxImageFiles as $gfxImageFile)
                                    {
                                        $gfxImageFile = substr($gfxImageFile, 4);
                                        echo "<li class='menu-item dropdown dropdown-submenu'>
                                                <a href='#' style='padding-left: 10px;'><span class='gfxImgPreviewSpan'><img alt='preview' title='preview' src='/pb/projects/" . $this->getPID() . "/src/gfx/${gfxImageFile}'/></span> {$gfxImageFile}</a>
                                                <ul class='dropdown-menu'>
                                                    <li class='menu-item'><a href='#' onclick='proj.currFile = \"gfx/{$gfxImageFile}\"; deleteCurrentFile(); return false;'><span class='glyphicon glyphicon-trash' style='top: 2px; left: -1px;'></span> Delete image</a></li>
                                                    <li class='menu-item'><a href='/pb/projects/" . $this->getPID() . "/src/gfx/${gfxImageFile}' target='_blank' download><span class='glyphicon glyphicon-download' style='top: 2px; left: -1px;'></span> Download image</a></li>
                                                </ul>
                                              </li>\n";
                                    }
                                    ?>
                                </ul>
                            </li>
                            <li role="separator" class="divider"></li>
                            <li class="dropdown-header">Configuration file</li>
                            <li><a href='#' onclick='saveFile(function() { goToFile("gfx/convimg.yaml") });'><span class='filename' <?= $maybeBoldStyle ?>>convimg.yaml</span> <span class='fileTabIconContainer'></span></a></li>
                            <li role="separator" class="divider"></li>
                            <li class="dropdown-header">Generated files</li>
                            <?php
                            foreach ($this->availableSrcFiles as $gfxFile)
                            {
                                if ($gfxFile === 'gfx/convimg.yaml' || strpos($gfxFile, 'gfx/') !== 0) { continue; }
                                $maybeBoldStyle = $this->currentFile === $gfxFile ? 'style="font-weight:bold;"' : '';
                                $gfxFile = substr($gfxFile, 4);
                                echo "<li><a href='#' onclick='saveFile(function() { goToFile(\"gfx/{$gfxFile}\") });'><span class='filename' {$maybeBoldStyle}>{$gfxFile}</span> <span class='fileTabIconContainer'></span></a></li>";
                            }
                        }
                        else
                        {
                            echo '<li><a href="#">No gfx resources yet<br>Drag\'n\'drop image files!</a></li>';
                        }
                        ?>
                        </ul>
                    </div>
                </li>
            <?php
                $gfxTakenCareOk = true;
            }

            if (strpos($file, 'gfx/') === 0)
            {
                continue;
            }

            // Group same header and implementation files together
            // (no margin between tabs)
            $counterpartClass = '';
            if ($i < $filesCount-1)
            {
                preg_match(self::REGEXP_GOOD_FILE_PATTERN, $file, $matches);
                [, $nameNoExtCurr, ] = $matches;
                preg_match(self::REGEXP_GOOD_FILE_PATTERN, $this->availableSrcFiles[$i +1], $matches);
                [, $nameNoExtNext, ] = $matches;
                if ($nameNoExtCurr === $nameNoExtNext) {
                    $counterpartClass = 'counterpart';
                }
            }

            if ($file === $this->currentFile)
            {
                $fileListHTML .= "<li class='active tabover {$counterpartClass}";
                if ($this->isCurrentFileRenamable())
                {
                    $fileListHTML .= " renamableFile '><a title='Click to rename' data-toggle='tooltip' data-placement='bottom' id='currentFileTab' href='#' onclick='renameFile(\"{$file}\"); return false;'>";
                } else {
                    $fileListHTML .= "'><a title='Cannot rename this file' data-toggle='tooltip' data-placement='bottom' id='currentFileTab' href='#'>";
                }
                $fileListHTML .= "<span class='filename'>{$file}</span> <span class='fileTabIconContainer'></span></a></li>";
            } else {
                $fileListHTML .= "<li class='{$counterpartClass}'><a href='#' onclick='saveFile(function() { goToFile(\"{$file}\") });'><span class='filename'>{$file}</span> <span class='fileTabIconContainer'></span></a></li>";
            }
        }
        return $fileListHTML;
    }

    /**
     * @return string
     */
    public function getCurrentFileSourceHTML()
    {
        return $this->backend->getCurrentFileSourceHTML();
    }

    /**
     * @return int
     */
    public function getCurrentFileMtime()
    {
        return $this->backend->getCurrentFileMtime();
    }


    /****************************************************/
    // Setters
    // May return a boolean to inform the caller if it went OK and to proceed accordingly (update DB etc.)
    /****************************************************/

    /**
     * @param string $name
     * @return bool
     */
    public function setCurrentFile($name)
    {
        if (is_string($name) && ($name === 'gfx/convimg.yaml' || self::isFileNameOK($name) || self::isImageFileNameOK($name)))
        {
            if ($name === self::TEMPLATE_FILE || file_exists($this->projDirectory . 'src/' . $name))
            {
                $this->currentFile = $name;
                return true;
            }

            return false;
        }
        return false;
    }

    /**
     * Yes, description is actually the project name.
     * @param string $name
     * @return bool
     */
    public function setName($name)
    {
        if (!parent::setName($name)) {
            return false;
        }
        $newSettings = $this->backend->getSettings();
        $newSettings->description = $name;
        return $this->backend->setSettings((array)$newSettings) === PBStatus::OK;
    }

    /**
     * @param string $prgmName
     * @return bool
     */
    public function setInternalName($prgmName)
    {
        if (is_string($prgmName) && self::isPrgmNameOK($prgmName))
        {
            $this->internalName = $prgmName;
            return true;
        }
        return false;
    }


    /****************************************************/
    /* Public methods
    /****************************************************/

    public function doUserAction(UserInfo $user, array $params = [])
    {
        return $this->backend->doUserAction($user, $params);
    }

    public function getSettings()
    {
        return $this->backend->getSettings();
    }

    public function removeFromAvailableFilesList($file)
    {
        if (($key = array_search($file, $this->availableSrcFiles, true)) !== false) {
            unset($this->availableSrcFiles[$key]);
        }
        if (($key = array_search($file, $this->availableBinFiles, true)) !== false) {
            unset($this->availableBinFiles[$key]);
        }
        if (($key = array_search($file, $this->availableGfxImageFiles, true)) !== false) {
            unset($this->availableGfxImageFiles[$key]);
        }
    }

}
