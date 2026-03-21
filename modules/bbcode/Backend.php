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
        parent::__construct($project, $projFolder, self::TEMPLATE_FILE_PATH);
        $this->projPrgmExtension = 'bbcode';
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
                if (!$thisProject->canUserWriteProject($user))
                {
                    return PBStatus::Error('Unauthorized');
                }
                if (!isset($params['source']))
                {
                    return PBStatus::Error('No source provided');
                }
                return $this->saveSource($params['source'], $params['baseSourceHash'] ?? null);

            case 'preview_bbcode':
                if (!isset($params['source']))
                {
                    return PBStatus::Error('No source provided');
                }
                $strippedSource = strip_tags($params['source']);
                return $this->renderBBCodePreview($strippedSource);
        }

        return PBStatus::Error('Unknown action');
    }

    private function saveSource($source, $baseSourceHash = null)
    {
        if (mb_strlen($source) > 5*1024*1024)
        {
            return PBStatus::Error("Couldn't save such a big content (Max = 5 MB)");
        }
        $status = $this->createProjectDirectoryIfNeeded();
        if ($status !== PBStatus::OK)
        {
            return $status;
        }

        $filePath = $this->projFolder . 'src/' . $this->project->getCurrentFile();
        $validation = $this->validateExpectedFileHash($baseSourceHash, $filePath, self::TEMPLATE_FILE_PATH);
        if ($validation !== true)
        {
            return $validation;
        }

        if (!$this->atomicWriteFile($filePath, $source))
        {
            return PBStatus::Error("Couldn't save source to current file");
        }

        return [
            'ok' => true,
            'source_hash' => hash('sha256', $source),
            'mtime' => (int)@filemtime($filePath),
        ];
    }

    private function renderBBCodePreview($text)
    {
        global $phpbb_root_path;
        require_once $phpbb_root_path . 'common.php';
        require_once $phpbb_root_path . 'includes/functions_content.php';

        global $user, $auth;
        $user->session_begin();
        $auth->acl($user->data);
        $user->setup('posting');

        $uid = $bitfield = '';
        $flags = 0;

        $startTime = microtime(true);
        generate_text_for_storage($text, $uid, $bitfield, $flags, true, true, true);
        $html = generate_text_for_display($text, $uid, $bitfield, $flags);
        $html = str_replace('"/data/web/vhosts/tiplanet.org/ROOT/', '"/', $html);
        $renderTime = round((microtime(true) - $startTime) * 1000);

        return [ 'html' => $html, 'renderTime' => $renderTime ];
    }

    public function setSettings(array $params = [])
    {
        // Nothing to persist for now; accept call for API parity
        return PBStatus::OK;
    }
}
