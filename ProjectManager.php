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
    /**
     * @var IDBHelper
     */
    private $pmdb;
    /**
     * @var IUserInfoProvider
     */
    private $userInfoProvider;
    /**
     * @var UserInfo
     */
    private $currentUser;
    /**
     * @var Project
     */
    private $currentProject;
    /**
     * @var string|null
     */
    private $lastError;

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
        $this->initFromConfig();

        $this->currentUser = $this->userInfoProvider->getConnectedUserInfo();
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
        $randKey = str_pad(substr(bin2hex(openssl_random_pseudo_bytes(5)), 0, 10), 10, '0', STR_PAD_LEFT);
        $author = $this->currentUser;
        $now = time();

        $project = null;

        $fork_of = ($isForkOfCurrent && $this->hasValidCurrentProject() ? $this->currentProject->getDBID() : null);

        $ok = $this->pmdb->execQuery('INSERT INTO `pb_projects` (`randkey`, `author`, `type`, `name`, `internal_name`, `multiuser`, `multi_readwrite`, `chat_enabled`, `created`, `updated`, `fork_of`)
                                         VALUES ( :rk , :aut , :type , :pname , :iname , :mu , :murw , :chat , :crea , :upd , :forkof )',
                                    [ 'rk' => $randKey, 'aut' => $author->getID(), 'type' => $type, 'pname' => $name, 'iname' => $internalName,
                                      'mu' => false, 'murw' => false, 'chat' => false, 'crea' => $now, 'upd' => $now, 'forkof' => $fork_of ]);
        if ($ok === false)
        {
            $this->lastError = 'Error creating the project in the DB';
            return null;
        }

        $project = ProjectFactory::create((int)$this->pmdb->lastInsertId(), $randKey, $author, $type, $name, $internalName, false, false, true, $now, $now);
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
                    // Fork can be done by the author, or others as long as the project is at least multiuser
                    if ($this->currentUserIsProjOwnerOrStaff() || $this->currentProject->isMultiuser())
                    {
                        try
                        {
                            $forkProj = $this->createNewProject($this->currentProject->getType(), $this->currentProject->getName(), $this->currentProject->getInternalName(), true);
                            if ($forkProj !== null)
                            {
                                // Now, handle fork stuff at a module level as well
                                $params['fork_newid'] = $forkProj->getID();
                                $msg = $this->currentProject->doUserAction($this->currentUser, $params);
                                if (PBStatus::isError($msg))
                                {
                                    return ($this->lastError = $msg);
                                }

                                return $forkProj->getID();
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

                // Special case of the zip dl, which only needs to have read-only access minimum
                if (isset($params['action']) && $params['action'] === 'downloadZipExport')
                {
                    if ($this->currentUserIsProjOwnerOrStaff() || $this->currentProject->isMultiuser())
                    {
                        return $this->currentProject->doUserAction($this->currentUser, $params);
                    }

                    return ($this->lastError = 'Unauthorized');
                }

                // From here on, need special permissions
                if (!($this->currentUserIsProjOwnerOrStaff() || $this->currentProject->isMulti_ReadWrite()))
                {
                    return ($this->lastError = 'Unauthorized');
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
        if ($params !== null && !empty($params) && isset($params['action']))
        {
            switch ($params['action'])
            {
                case 'disableMulti':
                case 'enableMultiRO':
                case 'enableMultiRW':
                    // Don't allow anyone but the project owner (or staff) to change the shared status
                    if (!$this->currentUserIsProjOwnerOrStaff())
                    {
                        $this->lastError = 'Unauthorized';
                        return false;
                    }
                    $wantMultiUser = $params['action'] === 'enableMultiRO' || $params['action'] === 'enableMultiRW';
                    $wantReadWrite = $wantMultiUser && $params['action'] === 'enableMultiRW';
                    if ($this->currentProject->setMultiuser($wantMultiUser, $wantReadWrite))
                    {
                        $ok = $this->pmdb->execQuery('UPDATE `pb_projects` SET `multiuser` = :mu , `multi_readwrite` = :murw WHERE `id` = :id ',
                                                    [ 'mu' => $wantMultiUser, 'murw' => $wantReadWrite, 'id' => $this->currentProject->getDBID() ] );
                        unset($params['action']);
                        $this->lastError = $ok ? $this->lastError : 'Error updating the sharing status in the DB';
                        return $ok;
                    }

                $this->lastError = 'Error changing the sharing status';
                return false;

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
                                                        ['val'  => $val === '1', 'id' => $this->currentProject->getDBID() ] );
                            $this->lastError = $ok ? $this->lastError : 'Error updating the setting in the DB';
                        } else {
                            $this->lastError = 'Error - invalid value for chatEnabled';
                            return false;
                        }
                    }
                    if (isset($params['sharingMode']))
                    {
                        $val = $params['sharingMode'];
                        $wantMultiUser = $val === 'publicRO' || $val === 'publicRW';
                        $wantReadWrite = $wantMultiUser && $val === 'publicRW';
                        if ($this->currentProject->setMultiuser($wantMultiUser, $wantReadWrite))
                        {
                            $ok = $this->pmdb->execQuery('UPDATE `pb_projects` SET `multiuser` = :mu , `multi_readwrite` = :murw WHERE `id` = :id ',
                                                         [ 'mu' => $wantMultiUser, 'murw' => $wantReadWrite, 'id' => $this->currentProject->getDBID() ] );
                            $this->lastError = $ok ? $this->lastError : 'Error updating the sharing status in the DB';
                        } else {
                            $this->lastError = 'Error changing the sharing status';
                            return false;
                        }
                    }
                    unset($params['id'], $params['sharingMode'], $params['chatEnabled'], $params['csrf_token']);
                    break;
            }
            // TODO : handle more global action cases ?
        }

        return true;
    }

    /**
     * @param $projectID
     * @return Project|null
     * @throws \Exception
     */
    private function getProjectIfExistsAndAllowed($projectID)
    {
        if (!is_string($projectID) || preg_match('/^(\d+)_(\d{10})_([a-zA-Z0-9]{10})$/', $projectID, $matches) !== 1)
        {
            return null;
        }

        $projAuthor = $this->userInfoProvider->getUserInfoFromID((int)$matches[1]);
        $projCTime  = (int)$matches[2];
        $projKey    = $matches[3];

        // TODO : finer permission check (multi user -> if user is allowed)
        $sqlCond = ($projAuthor->getID() !== $this->currentUser->getID()) ? ' AND `multiuser` = 1 ' : '';
        $res = $this->pmdb->getQueryResults('SELECT `id`, `type`, `name`, `internal_name`, `multiuser`, `multi_readwrite`, `chat_enabled`, `updated`
                                               FROM `pb_projects`
                                              WHERE `author` = :aut AND `created` = :crea AND `randkey` = :rk AND `deleted` IS NULL ' . $sqlCond,
                                           [ 'aut' => $projAuthor->getID(), 'crea' => $projCTime, 'rk' => $projKey ]);
        if (count($res) === 1)
        {
            $res = $res[0];
            $db_id = (int)$res->id;
            $multiuser = $res->multiuser === '1';
            $multi_readwrite = $res->multi_readwrite === '1';
            $chatEnabled = $res->chat_enabled === '1';
            $projUTime = (int)$res->updated;

            return ProjectFactory::create($db_id, $projKey, $projAuthor, $res->type, $res->name, $res->internal_name, $multiuser, $multi_readwrite, $chatEnabled, $projCTime, $projUTime);
        }

        return null;
    }

    public function logActionInDB($action, $paramsStr, $isOK)
    {
        $this->pmdb->execQuery('INSERT INTO `pb_logs` (`user_id`, `proj_id`, `action`, `params`, `ok`, `tstamp`) VALUES ( ? , ? , ? , ? , ? , ? )',
                               [ @$this->getCurrentUser()->getID(), @$this->getCurrentProject()->getDBID(), $action, $paramsStr, $isOK, time() ]);
    }

}
