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

final class bbcodeProjectBackend extends PHPBasedBackend
{
    private const TEMPLATE_FILE_PATH = __DIR__ . '/../../projects/template_bbcode/src/Article.bbcode';

    public function __construct(bbcodeProject $project, $projFolder)
    {
        parent::__construct($project, $projFolder);
        $this->projPrgmExtension = 'bbcode';
    }

    public function getAvailableSrcFiles()
    {
        $availableFiles = array_filter(array_map('basename', glob($this->projFolder . 'src/*.*') ?: []), '\ProjectBuilder\bbcodeProject::isFileNameOK');
        sort($availableFiles);
        return $availableFiles;
    }

    public function doUserAction(UserInfo $user, array $params)
    {
        $retParent = parent::handleGlobalProjectAction($user, $params);
        if ($retParent !== self::doUserAction_Unhandled_Action)
        {
            return $retParent;
        }

        /** @var bbcodeProject $thisProject */
        $thisProject = &$this->project;

        if (empty($params['action']))
        {
            return PBStatus::Error('No action parameter given');
        }

        $action = $params['action'];

        switch ($action)
        {
            case 'save':
                if (!($thisProject->isMulti_ReadWrite() || $thisProject->getAuthorID() === $user->getID() || $user->isModeratorOrMore()))
                {
                    return PBStatus::Error('Unauthorized');
                }
                if (!isset($params['source']))
                {
                    return PBStatus::Error('No source provided');
                }
                return $this->saveSource($params['source']);

            case 'preview_bbcode':
                if (!isset($params['source']))
                {
                    return PBStatus::Error('No source provided');
                }
                if ($params['source'] !== strip_tags($params['source']))
                {
                    return PBStatus::Error('BBCode source contains HTML tags, please remove them');
                }
                return $this->renderBBCodePreview($params['source']);
        }

        return PBStatus::Error('Unknown action');
    }

    private function saveSource($source)
    {
        if (mb_strlen($source) > 5*1024*1024)
        {
            return PBStatus::Error("Couldn't save such a big content (Max = 5 MB)");
        }
        $this->createProjectDirectoryIfNeeded();
        $ok = file_put_contents($this->projFolder . 'src/' . $this->project->getCurrentFile(), $source);
        return ($ok !== false) ? PBStatus::OK : PBStatus::Error("Couldn't save source to current file");
    }

    private function renderBBCodePreview($text)
    {
        if (!defined('IN_PHPBB')) {
            define('IN_PHPBB', true);
        }
        $phpEx = 'php';
        $phpbb_root_path = '/data/web/vhosts/tiplanet.org/ROOT/forum/';
        require_once $phpbb_root_path . 'common.php';
        require_once $phpbb_root_path . 'includes/functions_content.php';

        global $user, $auth;
        $user->session_begin();
        $auth->acl($user->data);
        $user->setup('posting');

        $uid = $bitfield = '';
        $flags = 0;

        generate_text_for_storage($text, $uid, $bitfield, $flags, true, true, true);
        $html = generate_text_for_display($text, $uid, $bitfield, $flags);
        $html = str_replace('"/data/web/vhosts/tiplanet.org/ROOT/', '"/', $html);

        return [ 'html' => $html ];
    }

    public function getCurrentFileSourceHTML()
    {
        $currFile = $this->project->getCurrentFile();
        $sourceFile = $this->projFolder . 'src/' . $currFile;
        $templateFile = self::TEMPLATE_FILE_PATH;
        $whichSource = file_exists($sourceFile) ? $sourceFile : $templateFile;
        return htmlentities(file_get_contents($whichSource), ENT_QUOTES);
    }

    public function getCurrentFileMtime()
    {
        $currFile = $this->project->getCurrentFile();
        $sourceFile = $this->projFolder . 'src/' . $currFile;
        $templateFile = self::TEMPLATE_FILE_PATH;
        $whichSource = file_exists($sourceFile) ? $sourceFile : $templateFile;
        return (int)@filemtime($whichSource);
    }

    protected function addIconFile($icon)
    {
        return PBStatus::OK; // no icon
    }

    public function setSettings(array $params = [])
    {
        // Nothing to persist for now; accept call for API parity
        return PBStatus::OK;
    }
}
