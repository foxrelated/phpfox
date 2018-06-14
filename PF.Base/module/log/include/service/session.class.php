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
 * @package  		Module_Log
 * @version 		$Id: session.class.php 7244 2014-03-31 17:41:12Z Fern $
 */
class Log_Service_Session extends Phpfox_Service
{
 	private $_aSession = array();
 	private $_sSecurityToken;
 	
 	/**
	 * Class constructor
	 */	
	public function __construct()
 	{
 		$this->_sTable = Phpfox::getT('log_session');	
 	}
	
	public function getSessionId()
	{
		return (isset($this->_aSession['session_hash']) ? $this->_aSession['session_hash'] : 0);
	}
	
	public function get($sName, $mDef = '')
	{		
		return (isset($this->_aSession[$sName]) ? $this->_aSession[$sName] : $mDef);
	}
	
	public function verifyToken()
	{
		return;
	}
		
	public function getToken()
	{
		if (defined('PHPFOX_INSTALLER'))
		{
			return false;
		}
		
		static $sToken;
		
		if ($sToken)
		{
			return $sToken;
		}
		
		$sToken = (md5(Phpfox_Request::instance()->getIdHash() . md5(Phpfox::getParam('core.salt'))));

		return $sToken;
	}
	
	public function getActiveTime()
	{
		return (PHPFOX_TIME - (Phpfox::getParam('log.active_session') * 60));
	}
	
	public function setUserSession()
	{

		if (redis()->enabled()) {
			$update = ['last_activity' => PHPFOX_TIME, 'last_ip_address' => Phpfox::getIp()];
			redis()->set('last/activity/' . user()->id, $update);

			redis()->lrem('online', 0, user()->id);
			redis()->lpush('online', user()->id);
			redis()->expire('online', 900);

            if (auth()->isLoggedIn() && !Phpfox::getCookie('last_login')) {
                Phpfox::setCookie('last_login', PHPFOX_TIME, (PHPFOX_TIME + (Phpfox::getParam('log.active_session') * 60)));
                $this->database()->update(Phpfox::getT('user'), array('last_login' => PHPFOX_TIME), 'user_id = ' . Phpfox::getUserId());
            }

			return null;
		}


		$oSession = Phpfox::getLib('session');
		$oRequest = Phpfox_Request::instance();
		
		$sSessionHash = $oSession->get('session');		

		if ($sSessionHash)
        {
            $this->_aSession = Phpfox::getService('user.auth')->getUserSession();

            if (!isset($this->_aSession['session_hash']))
            {
                $this->database()->where("s.session_hash = '" . $this->database()->escape($oSession->get('session')) . "' AND s.id_hash = '" . $this->database()->escape($oRequest->getIdHash()) . "'");

                $this->_aSession = $this->database()->select('s.session_hash, s.id_hash, s.captcha_hash, s.user_id')
                    ->from($this->_sTable, 's')
                    ->execute('getSlaveRow');
            }
        }
		
		$sLocation = $oRequest->get(PHPFOX_GET_METHOD);
		$sLocation = substr($sLocation, 0, 244);
		$sBrowser = substr(Phpfox_Request::instance()->getBrowser(), 0, 99);
		$sIp = Phpfox_Request::instance()->getIp();

		/**
		 * @todo Needs to be added into the 'setting' db table
		 */
		$aDisAllow = array(
			'captcha/image'
		);
		
		// Don't log a session into the DB if we disallow it
		if (Phpfox_Url::instance()->isUrl($aDisAllow))
		{
			return null;
		}	
		
		$bIsForum = (strstr($sLocation, 'forum') ? true : false);
		$iForumId = 0;
		if ($bIsForum)
		{
			$aForumIds = explode('-', $oRequest->get('req2'));
			if (isset($aForumIds[(count($aForumIds) - 1)]))
			{
				$iForumId = (int) $aForumIds[(count($aForumIds) - 1)];				
			}			
		}
		
		$iIsHidden = 0;
		if (!isset($this->_aSession['session_hash']))
        {
            $sSessionHash = $oRequest->getSessionHash();
            if(Phpfox::getUserId() > 0)
            {
                $this->database()->delete($this->_sTable, 'user_id = ' . Phpfox::getUserId());
            }
            $this->database()->insert($this->_sTable, array(
                    'session_hash' => $sSessionHash,
                    'id_hash' => $oRequest->getIdHash(),
                    'user_id' => Phpfox::getUserId(),
                    'last_activity' => PHPFOX_TIME,
                    'location' => $sLocation,
                    'is_forum' => ($bIsForum ? '1' : '0'),
                    'forum_id' => $iForumId,
                    'im_hide' => $iIsHidden,
                    'ip_address' => $sIp,
                    'user_agent' => $sBrowser
                )
            );
            $oSession->set('session', $sSessionHash);
        }
        elseif (isset($this->_aSession['session_hash']))
        {
            $this->database()->update($this->_sTable, array(
                'last_activity' => PHPFOX_TIME,
                'user_id' => Phpfox::getUserId(),
                "location" => $sLocation,
                "is_forum" => ($bIsForum ? "1" : "0"),
                "forum_id" => $iForumId,
                'im_hide' => $iIsHidden,
                "ip_address" => $sIp,
                "user_agent" => $sBrowser
            ), "session_hash = '" . $this->_aSession["session_hash"] . "'");
        }
			
		if (!Phpfox::getCookie('visit'))
		{
			Phpfox::setCookie('visit', PHPFOX_TIME);			
		}		
		
		if (Phpfox::isUser()) {
			if (!Phpfox::getCookie('last_login')) {
//				Phpfox::setCookie('last_login', PHPFOX_TIME, (PHPFOX_TIME + (Phpfox::getParam('log.active_session') * 60)));
//				if (Phpfox::getUserBy('last_activity') < (PHPFOX_TIME + (Phpfox::getParam('log.active_session') * 60))) {
//					$this->database()->update(Phpfox::getT('user'), array('last_activity' => PHPFOX_TIME, 'last_ip_address' => Phpfox::getIp(),'last_login' => PHPFOX_TIME), 'user_id = ' . Phpfox::getUserId());
//				}
			}
            if (!Phpfox::getParam('user.disable_store_last_user')) {
                $update = ['last_activity' => PHPFOX_TIME, 'last_ip_address' => Phpfox::getIp()];
                $this->database()->update(Phpfox::getT('user'), $update, 'user_id = ' . Phpfox::getUserId());
            }

            if(mt_rand(1,30) ==1){
                $this->database()->insert(Phpfox::getT('user_ip'), array(
                        'user_id' => Phpfox::getUserId(),
                        'type_id' => 'session_login',
                        'ip_address' => Phpfox::getIp(),
                        'time_stamp' => PHPFOX_TIME
                    )
                );
            }
		}
	}
	
	public function getActiveUsers($aCond)
	{
		$aCond[] = 'AND s.last_activity > \'' . $this->getActiveTime() . '\'';		
		
		$iCnt = (int) $this->database()->select('COUNT(DISTINCT u.user_id)')
			->from(Phpfox::getT('log_session'), 's')
			->join(Phpfox::getT('user'), 'u', 'u.user_id = s.user_id')
			->where($aCond)
			->execute('getSlaveField');
		
		$aRows = $this->database()->select(Phpfox::getUserField())
			->from(Phpfox::getT('log_session'), 's')
			->join(Phpfox::getT('user'), 'u', 'u.user_id = s.user_id')
			->where($aCond)
            ->group('u.user_id', true)
			->order('s.last_activity')
			->limit(20)
			->execute('getSlaveRows');

		return array($iCnt, $aRows);
	}
	
	public function getActiveLocation($sLocation)
	{
		$sLocation = trim($sLocation, '/');
		
		switch ($sLocation)
		{
			case 'admincp':
				if ($sLocation == 'admincp')
				{
					$sLocation = _p('admincp_dashboard');
				}
				break;
			default:
				$sLocation = _p('site_index');
				break;
		}
		
		return $sLocation;
	}
	
	public function getOnlineStats()
	{
		$sOnlineMembers = $this->database()->select('COUNT(DISTINCT user_id)')
			->from(Phpfox::getT('log_session'))
			->where('user_id > 0 AND last_activity > ' . (PHPFOX_TIME - (Phpfox::getParam('log.active_session')*60)))
			->execute('getSlaveField');
			
        $sOnlineGuests = 0;

		return array(
			'members' => (int) $sOnlineMembers,
			'guests' => (int) $sOnlineGuests
		);
	}
	
	public function getOnlineMembers()
	{
		static $iTotal = null;
		
		if ($iTotal === null)
		{
			$iTotal = $this->database()->select('COUNT(DISTINCT user_id)')
				->from(Phpfox::getT('log_session'))
				->where('user_id > 0 AND last_activity > ' . (PHPFOX_TIME - (Phpfox::getParam('log.active_session')*60)))
				->execute('getSlaveField');		
		}
		
		return $iTotal;
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
		if ($sPlugin = Phpfox_Plugin::get('log.service_session___call'))
		{
			eval($sPlugin);
            return null;
		}
			
		/**
		 * No method or plug-in found we must throw a error.
		 */
		Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
	}
	
	private function _log($sMessage)
	{
		if (PHPFOX_DEBUG)
		{
			Phpfox_Error::trigger($sMessage, E_USER_ERROR);
		}
		exit($sMessage);
	}
}