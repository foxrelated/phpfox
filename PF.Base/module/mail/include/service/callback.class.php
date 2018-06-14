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
 * @version 		$Id: callback.class.php 6051 2013-06-11 13:33:49Z Raymond_Benc $
 */
class Mail_Service_Callback extends Phpfox_Service 
{
	public function  __construct()
	{
		$this->_sTable = Phpfox::getT('mail');
	}
	
	public function addTrack($iId, $iUserId = null)
	{
        return $this->database()->insert(Phpfox::getT('track'), [
            'type_id'    => 'mail',
            'item_id'    => (int)$iId,
            'user_id'    => ($iUserId === null ? Phpfox::getUserBy('user_id') : $iUserId),
            'ip_address' => '',
            'time_stamp' => PHPFOX_TIME
        ]);
    }
    
    public function removeTrack($iId, $iUserId = null)
	{
		return $this->database()->delete(Phpfox::getT('mail_track'), 'item_id = ' . (int) $iId . ' AND user_id = ' . ($iUserId === null ? Phpfox::getUserBy('user_id') : $iUserId));
	}	
	
	public function getNotificationSettings()
	{
		return array('mail.new_message' => array(
				'phrase' => _p('new_messages'),
				'default' => 1
			)
		);		
	}
	
	public function getProfileSettings()
	{
		return array(
			'mail.send_message' => array(
				'phrase' => _p('send_you_a_message'),
				'default' => '1',
				'anyone' => false
			)			
		);		
	}

	public function getNotificationLink($mId, $mTotal = null)
	{
		$sImage = '<img src="' . Phpfox_Template::instance()->getStyle('image', 'misc/email.png') . '" alt="" class="v_middle" />';
		if (is_array($mId) && $mTotal === null)
		{	
			return _p('li_a_href_link_email_image_new_messages_messages_number_a_li',array('link' => Phpfox_Url::instance()->makeUrl('mail'), 'email_image' => $sImage, 'messages_number' => (isset($mId['mail']) ? $mId['mail'] : '0')));
		}
		else 
		{			
			return '<li><a href="' . Phpfox_Url::instance()->makeUrl('mail') . '" class="js_nofitication_' . $mId . '">' . $sImage . ' ' . ($mTotal > 1 ? _p('total_new_messages', array('total' => $mTotal)) : _p('1_new_message')) . '</a></li>';
		}
	}
	
	public function getAttachmentField()
	{
		return array(
			'mail',
			'mail_id'
		);
	}	
	
	public function getNotificationFeedSend($aRow)
	{		
		return array(
			'message' => _p('user_link_sent_you_a_message', array(($aRow['user_id'] > 0 ? 'user' : 'user_link') => ($aRow['user_id'] > 0 ? $aRow : Phpfox::getParam('core.site_title')))),
			'link' => Phpfox_Url::instance()->makeUrl('mail.view', array('id' => $aRow['item_id']))
		);
	}	
	
	public function getUserCountFieldSend()
	{
		return 'mail_new';
	}

	/**
	 * Action to take when user cancelled their account
	 * @param int $iUser
	 */
	public function onDeleteUser($iUser)
	{
        $aThreads = $this->database()->select('thread_id')
            ->from(Phpfox::getT('mail_thread_user'))
            ->where('user_id = ' . (int) $iUser)
            ->execute('getSlaveRows');
        foreach ($aThreads as $aThread) {
            $iCount = $this->database()->select('COUNT(*)')
                ->from(Phpfox::getT('mail_thread_user'))
                ->where('thread_id = ' . (int) $aThread['thread_id'])
                ->execute('getSlaveField');
            if ($iCount > 2) {
                $this->database()->delete(Phpfox::getT('mail_thread_text'), 'user_id = ' . $iUser);
                $this->database()->delete(Phpfox::getT('mail_thread_user'), 'user_id = ' . $iUser);

                $aLastMess = $this->database()->select('message_id, user_id, time_stamp')
                    ->from(Phpfox::getT('mail_thread_text'))
                    ->where('thread_id = ' . (int) $aThread['thread_id'])
                    ->order('time_stamp DESC')
                    ->execute('getSlaveRow');

                $this->database()->update(Phpfox::getT('mail_thread'), array('last_id' => $aLastMess['message_id'], 'time_stamp' => $aLastMess['time_stamp']), 'thread_id = ' . $aThread['thread_id']);
                $this->database()->update(Phpfox::getT('mail_thread_user'), array('is_read' => 1, 'is_sent' => 1, 'is_sent_update' => 1), 'thread_id = ' . $aThread['thread_id'] . ' AND user_id = ' . $aLastMess['user_id']);
            }
            else {
                $this->database()->delete(Phpfox::getT('mail_thread_text'), 'thread_id = ' . $aThread['thread_id']);
                $this->database()->delete(Phpfox::getT('mail_thread_user'), 'thread_id = ' . $aThread['thread_id']);
            }
        }
		// get all the mail in this user's inbox		
		$aMails = $this->database()
			->select('mail_id, owner_user_id, viewer_user_id')
			->from($this->_sTable)
			->where('owner_user_id = ' . (int)$iUser . ' OR viewer_user_id = ' . (int)$iUser)
			->execute('getSlaveRows');
			
		foreach ($aMails as $aMail)
		{
			if (Phpfox::getParam('mail.delete_sent_when_account_cancel'))
			{// if that setting is enabled then we can do a hard delete:
				$this->database()->delete($this->_sTable, 'mail_id = ' . $aMail['mail_id']);
				$this->database()->delete(Phpfox::getT('mail_text'), 'mail_id = ' . $aMail['mail_id']);
				// soft delete
			}
			else
			{
				$bSent = $aMail['owner_user_id'] == $iUser;
				Phpfox::getService('mail.process')->delete($aMail['mail_id'], $bSent);
			}
		}
		$this->database()->delete(Phpfox::getT('mail_folder'), 'user_id = ' . (int)$iUser);
	}
	
	public function reparserList()
	{
		return array(
			'name' => _p('mail_text'),
			'table' => 'mail_text',
			'original' => 'text',
			'parsed' => 'text_parsed',
			'item_field' => 'mail_id'
		);
	}

	public function getSiteStatsForAdmins()
	{
		$iToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		
		return array(
			'phrase' => _p('mesages_sent'),
			'value' => $this->database()->select('COUNT(*)')
				->from(Phpfox::getT('mail'))
				->where('time_stamp >= ' . $iToday)
				->execute('getSlaveField')
		);
	}	

	public function getReportRedirect($iId)
	{
		return Phpfox_Url::instance()->makeUrl('admincp.mail.view', array('id' => $iId));
	}

	public function updateCounterList()
	{
		$aList = array();
		
		$aList[] = array(
			'name' => _p('update_mail_count'),
			'id' => 'mail-count'			
		);
		
		return $aList;
	}			
	
	public function updateCounter($iId, $iPage, $iPageLimit)
	{		
		$iCnt = $this->database()->select('COUNT(*)')
			->from(Phpfox::getT('user'))
			->execute('getSlaveField');
			
		$aRows = $this->database()->select('u.user_id')
			->from(Phpfox::getT('user'), 'u')
			->limit($iPage, $iPageLimit, $iCnt)
			->execute('getSlaveRows');						
					
		foreach ($aRows as $aRow)
		{
			$iTotalNewMessages = $this->database()->select('COUNT(*)')
				->from(Phpfox::getT('mail'), 'm')
				->join(Phpfox::getT('user'), 'u', 'u.user_id = m.owner_user_id')
				->where('m.viewer_user_id = ' . (int) $aRow['user_id'] . ' AND m.viewer_is_new = 1 AND m.viewer_type_id = 0')
				->execute('getSlaveField');		
				
			$this->database()->update(Phpfox::getT('user_count'), array('mail_new' => $iTotalNewMessages), 'user_id = ' . (int) $aRow['user_id']);
		}		
			
		return $iCnt;		
	}

	public function getSqlTitleField()
	{
		return array(
			'table' => 'mail',
			'field' => 'subject'
		);
	}

    public function getGlobalNotifications()
    {
        $iTotal = Phpfox::getService('mail')->getUnseenTotal();

        if ($sPlugin = Phpfox_Plugin::get('mail.service_callback_getglobalnotifications')) {
            eval($sPlugin);
        }

        if ($iTotal > 0) {
            $iTotal = Phpfox::getService('core.helper')->shortNumberOver100($iTotal);
            Phpfox_Ajax::instance()->call('$(\'span#js_total_new_messages\').html(\'' . $iTotal . '\').css({display: \'block\'}).show();');
        }
    }

	/**
	 * This function checks if the current user is either the sender or the receiver of iMailId
	 * Used to validate who can download attachments
	 * @param int $iMailId
	 * @return bool
	 */
	public function attachmentControl($iMailId)
	{
        $iThreadId = (int) $this->database()->select('thread_id')
            ->from(Phpfox::getT('mail_thread_text'))
            ->where('message_id = ' . (int) $iMailId)
            ->execute('getSlaveField');

        if ($iThreadId <= 0)
        {
            return false;
        }

        $iUserCheck = (int) $this->database()->select('COUNT(*)')
            ->from(Phpfox::getT('mail_thread_user'))
            ->where('thread_id = ' . (int) $iThreadId . ' AND user_id = ' . (int) Phpfox::getUserId())
            ->execute('getSlaveField');

        return ($iUserCheck > 0 ? true : false);
	}
	
	public function __call($sMethod, $aArguments)
	{
		if ($sPlugin = Phpfox_Plugin::get('mail.service_callback__call'))
		{
			eval($sPlugin);
            return null;
		}
			
		Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
	}	
}