<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 *
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author  		Raymond Benc
 * @package  		Module_Mail
 * @version 		$Id: mail.class.php 7047 2014-01-16 13:28:17Z Fern $
 */
class Mail_Service_Mail extends Phpfox_Service
{
	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->_sTable = Phpfox::getT('mail');
	}

	/**
	 * This function validates the permission to send a PM to another user, it
	 * takes into account the user group setting: mail.can_compose_message
	 * the privacy setting by the receiving user: mail.send_message
	 * and if the receiving user is blocked by the sender user or viceversa
	 * Also checks on other user group based restrictions
	 * @param int $iUser The user id of the member trying to send a message
	 * @return boolean true if its ok to send the message, false otherwise
	 */
	public function canMessageUser($iUser)
	{
		(($sPlugin = Phpfox_Plugin::get('mail.service_mail_canmessageuser_1')) ? eval($sPlugin) : false);
		if (isset($bCanOverrideChecks))
		{
			return true;
		}
		// 1. user group setting:
		if (!Phpfox::getUserParam('mail.can_compose_message'))
		{
			return false;
		}
		// 2. Privacy setting check
		$iPrivacy = $this->database()->select('user_value')
				->from(Phpfox::getT('user_privacy'))
				->where('user_id = ' . (int)$iUser . ' AND user_privacy = "mail.send_message"')
				->execute('getSlaveField');

		if (!empty($iPrivacy) && !Phpfox::isAdmin())
		{
			if ($iPrivacy == 4) // No one
			{
				return false;
			}
			else if($iPrivacy == 1 && !Phpfox::isUser()) // trivial case
			{
				return false;
			}
			else if ($iPrivacy == 2 && Phpfox::isModule('friend') &&  !Phpfox::getService('friend')->isFriend(Phpfox::getUserId(), $iUser, false)) // friends only
			{
				return false;
			}
		}

		// 3. Blocked users
		if (!Phpfox::isAdmin() && (Phpfox::getService('user.block')->isBlocked(Phpfox::getUserId(), $iUser) > 0 || Phpfox::getService('user.block')->isBlocked($iUser, Phpfox::getUserId()) > 0))
		{
			return false;
		}

		// 4. Sending message to yourself
		if ($iUser == Phpfox::getUserId())
		{
			return false;
		}

		// 5. User group setting (different from check 2 since that is user specific)
		if ((Phpfox::getUserParam('mail.restrict_message_to_friends') == true)
			&& (Phpfox::isModule('friend') && Phpfox::getService('friend')->isFriend(Phpfox::getUserId(), $iUser, false) == false))
		{
			return false;
		}
		// then its ok
		return true;
	}

    public function get(
        $aConds = array(),
        $sSort = 'm.time_updated DESC',
        $iPage = '',
        $iLimit = '',
        $bIsSentbox = false,
        $bIsTrash = false
    ) {
        $aRows = array();
        $aInputs = array(
            'unread',
            'read'
        );

        $iArchiveId = ($bIsTrash ? 1 : 0);
        $bIsTextSearch = false;
        if (!defined('PHPFOX_IS_PRIVATE_MAIL')) {
            $this->database()->select('COUNT(*)');
            if ($bIsSentbox) {
                $this->database()->where('th.user_id = ' . (int)Phpfox::getUserId() . ' AND th.is_archive = 0 AND th.is_sent = 1');
            } else {
                $this->database()->where('th.user_id = ' . (int)Phpfox::getUserId() . ' AND th.is_archive = ' . (int)$iArchiveId . '');
            }
        } else {
            $this->database()->select('COUNT(DISTINCT t.thread_id)');
            $aNewCond = array();
            if (count($aConds)) {
                foreach ($aConds as $sCond) {
                    if (preg_match('/AND mt.text LIKE \'%(.*)%\'/i', $sCond, $aTextMatch)) {
                        $bIsTextSearch = true;
                        $aNewCond[] = $sCond;
                    }
                }
            }
        }

        if ($bIsTextSearch) {
            $iCnt = $this->database()->from(Phpfox::getT('mail_thread_text'), 'mt')
                ->join(Phpfox::getT('mail_thread'), 't', 't.thread_id = mt.thread_id')
                ->where(isset($aNewCond) ? $aNewCond : "true")
                ->execute('getSlaveField');
        } else {
            $iCnt = $this->database()->from(Phpfox::getT('mail_thread_user'), 'th')
                ->join(Phpfox::getT('mail_thread'), 't', 't.thread_id = th.thread_id')
                ->execute('getSlaveField');
        }

        if ($iCnt) {
            (($sPlugin = Phpfox_Plugin::get('mail.service_mail_get')) ? eval($sPlugin) : false);

            if (!defined('PHPFOX_IS_PRIVATE_MAIL')) {
                if ($bIsSentbox) {
                    $this->database()->where('th.user_id = ' . (int)Phpfox::getUserId() . ' AND th.is_archive = 0 AND th.is_sent = 1');
                } else {
                    $this->database()->where('th.user_id = ' . (int)Phpfox::getUserId() . ' AND th.is_archive = ' . (int)$iArchiveId . '');
                }
            } else {
                if (isset($aNewCond) && count($aNewCond)) {
                    $this->database()->where($aNewCond);
                }
            }

            if ($bIsTextSearch) {
                $aRows = $this->database()->select('th.*, mt.text AS preview, mt.time_stamp, mt.user_id AS last_user_id')
                    ->from(Phpfox::getT('mail_thread_text'), 'mt')
                    ->join(Phpfox::getT('mail_thread_user'), 'th', 'th.user_id = mt.user_id')
                    ->join(Phpfox::getT('mail_thread'), 't', 't.thread_id = mt.thread_id')
                    ->join(Phpfox::getT('user'), 'u', 'u.user_id = mt.user_id')
                    ->limit($iPage, $iLimit, $iCnt)
                    ->order('t.time_stamp DESC')
                    ->group('mt.thread_id', true)
                    ->execute('getSlaveRows');
            } else {
                $aRows = $this->database()->select('th.*, tt.text AS preview, tt.time_stamp, tt.user_id AS last_user_id')
                    ->from(Phpfox::getT('mail_thread_user'), 'th')
                    ->join(Phpfox::getT('mail_thread'), 't', 't.thread_id = th.thread_id')
                    ->leftJoin(Phpfox::getT('mail_thread_text'), 'tt', 'tt.message_id = t.last_id')
                    ->leftJoin(Phpfox::getT('user'), 'u', 'u.user_id = tt.user_id')
                    ->limit($iPage, $iLimit, $iCnt)
                    ->order('t.time_stamp DESC')
                    ->group('th.thread_id', true)
                    ->execute('getSlaveRows');
            }

            $aFields = Phpfox::getService('user')->getUserFields();

            foreach ($aRows as $iKey => $aRow) {
                $bCheckRow = true;
                $filter = array();
                foreach ($aConds as $sCond) {
                    // search by sender
                    if (strpos($sCond, 'SENDER=') !== false) {
                        $filter['full_name'] = Phpfox::getLib('parse.input')->clean(str_replace(array(
                            'SENDER=',
                            "'"
                        ), '', $sCond));
                    } elseif (strpos($sCond, 'USERGROUPID=') !== false) {
                        $filter['user_group_id'] = Phpfox::getLib('parse.input')->clean(str_replace(array(
                            'USERGROUPID=',
                            "'"
                        ), '', $sCond));
                    }
                }
                if (count($filter)) {
                    $aUsers = array();
                    if (isset($filter['full_name']) && isset($filter['user_group_id'])) {
                        $aUsers = $this->database()->select('user_id')->from(':user')
                            ->where("full_name LIKE '%" . $filter['full_name'] . "%' AND user_group_id=" . $filter['user_group_id'])
                            ->executeRows();
                    } elseif (isset($filter['full_name'])) {
                        $aUsers = $this->database()->select('user_id')->from(':user')
                            ->where("full_name LIKE '%" . $filter['full_name'] . "%'")->executeRows();
                    } elseif (isset($filter['user_group_id'])) {
                        $aUsers = $this->database()->select('user_id')->from(':user')
                            ->where("user_group_id = " . $filter['user_group_id'])->executeRows();
                    }
                    if (!count($aUsers)) {
                        $bCheckRow = false;
                    } else {
                        $bCheckUser = false;
                        foreach ($aUsers as $aUser) {
                            // check if search user send message in coversation
                            $result = $this->database()->select('message_id')->from(':mail_thread_text')
                                ->where(array(
                                    'user_id' => $aUser['user_id'],
                                    'thread_id' => $aRow['thread_id']
                                ))->executeField();
                            if ($result) {
                                $bCheckUser = true;
                                break;
                            }
                        }
                        $bCheckRow = $bCheckUser;
                    }
                }


                if (!$bCheckRow) {
                    unset($aRows[$iKey]);
                    continue;
                }
                $aRows[$iKey]['preview'] = strip_tags($aRow['preview']);
                $aRows[$iKey]['viewer_is_new'] = ($aRow['is_read'] ? false : true);
                $aRows[$iKey]['users'] = $this->database()->select('th.is_read, ' . Phpfox::getUserField())
                    ->from(Phpfox::getT('mail_thread_user'), 'th')
                    ->join(Phpfox::getT('user'), 'u', 'u.user_id = th.user_id')
                    ->where('th.thread_id = ' . (int)$aRow['thread_id'])
                    ->execute('getSlaveRows');

                $iUserCnt = 0;
                foreach ($aRows[$iKey]['users'] as $iUserKey => $aUser) {
                    if (!\Core\Route\Controller::$isApi && !defined('PHPFOX_IS_PRIVATE_MAIL') && $aUser['user_id'] == Phpfox::getUserId()) {
                        unset($aRows[$iKey]['users'][$iUserKey]);
                        continue;
                    }

                    $iUserCnt++;

                    if ($iUserCnt == 1) {
                        foreach ($aFields as $sField) {
                            if ($sField == 'server_id') {
                                $sField = 'user_server_id';
                            }
                            $aRows[$iKey][$sField] = $aUser[$sField];
                        }
                    }

                    if (!isset($aRows[$iKey]['users_is_read'])) {
                        $aRows[$iKey]['users_is_read'] = array();
                    }

                    if ($aUser['is_read']) {
                        $aRows[$iKey]['users_is_read'][] = $aUser;
                    }
                }

                if (!$iUserCnt) {
                    unset($aRows[$iKey]);
                }
            }
        }

        //thread name
        foreach ($aRows as $iKey => $aRow) {
            $iCntUser = 0;
            $sThreadName = '';
            $iCut = 0;
            foreach ($aRow['users'] as $aUser) {
                $sMore = \Phpfox_Parse_Output::instance()->shorten($aUser['full_name'], 30, '...');
                if (strlen($sThreadName . $sMore) < 45) {
                    $sThreadName .= $sMore;
                    $iCut++;
                }
                $iCntUser++;
                if ($iCntUser == $iCut && count($aRow['users']) > 1) {
                    $sThreadName .= ', ';
                }
            }
            if ($iCntUser > $iCut) {
                if (Phpfox::isPhrase('mail.and_number_other')) {
                    $sThreadName .= ' ' . _p('and_number_other',
                            array('number' => ($iCntUser - $iCut))) . ((($iCntUser - $iCut) > 1) ? 's' : '');
                } else {
                    $sThreadName .= ' and ' . ($iCntUser - $iCut) . ' other' . ((($iCntUser - $iCut) > 1) ? 's' : '');
                }
            }
            $aRows[$iKey]['thread_name'] = rtrim(rtrim($sThreadName), ',');
        }

        return array($iCnt, $aRows, $aInputs);
    }

	/**
	 * Gets all the mail_id for a specific user in a specific folder.
	 * @param int $iUser
	 * @param int $iFolder
	 * @param bool $bIsSentbox
	 * @return array
	 */
	public function getAllMailFromFolder($iUser, $iFolder, $bIsSentbox, $bIsTrash)
	{
		$sWhere = '';
		if ($bIsSentbox)
		{
			$sWhere .= (int)$iUser . ' = m.owner_user_id' . ' AND ' . (int)$iFolder. ' = m.owner_folder_id AND m.owner_type_id != 3' ;
		}
		elseif ($bIsTrash)
		{
			$sWhere .= '(m.viewer_user_id = '.(int)$iUser.' AND m.viewer_type_id = 1) OR (m.owner_user_id = '.(int)$iUser.' AND m.owner_type_id = 1)';
		}
		else
		{
			$sWhere .= (int)$iUser . ' = m.viewer_user_id AND ' . (int)$iFolder . ' = m.viewer_folder_id AND m.viewer_type_id != 3' ;
		}

		$aMails = $this->database()->select('m.mail_id')
			->from($this->_sTable, 'm')
			->where($sWhere)
			->execute('getSlaveRows');

		$aOut = array();

		foreach ($aMails as $aMail) $aOut[] = $aMail['mail_id'];
		return $aOut;
	}

	public function getMail($iId, $bForce = false)
    {
        if (!$bForce) {
            list(, $aMessages) = $this->getThreadedMail($iId);

            return $aMessages;
        }

		(($sPlugin = Phpfox_Plugin::get('mail.service_mail_getmail')) ? eval($sPlugin) : false);

		$aMail = $this->database()->select('m.*, ' . (Phpfox::getParam('core.allow_html') ? "mreply.text_parsed" : "mreply.text") . ' AS text_reply, ' . (Phpfox::getParam('core.allow_html') ? "mt.text_parsed" : "mt.text") . ' AS text, ' . Phpfox::getUserField('u', 'owner_') . ', ' . Phpfox::getUserField('u2', 'viewer_'))
			->from($this->_sTable, 'm')
			->join(Phpfox::getT('mail_text'), 'mt', 'mt.mail_id = m.mail_id')
			->leftJoin(Phpfox::getT('user'), 'u', 'u.user_id = m.owner_user_id')
			->join(Phpfox::getT('user'), 'u2', 'u2.user_id = m.viewer_user_id')
			->leftJoin(Phpfox::getT('mail_text'), 'mreply', 'mreply.mail_id = m.parent_id') /** @TODO PUREFAN changed this */
			->where('m.mail_id = ' . (int) $iId . '')
			->execute('getSlaveRow');
		if (empty($aMail))
		{
			return $aMail;
		}

		if ($aMail['viewer_folder_id'] > 0)
		{
			$aMail['folder_name'] = Phpfox::getService('mail.folder')->getFolder($aMail['viewer_folder_id']);
		}

		return $aMail;
	}

	public function getPrev($iTime, $bIsSentbox = false, $bIsTrash = false)
	{
		return $this->database()->select('m.mail_id')
			->from($this->_sTable, 'm')
			->where(($bIsSentbox ? 'm.owner_user_id = ' . Phpfox::getUserId() . ' AND m.time_updated > ' . (int) $iTime . ' AND m.owner_type_id = ' . ($bIsTrash ? 1 : 0) . '' : 'm.viewer_user_id = ' . Phpfox::getUserId() . ' AND m.viewer_type_id = ' . ($bIsTrash ? 1 : 0) . ' AND m.time_updated > ' . (int) $iTime . ''))
			->order('m.time_updated ASC')
			->execute('getSlaveField');
	}

	public function getNext($iTime, $bIsSentbox = false, $bIsTrash = false)
	{
		return $this->database()->select('m.mail_id')
			->from($this->_sTable, 'm')
			->where(($bIsSentbox ? 'm.owner_user_id = ' . Phpfox::getUserId() . ' AND m.time_updated < ' . (int) $iTime . ' AND m.owner_type_id = ' . ($bIsTrash ? 1 : 0) . '' : 'm.viewer_user_id = ' . Phpfox::getUserId() . ' AND m.viewer_type_id = ' . ($bIsTrash ? 1 : 0) . ' AND m.time_updated < ' . (int) $iTime . ''))
			->order('m.time_updated DESC')
			->execute('getSlaveField');
	}

	/**
	 * If a call is made to an unknown method attempt to connect
	 * it to a specific plug-in with the same name thus allowing
	 * plug-in developers the ability to extend classes.
	 *
	 * @param string $sMethod is the name of the method
	 * @param array $aArguments is the array of arguments of being passed
	 */
	public function __call($sMethod, $aArguments)
	{
		/**
		 * Check if such a plug-in exists and if it does call it.
		 */
		if ($sPlugin = Phpfox_Plugin::get('mail.service_mail__call'))
		{
			eval($sPlugin);
            return null;
		}

		/**
		 * No method or plug-in found we must throw a error.
		 */
		Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
	}

	public function getDefaultFoldersCount($iUserId)
	{
        $iCountInbox = (int) $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('mail_thread_user'), 'm')
            ->where('m.user_id = ' . Phpfox::getUserId() . ' AND m.is_archive = 0 AND m.is_sent = 0')
            ->execute('getSlaveField');

        $iCountSentbox = (int) $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('mail_thread_user'), 'm')
            ->where('m.user_id = ' . Phpfox::getUserId() . ' AND m.is_archive = 0 AND m.is_sent = 1')
            ->execute('getSlaveField');

        $iCountDeleted = (int) $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('mail_thread_user'), 'm')
            ->where('m.user_id = ' . Phpfox::getUserId() . ' AND m.is_archive = 1')
            ->execute('getSlaveField');

        return array(
			'iCountInbox' => $iCountInbox,
			'iCountSentbox' => $iCountSentbox,
			'iCountDeleted' => $iCountDeleted);
	}

	public function getLatest()
	{
        $aFields = Phpfox::getService('user')->getUserFields();

        $aRows = $this->database()->select('th.*, tt.text AS preview, tt.time_stamp, tt.user_id AS last_user_id')
            ->from(Phpfox::getT('mail_thread_user'), 'th')
            ->join(Phpfox::getT('mail_thread'), 't', 't.thread_id = th.thread_id')
            ->join(Phpfox::getT('mail_thread_text'), 'tt', 'tt.message_id = t.last_id')
            ->join(Phpfox::getT('user'), 'u', 'u.user_id = tt.user_id')
            ->where('th.user_id = ' . (int) Phpfox::getUserId() . ' AND th.is_archive = 0 AND th.is_sent_update = 0')
            ->limit(5)
            ->order('t.time_stamp DESC')
            ->execute('getSlaveRows');

        foreach ($aRows as $iKey => $aRow)
        {
            $aRows[$iKey]['viewer_is_new'] = ($aRow['is_read'] ? false : true);
            $aRows[$iKey]['users'] = $this->database()->select('th.is_read, ' . Phpfox::getUserField())
                ->from(Phpfox::getT('mail_thread_user'), 'th')
                ->join(Phpfox::getT('user'), 'u', 'u.user_id = th.user_id')
                ->where('th.thread_id = ' . (int) $aRow['thread_id'])
                ->execute('getSlaveRows');

            $iUserCnt = 0;
            foreach ($aRows[$iKey]['users'] as $iUserKey => $aUser)
            {
                if ($aUser['user_id'] == Phpfox::getUserId())
                {
                    unset($aRows[$iKey]['users'][$iUserKey]);
                    continue;
                }

                $iUserCnt++;

                if ($iUserCnt == 1)
                {
                    foreach ($aFields as $sField)
                    {
                        if ($sField == 'server_id')
                        {
                            $sField = 'user_server_id';
                        }
                        $aRows[$iKey][$sField] = $aUser[$sField];
                    }
                }
            }
        }

        return $aRows;
	}

	/**
	 * We needed a different join so instead of adding another param or loading extra the $this->get() function
	 * its more practical to create a new function with stepping
	 */
	public function getPrivate($aConds, $iLimit,  $sSort, $iPage = 0)
	{
        list($iCnt, $aMail,) = $this->get($aConds, $sSort, $iPage, $iLimit);

        return array($aMail, $iCnt);
	}

	public function isDeleted($iMail)
	{
		$iValue = $this->database()->select('mail_id')
			->from($this->_sTable)
			->where('(viewer_user_id = '.Phpfox::getUserId().' AND viewer_type_id = 1) OR (owner_user_id = '.Phpfox::getUserId().' AND owner_type_id = 1)')
			->execute('getSlaveField');
		return ($iValue == $iMail);
	}

	public function isSent($iMail)
	{
		$iValue = $this->database()->select('mail_id')
			->from($this->_sTable)
			->where('mail_id = ' . (int)$iMail . ' AND owner_user_id = ' . Phpfox::getUserId())
			->execute('getSlaveField');
		return ($iValue == $iMail);
	}

    /**
     * @deprecated 4.7.0
     * @return int
     */
	public function getLegacyCount()
	{
		$iCnt = (int) $this->database()->select('COUNT(*)')
			->from(Phpfox::getT('mail'), 'm')
			->where('m.viewer_folder_id = 0 AND m.viewer_user_id = ' . Phpfox::getUserId() . ' AND m.viewer_type_id = 0')
			->execute('getSlaveField');
		return $iCnt;
	}

	public function getUnseenTotal()
	{
        $iCnt = (int) $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('mail_thread_user'), 'm')
            ->where('m.user_id = ' . Phpfox::getUserId() . ' AND m.is_read = 0')
            ->execute('getSlaveField');

        return $iCnt;
	}

	public function buildMenu()
	{
		// Add a hook with return here
		if (Phpfox::getParam('mail.show_core_mail_folders_item_count') && Phpfox::getUserParam('mail.show_core_mail_folders_item_count'))
		{
			$aCountFolders = Phpfox::getService('mail')->getDefaultFoldersCount(Phpfox::getUserId());
		}

		$aFilterMenu = array(
			_p('inbox') . (isset($aCountFolders['iCountInbox']) ? ' <span class="count-item">' . $aCountFolders['iCountInbox'] . '</span>' : '') => '',
			_p('sent_messages') . (isset($aCountFolders['iCountSentbox']) ? ' <span class="count-item">' . $aCountFolders['iCountSentbox'] . '</span>' : '') => 'sent',
			_p('archive') . (isset($aCountFolders['iCountDeleted']) ? ' <span class="count-item">' . $aCountFolders['iCountDeleted'] . '</span>' : '') => 'trash'
		);

		Phpfox_Template::instance()->buildSectionMenu('mail', $aFilterMenu);
	}

	public function getThreadedMail($iThreadId, $iPage = 0, $getLatest = false, $iOffset = 0)
	{
		$aThread = $this->database()->select('mt.*, mtu.is_archive AS user_is_archive')
			->from(Phpfox::getT('mail_thread'), 'mt')
			->join(Phpfox::getT('mail_thread_user'), 'mtu', 'mtu.thread_id = mt.thread_id')
			->where('mt.thread_id = ' . (int) $iThreadId)
			->execute('getSlaveRow');

		if (!isset($aThread['thread_id']))
		{
			return array(false, false);
		}

		$aThread['users'] =  $this->database()->select(Phpfox::getUserField())
			->from(Phpfox::getT('mail_thread_user'), 'th')
			->join(Phpfox::getT('user'), 'u', 'u.user_id = th.user_id')
			->where('th.thread_id = ' . (int) $aThread['thread_id'])
			->execute('getSlaveRows');

		$aThread['user_id'] = [];
		foreach ($aThread['users'] as $aUser) {
			$aThread['user_id'][] = $aUser['user_id'];
		}

		$iLimit = 10;
		if ($iOffset == 0)
			$iOffset = ($iPage * $iLimit);

		if ($getLatest) {
			$iLimit = 1;
		}

		$aMessages = $this->database()->select('mtt.*, ' . Phpfox::getUserField())
			->from(Phpfox::getT('mail_thread_text'), 'mtt')
			->join(Phpfox::getT('user'), 'u', 'u.user_id = mtt.user_id')
			->where('mtt.thread_id = ' . (int) $iThreadId)
			->order('mtt.time_stamp DESC')
			->limit($iOffset, $iLimit, null, false, true)
			->execute('getSlaveRows');

		if ($getLatest) {
			if (!isset($aMessages[0])) {
				throw error(_p('Message not found.'));
			}
			return $aMessages[0];
		}

		$aMessages = array_reverse($aMessages);

		foreach ($aMessages as $iKey => $aMail)
		{
			if ($aMail['total_attachment'] > 0)
			{
				list(, $aAttachments) = Phpfox::getService('attachment')->get(array('AND attachment.item_id = ' . $aMail['message_id'] . ' AND attachment.category_id = \'mail\' AND is_inline = 0'), 'attachment.attachment_id DESC', false);

				$aMessages[$iKey]['attachments'] = $aAttachments;
			}

			$aMessages[$iKey]['forwards'] = array();
			if ($aMail['has_forward'])
			{
				$aMessages[$iKey]['forwards'] = $this->database()->select('mtt.*, ' . Phpfox::getUserField())
					->from(Phpfox::getT('mail_thread_forward'), 'mtf')
					->join(Phpfox::getT('mail_thread_text'), 'mtt', 'mtt.message_id = mtf.copy_id')
					->join(Phpfox::getT('user'), 'u', 'u.user_id = mtt.user_id')
					->where('mtf.message_id = ' . $aMail['message_id'])
					->execute('getSlaveRows');
			}
		}

		return array($aThread, $aMessages);
	}

	public function getThreadsForExport($aThreads)
	{
		define('PHPFOX_XML_SKIP_STAMP', true);

		$sThreads = implode(',', $aThreads);

		if (empty($sThreads))
		{
			return Phpfox_Error::set(_p('unable_to_export_your_messages'));
		}

		$aThreads = $this->database()->select('mt.*')
			->from(Phpfox::getT('mail_thread'), 'mt')
			->join(Phpfox::getT('mail_thread_user'), 'mtu', 'mtu.thread_id = mt.thread_id AND mtu.user_id = ' . Phpfox::getUserId())
			->where('mt.thread_id IN(' . $sThreads . ')')
			->execute('getSlaveRows');

		if (!count($aThreads))
		{
			return Phpfox_Error::set(_p('unable_to_export_your_messages'));
		}

		$oXmlBuilder = Phpfox::getLib('xml.builder');
		$oXmlBuilder->addGroup('threads');

		foreach ($aThreads as $iKey => $aThread)
		{
			$aMessages = $this->database()->select('mtt.*, ' . Phpfox::getUserField())
				->from(Phpfox::getT('mail_thread_text'), 'mtt')
				->join(Phpfox::getT('user'), 'u', 'mtt.user_id = u.user_id')
				->where('thread_id = ' . (int) $aThread['thread_id'])
				->execute('getSlaveRows');

			$aUsers = $this->database()->select('th.is_read, ' . Phpfox::getUserField())
				->from(Phpfox::getT('mail_thread_user'), 'th')
				->join(Phpfox::getT('user'), 'u', 'u.user_id = th.user_id')
				->where('th.thread_id = ' . (int) $aThread['thread_id'])
				->execute('getSlaveRows');

			$oXmlBuilder->addGroup('thread', array(
					'id' => $aThread['thread_id']
				)
			);

			$iCnt = 0;
			$sUsers = '';
			foreach ($aUsers as $aUser)
			{
				$iCnt++;
				if ($iCnt != 1)
				{
					$sUsers .= ',';
				}
				$sUsers .= $aUser['full_name'];
			}

			$oXmlBuilder->addTag('conversation', $sUsers);
			$oXmlBuilder->addTag('url', Phpfox_Url::instance()->makeUrl('mail.thread', array('id' => $aThread['thread_id'])));

			$oXmlBuilder->addGroup('messages');
			foreach ($aMessages as $aMessage)
			{
				$oXmlBuilder->addGroup('message', array(
						'id' => $aMessage['message_id']
					)
				);

				$oXmlBuilder->addTag('time', $aMessage['time_stamp']);
				$oXmlBuilder->addTag('user', $aMessage['full_name']);
				$oXmlBuilder->addTag('content', Phpfox::getLib('parse.output')->parse($aMessage['text']));
				$oXmlBuilder->closeGroup();
			}
			$oXmlBuilder->closeGroup();

			$oXmlBuilder->closeGroup();
		}

		$oXmlBuilder->closeGroup();

		$sFile = md5(Phpfox::getUserId() . uniqid()) . '.xml';

		Phpfox_File::instance()->writeToCache($sFile, $oXmlBuilder->output());

		return PHPFOX_DIR_CACHE . $sFile;
	}
}