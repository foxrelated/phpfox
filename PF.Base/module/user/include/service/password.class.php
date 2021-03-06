<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Service_Password
 */
class User_Service_Password extends Phpfox_Service
{	
	public function __construct()
	{
		$this->_sTable = Phpfox::getT('user');
	}
	
	public function requestPassword($aVals)
	{
		$aUser = $this->database()->select('user_id, profile_page_id, email, full_name')
			->from($this->_sTable)
			->where('email = \'' . $this->database()->escape($aVals['email']) . '\'')
			->execute('getSlaveRow');

        if (!isset($aUser['user_id'])) {
            return Phpfox_Error::set(_p('not_a_valid_email'));
        }


        if (empty($aUser['email']) || $aUser['profile_page_id'] > 0) {
            return Phpfox_Error::set(_p('unable_to_attain_a_password_for_this_account_dot'));
        }

		// Send the user an email
		$sHash = md5($aUser['user_id'] . $aUser['email'] . Phpfox::getParam('core.salt'));
		$sLink = Phpfox_Url::instance()->makeUrl('user.password.verify', array('id' => $sHash));
		Phpfox::getLib('mail')->to($aUser['user_id'])
			->subject(array('user.password_request_for_site_title', array('site_title' => Phpfox::getParam('core.site_title'))))
			->message(array('user.you_have_requested_for_us_to_send_you_a_new_password_for_site_title', array(
						'site_title' => Phpfox::getParam('core.site_title'),
						'link' => $sLink
					)
				)
			)
			->send(false, true);
		
		$this->database()->delete(Phpfox::getT('password_request'), 'user_id = ' . $aUser['user_id']);
		$this->database()->insert(Phpfox::getT('password_request'), array(
				'user_id' => $aUser['user_id'],
				'request_id' => $sHash,
				'time_stamp' => PHPFOX_TIME
			)
		);
		
		return true;
	}
	
	public function isValidRequest($sId)
	{
		$aRequest = $this->database()->select('r.*, u.email, u.full_name')
			->from(Phpfox::getT('password_request'), 'r')
			->join($this->_sTable, 'u', 'u.user_id = r.user_id')
			->where('r.request_id = \'' . $this->database()->escape($sId) . '\'')
			->execute('getSlaveRow');
			
		if (!isset($aRequest['user_id']))
		{
			return Phpfox_Error::set(_p('not_a_valid_password_request'));
		}
		if (md5($aRequest['user_id'] . $aRequest['email'] . Phpfox::getParam('core.salt')) != $sId)
		{
			return Phpfox_Error::set(_p('password_request_id_does_not_match'));
		}
		if (Phpfox::getParam('user.verify_email_timeout') > 0 && ($aRequest['time_stamp'] < (PHPFOX_TIME - (Phpfox::getParam('user.verify_email_timeout')*60))))
		{
			$this->database()->delete(Phpfox::getT('password_request'), 'request_id = "' . $this->database()->escape($sId) . '"');
			return Phpfox_Error::set(_p('request_expired_please_try_again'));
		}
		return true;
	}
	
	public function verifyRequest($sId)
	{
        $sSelect = 'r.*, u.email, u.full_name';
        $sWhere = 'r.request_id = \'' . $this->database()->escape($sId) . '\'';
        $sJoin = 'u.user_id = r.user_id';
        
		if ($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_start'))
		{
			eval($sPlugin);
		}
		$aRequest = $this->database()->select($sSelect)
			->from(Phpfox::getT('password_request'), 'r')
			->join($this->_sTable, 'u', $sJoin)
			->where($sWhere)
			->execute('getSlaveRow');

		(($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_2')) ? eval($sPlugin) : false);

		if (!isset($aRequest['user_id']))
		{
			return Phpfox_Error::set(_p('not_a_valid_password_request'));
		}		
		
		if ($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_check_1'))
		{
			eval($sPlugin);
		}
		
		if (md5($aRequest['user_id'] . $aRequest['email'] . Phpfox::getParam('core.salt')) != $sId)
		{
			return Phpfox_Error::set(_p('password_request_id_does_not_match'));
		}
		
		$sNewPassword = $this->generatePassword(15, 10);
		$sSalt = $this->_getSalt();			
		$aUpdate = array();
		$aUpdate['password'] = Phpfox::getLib('hash')->setHash($sNewPassword, $sSalt);
		$aUpdate['password_salt'] = $sSalt;	

		(($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_3')) ? eval($sPlugin) : false);
		$this->database()->update($this->_sTable, $aUpdate, 'user_id = ' . $aRequest['user_id']);
		$this->database()->delete(Phpfox::getT('password_request'), 'user_id = ' . $aRequest['user_id']);
		
		// Send the user an email
		$sLink = Phpfox_Url::instance()->makeUrl('user.login');
		
		(($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_4')) ? eval($sPlugin) : false);
		Phpfox::getLib('mail')->to($aRequest['user_id'])
			->subject(array('user.new_password_for_site_title', array('site_title' => Phpfox::getParam('core.site_title'))))
			->message(array('user.you_have_requested_for_us_to_send_you_a_new_password_for_site_title_with_password', array(
						'site_title' => Phpfox::getParam('core.site_title'),
						'password' => $sNewPassword,
						'link' => $sLink
					)
				)
			)
			->send(false, true);
			
		if ($sPlugin = Phpfox_Plugin::get('user.service_password_verifyrequest_end'))
		{
			eval($sPlugin);
		}
		return true;
	}
	
	public function updatePassword($sRequest, $aVals)
	{
		if (!isset($aVals['newpassword']) || !isset($aVals['newpassword2']) || $aVals['newpassword'] != $aVals['newpassword2'])
		{
			return Phpfox_Error::set(_p('passwords_do_not_match'));
		}
		$aRequest = $this->database()->select('r.*, u.email, u.full_name')
			->from(Phpfox::getT('password_request'), 'r')
			->join($this->_sTable, 'u', 'u.user_id = r.user_id')
			->where('r.request_id = \'' . $this->database()->escape($sRequest) . '\'')
			->execute('getSlaveRow');
			
		$sSalt = $this->_getSalt();			
		$aUpdate = array();
		$aUpdate['password'] = Phpfox::getLib('hash')->setHash($aVals['newpassword'], $sSalt);
		$aUpdate['password_salt'] = $sSalt;	

		
		$this->database()->update($this->_sTable, $aUpdate, 'user_id = ' . $aRequest['user_id']);
		$this->database()->delete(Phpfox::getT('password_request'), 'user_id = ' . $aRequest['user_id']);
		return true;
	}
	/**
	 * If a call is made to an unknown method attempt to connect
	 * it to a specific plug-in with the same name thus allowing 
	 * plug-in developers the ability to extend classes.
	 *
	 * @param string $sMethod is the name of the method
	 * @param array $aArguments is the array of arguments of being passed
     *
     * @return null
	 */
	public function __call($sMethod, $aArguments)
	{
		/**
		 * Check if such a plug-in exists and if it does call it.
		 */
		if ($sPlugin = Phpfox_Plugin::get('user.service_password__call'))
		{
			return eval($sPlugin);
		}
			
		/**
		 * No method or plug-in found we must throw a error.
		 */
		Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
	}	
	
	public function generatePassword($iLength = 9, $iStrength = 10)
	{
		$sVowels = 'aeuy';
		$sConsonants = 'bdghjmnpqrstvz';
		
		if ($iStrength > 1) 
		{
			$sConsonants .= 'BDGHJLMNPQRSTVWXZ';
		}
		
		if ($iStrength > 2) 
		{
			$sVowels .= "AEUY";
		}
		
		if ($iStrength > 4) 
		{
			$sConsonants .= '23456789';
		}
		
		if ($iStrength > 8) 
		{
			$sConsonants .= '@#$%{}[]!?*;:';
		}
	
		$sPassword = '';
		$sAlt = time() % 2;
		for ($i = 0; $i < $iLength; $i++) 
		{
			if ($sAlt == 1) 
			{
				$sPassword .= $sConsonants[(rand() % strlen($sConsonants))];
				$sAlt = 0;
			} 
			else 
			{
				$sPassword .= $sVowels[(rand() % strlen($sVowels))];
				$sAlt = 1;
			}
		}
		return $sPassword;
	}
	
	private function _getSalt($iTotal = 3)
	{
		$sSalt = '';
		for ($i = 0; $i < $iTotal; $i++)
		{
			$sSalt .= chr(rand(33, 126));
		}
		
		return $sSalt;
	}	
}
