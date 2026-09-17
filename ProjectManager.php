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

require_once 'config.php';
require_once 'IUserInfoProvider.php';
require_once 'IDBHelper.php';
require_once 'PBStatus.class.php';
require_once 'ProjectFactory.php';
require_once 'Project.class.php';

header('X-Frame-Options: SAMEORIGIN');

// TODO : use the project's DB primary key for the queries here.

final class ProjectManager
{
    private IDBHelper $pmdb;
    private IUserInfoProvider $userInfoProvider;
    private UserInfo $currentUser;
    private ?Project $currentProject;
    private ?string $lastError;

    private const ANONYMOUS_READ_ONLY_ACTIONS = [
        'getCtags',
        'getBuildLog',
        'getCheckLog',
        'getSrcFileContent',
        'getAllSrcFilesContent',
    ];

    private const AUTHENTICATED_READ_ONLY_ACTIONS = [
        'downloadZipExport',
        'getCtags',
        'getBuildLog',
        'getCheckLog',
        'download',
        'getSrcFileContent',
        'getAllSrcFilesContent',
    ];

    private function initFromConfig()
    {
        global $PB_CONFIG;

        if (!isset($PB_CONFIG['USER_INFO_PROVIDER_CLASS'], $PB_CONFIG['DB_TYPE'], $PB_CONFIG['DB_OBJ']))
        {
            throw new \RuntimeException('Config error: Fields needed: USER_INFO_PROVIDER_CLASS, DB_TYPE, DB_OBJ');
        }

        $this->pmdb = DBHelper_Create($PB_CONFIG['DB_TYPE'], $PB_CONFIG['DB_OBJ']);
        $uipCls = "ProjectBuilder\\" . $PB_CONFIG['USER_INFO_PROVIDER_CLASS'];
        $this->userInfoProvider = new $uipCls();
    }

    public function __construct($projectID = null, array $opts = [])
    {
        $this->lastError = null;
        $this->initFromConfig();

        $this->currentUser = $this->userInfoProvider::getConnectedUserInfo();
        if ($projectID !== null)
        {
            $this->currentProject = $this->getProjectIfExistsAndAllowed($projectID);
            if ($this->currentProject !== null)
            {
                if (!empty($opts))
                {
                    $this->doUserAction($opts);
                }
            } else {
                $this->lastError = 'This project does not exist or you do not have access to it';
            }
        }
    }

    /****************************************************/
    /* Getters
    /****************************************************/

    /**
     * @return UserInfo
     */
    public function getCurrentUser()
    {
        return $this->currentUser;
    }

    /**
     * @return Project | null
     */
    public function getCurrentProject()
    {
        return $this->currentProject;
    }

    /**
     * @return bool
     */
    public function hasValidCurrentProject()
    {
        return $this->currentProject !== null;
    }

    /**
     * @return mixed
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /****************************************************/
    /* Public methods
    /****************************************************/

    public function currentUserIsProjOwnerOrStaff()
    {
        return $this->currentProject->getAuthorID() === $this->currentUser->getID() || $this->currentUser->isModeratorOrMore();
    }

    public function currentUserCanWriteCurrentProject()
    {
        return $this->currentProject !== null && $this->currentProject->canUserWriteProject($this->currentUser);
    }

    public function currentUserHasLiveCollabEditAccess()
    {
        return $this->currentProject !== null
            && $this->currentProject->isMulti_ReadWrite()
            && $this->currentProject->canUserWriteProject($this->currentUser);
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getUserProjectsDataFromDB($limit = 9999)
    {
        // Get all the user's projects that have been created with the pbbot PB
        $res = $this->pmdb->getQueryResults('SELECT * FROM `pb_projects` WHERE `author` = :aut AND `updated` > 1457409600 AND `deleted` IS NULL
                                             ORDER BY `updated` DESC LIMIT :lim ',
                                          [ 'aut' => $this->currentUser->getID(), 'lim' => (int)$limit ]);
        $dbErrCode = $this->pmdb->getLastErrCode();
        if ($dbErrCode !== null) {
            $this->lastError = "Could not retrieve the user's projects from the DB (err code {$dbErrCode}";
        }
        return $res;
    }

    /**
     * @param      $type
     * @param      $name
     * @param      $internalName
     * @param bool $isForkOfCurrent
     * @return null|Project
     * @throws \Exception
     */
    public function createNewProject($type, $name, $internalName, $isForkOfCurrent = false)
    {
        if ($this->currentUser->isAnonymous())
        {
            $this->lastError = 'Unauthorized';
            return null;
        }

        $randKey = str_pad(substr(bin2hex(openssl_random_pseudo_bytes(5)), 0, 10), 10, '0', STR_PAD_LEFT);
        $author = $this->currentUser;
        $now = time();

        $fork_of = ($isForkOfCurrent && $this->hasValidCurrentProject() ? $this->currentProject->getDBID() : null);

        $ok = $this->pmdb->execQuery('INSERT INTO `pb_projects` (`randkey`, `author`, `type`, `name`, `internal_name`, `multiuser`, `multi_readwrite`, `multi_rw_custom`, `chat_enabled`, `created`, `updated`, `fork_of`)
                                         VALUES ( :rk , :aut , :type , :pname , :iname , :mu , :murw , :murwc , :chat , :crea , :upd , :forkof )',
                                    [ 'rk' => $randKey, 'aut' => $author->getID(), 'type' => $type, 'pname' => $name, 'iname' => $internalName,
                                      'mu' => 0, 'murw' => 0, 'murwc' => 0, 'chat' => 0, 'crea' => $now, 'upd' => $now, 'forkof' => $fork_of ]);
        if ($ok === false)
        {
            $this->lastError = 'Error creating the project in the DB';
            return null;
        }

        $pid = $author->getID() . '_' . $now . '_' . $randKey; // TODO: get back from DB?

        $project = ProjectFactory::create((int)$this->pmdb->lastInsertId(), $pid, $author, $type, $name, $internalName, false, false, true, $now, $now);
        if ($project === null)
        {
            // TODO : delete DB line
            $this->lastError = 'Could not create this project - wut?';
            return null;
        }

        return $project;
    }

    /**
     * @param array $params
     * @return null|string
     */
    public function doUserAction(array $params = [])
    {
        if ($this->currentProject !== null)
        {
            if (!empty($params))
            {
                // No need for permission checks for this one...
                if (isset($params['file']) && !empty($params['file']))
                {
                    if (!$this->currentProject->setCurrentFile($params['file']))
                    {
                        return ($this->lastError = 'Invalid file parameter');
                    }
                    // We don't unset this because it might be useful later
                    // (example: backend checking if it was passed, since it can't know if it's the default value or not)
                }

                if (isset($params['action']) && $params['action'] === 'fork')
                {
                    if ($this->currentUser->isAnonymous())
                    {
                        return ($this->lastError = 'Unauthorized');
                    }

                    // Fork can be done by the author, or others as long as the project is at least multiuser
                    if ($this->currentUserIsProjOwnerOrStaff() || $this->currentProject->isMultiuser())
                    {
                        try
                        {
                            $forkProj = $this->createNewProject($this->currentProject->getType(), $this->currentProject->getName(), $this->currentProject->getInternalName(), true);
                            if ($forkProj !== null)
                            {
                                // Now, handle fork stuff at a module level as well
                                $params['fork_newpid'] = $forkProj->getPID();
                                $msg = $this->currentProject->doUserAction($this->currentUser, $params);
                                if (PBStatus::isError($msg))
                                {
                                    return ($this->lastError = $msg);
                                }

                                return $forkProj->getPID();
                            } else {
                                // Error message already set
                                return $this->lastError;
                            }
                        } catch (\Exception $e)
                        {
                            return ($this->lastError = $e->getMessage());
                        }
                    } else {
                        return ($this->lastError = 'Unauthorized');
                    }
                }

                // Special case for a few actions, which only need to have read-only access minimum.
                // Anonymous viewers get a stricter subset with no expected DB/FS side effects.
                if (isset($params['action']) && $this->isReadOnlyActionAllowed($params['action']))
                {
                    if ($this->currentUserIsProjOwnerOrStaff() || $this->currentProject->isMultiuser())
                    {
                        return $this->currentProject->doUserAction($this->currentUser, $params);
                    }

                    return ($this->lastError = $params['action'] . ' unauthorized');
                }

                // From here on, need special permissions
                if (!$this->currentUserCanWriteCurrentProject())
                {
                    return ($this->lastError = ($params['action'] ?? 'action') . ' unauthorized');
                }

                // Security checks are OK at this point
                if (!$this->handleGlobalParameters($params))
                {
                    return $this->lastError;
                }

                if ((count($params) > 1) && (!(count($params) === 2 && isset($params['id']) && isset($params['file'])))) // if file only, it was already taken care of.
                {
                    if (isset($params['action']))
                    {
                        $ret = $this->currentProject->doUserAction($this->currentUser, $params);
                        if (!PBStatus::isError($ret))
                        {
                            // Some global post-action things to do.
                            switch ($params['action'])
                            {
                                case 'save':
                                    $timeNow = time();
                                    $this->currentProject->setUpdatedTstamp($timeNow);
                                    $ok = $this->pmdb->execQuery('UPDATE `pb_projects` SET `updated` = :upd WHERE `id` = :id ',
                                                                 [ 'upd' => $timeNow, 'id' => $this->currentProject->getDBID() ]);
                                    if ($ok === false) {
                                        return ($this->lastError = "Error updating the database (err code: {$this->pmdb->getLastErrCode()})");
                                    }
                                    break;

                                case 'deleteProj':
                                    if (!$this->currentUserIsProjOwnerOrStaff())
                                    {
                                        return ($this->lastError = 'Unauthorized');
                                    }
                                    $ok = $this->pmdb->execQuery('UPDATE `pb_projects` SET `deleted` = :now WHERE `id` = :id ',
                                                                 [ 'now' => time(), 'id' => $this->currentProject->getDBID() ]);
                                    if ($ok === false) {
                                        return ($this->lastError = "Error updating the database (err code: {$this->pmdb->getLastErrCode()})");
                                    }
                                    break;
                            }
                        } else {
                            return ($this->lastError = $ret);
                        }

                        // All done...
                        $this->lastError = null;
                        return $ret;
                    }
                }
            } else {
                return ($this->lastError = 'No parameters');
            }
        } else {
            return ($this->lastError = 'No current project');
        }

        $this->lastError = null;
        return null;
    }


    /****************************************************/
    /* Private methods
    /****************************************************/

    /**
     * Called by doUserAction
     * This method handles secure actions that are the project-manager level, not module-level
     * @param   array   $params
     * @return  bool    Whether there were problems
     */
    private function handleGlobalParameters(array &$params)
    {
        if (!empty($params) && isset($params['action']))
        {
            switch ($params['action'])
            {
                case 'disableMulti':
                case 'enableMultiRO':
                    // Don't allow anyone but the project owner (or staff) to change the shared status
                    if (!$this->currentUserIsProjOwnerOrStaff())
                    {
                        $this->lastError = 'Unauthorized';
                        return false;
                    }
                    $wantMultiUser = $params['action'] === 'enableMultiRO';
                    $this->currentProject->setMultiuser($wantMultiUser, false, false);
                    $ok = $this->pmdb->execQuery('UPDATE `pb_projects` SET `multiuser` = :mu , `multi_readwrite` = :murw, `multi_rw_custom` = :murwc WHERE `id` = :id ',
                                                [ 'mu' => (int)$wantMultiUser, 'murw' => 0, 'murwc' => 0, 'id' => $this->currentProject->getDBID() ] );
                    if ($ok)
                    {
                        $this->currentProject->setMultiReadWriteAllowedUserIDs([]);
                        $this->setReadWriteAllowedUserIDsForProject($this->currentProject->getDBID(), []);
                    }
                    unset($params['action']);
                    $this->lastError = $ok ? $this->lastError : 'Error updating the sharing status';
                    return $ok;

                case 'refreshFirebaseToken':
                    unset($params['action']);
                    $token = $this->currentUser->getOrGenerateFirebaseToken(true);
                    if ($token === null)
                    {
                        $this->lastError = 'Error while refreshing the token. ';
                        return false;
                    }
                    return true;

                case 'setName':
                    if (isset($params['name']) && !empty($params['name']))
                    {
                        if ($this->currentProject->setName($params['name']))
                        {
                            $ok = $this->pmdb->execQuery('UPDATE `pb_projects` SET `name` = :name WHERE `id` = :id ',
                                                         [ 'name' => $params['name'], 'id' => $this->currentProject->getDBID() ] );
                            unset($params['action'], $params['name']);
                            $this->lastError = $ok ? $this->lastError : 'Error updating the name in the DB';
                            return $ok;
                        }

                        $this->lastError = 'Error setting the name';
                        return false;
                    } else {
                        $this->lastError = 'No name given';
                        return false;
                    }
                    break;

                case 'setInternalName':
                    if (isset($params['internalName']) && !empty($params['internalName']))
                    {
                        if ($this->currentProject->setInternalName($params['internalName']))
                        {
                            $ok = $this->pmdb->execQuery('UPDATE `pb_projects` SET `internal_name` = :iname WHERE `id` = :id ',
                                                         [ 'iname' => $params['internalName'], 'id' => $this->currentProject->getDBID() ] );
                            unset($params['action'], $params['internalName']);
                            $this->lastError = $ok ? $this->lastError : 'Error updating the name in the DB';
                            return $ok;
                        }

                        $this->lastError = 'Error setting the name';
                        return false;
                    } else {
                        $this->lastError = 'No name given';
                        return false;
                    }
                    break;

                case 'setSettings':
                    // Don't allow anyone but the project owner (or staff) to change the settings
                    if (!$this->currentUserIsProjOwnerOrStaff())
                    {
                        $this->lastError = 'Unauthorized';
                        return false;
                    }
                    if (isset($params['chatEnabled']))
                    {
                        $val = $params['chatEnabled'];
                        if ($val === '1' || $val === '0')
                        {
                            $ok = $this->pmdb->execQuery('UPDATE `pb_projects` SET `chat_enabled` = :val WHERE `id` = :id ',
                                                        ['val'  => (int)($val === '1'), 'id' => $this->currentProject->getDBID() ] );
                            $this->lastError = $ok ? $this->lastError : 'Error updating the setting in the DB';
                        } else {
                            $this->lastError = 'Error - invalid value for chatEnabled';
                            return false;
                        }
                    }
                    if (isset($params['sharingMode']))
                    {
                        $val = $params['sharingMode'];
                        $wantMultiUser = false;
                        $wantReadWrite = false;
                        $wantCustomReadWrite = false;
                        $allowedRWUserIDs = [];

                        switch ($val)
                        {
                            case 'private':
                                break;

                            case 'publicRO':
                                $wantMultiUser = true;
                                break;

                            case 'publicRW':
                                $wantMultiUser = true;
                                $wantReadWrite = true;
                                break;

                            case 'custom':
                                $wantMultiUser = true;
                                $wantReadWrite = true;
                                $wantCustomReadWrite = true;
                                $allowedRWUserIDs = $this->parseAllowedReadWriteUserIDs($params['allowedRWUserIDs'] ?? '');
                                if ($allowedRWUserIDs === null)
                                {
                                    return false;
                                }
                                break;

                            default:
                                $this->lastError = 'Error - invalid value for sharing mode';
                                return false;
                        }

                        $this->currentProject->setMultiuser($wantMultiUser, $wantReadWrite, $wantCustomReadWrite);
                        if (!$this->pmdb->execQuery('UPDATE `pb_projects` SET `multiuser` = :mu , `multi_readwrite` = :murw, `multi_rw_custom` = :murwc WHERE `id` = :id ',
                            [ 'mu' => (int)$wantMultiUser, 'murw' => (int)$wantReadWrite, 'murwc' => (int)$wantCustomReadWrite, 'id' => $this->currentProject->getDBID() ] ))
                        {
                            $this->lastError = 'Error updating the sharing status in the DB';
                            return false;
                        }

                        if (!$this->setReadWriteAllowedUserIDsForProject($this->currentProject->getDBID(), $wantCustomReadWrite ? $allowedRWUserIDs : []))
                        {
                            $this->lastError = 'Error updating the custom write access list in the DB';
                            return false;
                        }
                        $this->currentProject->setMultiReadWriteAllowedUserIDs($allowedRWUserIDs);
                    }
                    unset($params['id'], $params['sharingMode'], $params['allowedRWUserIDs'], $params['chatEnabled'], $params['csrf_token']);
                    break;
            }
            // TODO : handle more global action cases ?
        }

        return true;
    }

    private function parseAllowedReadWriteUserIDs($rawIDs)
    {
        if (!is_string($rawIDs))
        {
            $this->lastError = 'Invalid value for allowed user IDs';
            return null;
        }

        $tmp = [];
        $parts = preg_split('/[\s,;]+/', trim($rawIDs), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $part)
        {
            if (preg_match('/^\d+$/', $part) !== 1)
            {
                $this->lastError = "Invalid user ID in custom list: {$part}";
                return null;
            }
            $uid = (int)$part;
            if ($uid <= 1)
            {
                $this->lastError = "Invalid user ID in custom list: {$part}";
                return null;
            }
            $tmp[$uid] = true;
        }

        $ids = array_map('intval', array_keys($tmp));
        sort($ids, SORT_NUMERIC);
        if (count($ids) === 0)
        {
            $this->lastError = 'Custom read+write mode needs at least one allowed user ID';
            return null;
        }
        if (count($ids) > 100)
        {
            $this->lastError = 'Custom read+write mode supports up to 100 allowed user IDs';
            return null;
        }

        return $ids;
    }

    private function isReadOnlyActionAllowed($action)
    {
        if (!is_string($action) || $action === '')
        {
            return false;
        }

        $allowedActions = $this->currentUser->isAnonymous()
            ? self::ANONYMOUS_READ_ONLY_ACTIONS
            : self::AUTHENTICATED_READ_ONLY_ACTIONS;

        return in_array($action, $allowedActions, true);
    }

    private function getReadWriteAllowedUserIDsForProject($projectDBID)
    {
        $res = $this->pmdb->getQueryResults('SELECT `user_id`
                                               FROM `pb_project_rw_acl`
                                              WHERE `proj_id` = :pid
                                           ORDER BY `user_id` ASC ',
                                           [ 'pid' => (int)$projectDBID ]);

        $ids = [];
        foreach ($res as $row)
        {
            $uid = (int)$row->user_id;
            if ($uid > 1) {
                $ids[$uid] = true;
            }
        }

        $ids = array_map('intval', array_keys($ids));
        sort($ids, SORT_NUMERIC);
        return $ids;
    }

    private function setReadWriteAllowedUserIDsForProject($projectDBID, array $userIDs)
    {
        if (!$this->pmdb->beginTransaction())
        {
            return false;
        }

        $ok = $this->pmdb->execQuery('DELETE FROM `pb_project_rw_acl` WHERE `proj_id` = :pid ',
                                     [ 'pid' => (int)$projectDBID ]);
        if ($ok !== false)
        {
            foreach ($userIDs as $uid)
            {
                $ok = $this->pmdb->execQuery('INSERT INTO `pb_project_rw_acl` (`proj_id`, `user_id`) VALUES (:pid, :uid) ',
                                             [ 'pid' => (int)$projectDBID, 'uid' => (int)$uid ]);
                if ($ok === false)
                {
                    break;
                }
            }
        }

        if ($ok)
        {
            $ok = $this->pmdb->commit();
            if ($ok)
            {
                $this->logActionInDB('setReadWriteAllowedUserIDsForProject',
                                      substr(json_encode([ 'proj_id' => (int)$projectDBID, 'user_ids' => $userIDs ]), 0, 49),
                                      true);
                return true;
            }
        }

        $this->pmdb->rollBack();
        $this->logActionInDB('setReadWriteAllowedUserIDsForProject',
                              substr(json_encode([ 'proj_id' => (int)$projectDBID, 'user_ids' => $userIDs ]), 0, 49),
                              false);
        return false;
    }

    /**
     * @param $projectID
     * @return Project|null
     * @throws \Exception
     */
    private function getProjectIfExistsAndAllowed($projectID)
    {
        if (!is_string($projectID) || preg_match('/^\d+_\d{10}_[a-zA-Z0-9]{10}$/', $projectID) !== 1)
        {
            return null;
        }

        $res = $this->pmdb->getQueryResults('SELECT `id`, `author`, `type`, `name`, `internal_name`, `multiuser`, `multi_readwrite`, `multi_rw_custom`, `chat_enabled`, `created`, `updated`
                                               FROM `pb_projects`
                                              WHERE `pid` = :pid AND `deleted` IS NULL ',
                                           [ 'pid' => $projectID ]);
        if (count($res) === 1)
        {
            $res = $res[0];

            $projAuthor = $this->userInfoProvider::getUserInfoFromID((int)$res->author);
            $db_id = (int)$res->id;
            $multiuser = $res->multiuser === '1';
            $multi_readwrite = $res->multi_readwrite === '1';
            $multi_readwrite_custom = $res->multi_rw_custom === '1';
            $chatEnabled = $res->chat_enabled === '1';
            $projCTime = (int)$res->created;
            $projUTime = (int)$res->updated;
            $multi_readwrite_allowed_userids = $multi_readwrite_custom ? $this->getReadWriteAllowedUserIDsForProject($db_id) : [];

            // Multiuser projects remain readable by anyone with the link.
            // Write permissions are enforced later through canUserWriteProject().
            if (!$multiuser && $projAuthor->getID() !== $this->currentUser->getID()) {
                return null;
            }

            // for now, bbcode is only for mods+
            if ($res->type === 'bbcode' && !$this->currentUser->isModeratorOrMore()) {
                die('No');
            }

            return ProjectFactory::create($db_id, $projectID, $projAuthor, $res->type, $res->name, $res->internal_name, $multiuser, $multi_readwrite, $chatEnabled, $projCTime, $projUTime, $multi_readwrite_custom, $multi_readwrite_allowed_userids);
        }

        return null;
    }

    public function logActionInDB($action, $paramsStr, $isOK)
    {
        $this->pmdb->execQuery('INSERT INTO `pb_logs` (`user_id`, `proj_id`, `action`, `params`, `ok`, `tstamp`) VALUES ( ? , ? , ? , ? , ? , ? )',
                               [ @$this->getCurrentUser()->getID(), @$this->getCurrentProject()->getDBID(), $action, $paramsStr, $isOK, time() ]);
    }

}
