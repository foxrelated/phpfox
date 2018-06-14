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
 * @package 		Phpfox_Service
 * @version 		$Id: process.class.php 6750 2013-10-08 13:58:53Z Miguel_Espinoza $
 */
class Subscribe_Service_Purchase_Process extends Phpfox_Service 
{
	/**
	 * Class constructor
	 */	
	public function __construct()
	{	
		$this->_sTable = Phpfox::getT('subscribe_purchase');	
	}
	
	public function add($aVals, $iUserId = null)
	{		
		if ($iUserId === null)
		{
			Phpfox::isUser(true);
            $iUserId = Phpfox::getUserId();
		}
		//Delete un-complete purchased with the same user and package_id
        $this->database()->delete($this->_sTable, 'user_id=' . (int) $iUserId . ' AND package_id=' . (int) $aVals['package_id'] . ' AND status IS NULL');
		$aForms = array(
			'package_id' => array(
				'message' => _p('package_is_required'),
				'type' => 'int:required'
			),
			'currency_id' => array(
				'message' => _p('currency_is_required'),
				'type' => array('string:required', 'regex:currency_id')
			),
			'price' => array(
				'message' => _p('price_is_required'),
				'type' => 'price:required'
			)
		);		
		
		$aVals = $this->validator()->process($aForms, $aVals);
		
		if (!Phpfox_Error::isPassed())
		{
			return false;
		}		
		
		$aExtra = array(
			'user_id' => ($iUserId === null ? Phpfox::getUserId() : $iUserId),
			'time_stamp' => PHPFOX_TIME	
		);
		
		$iId = $this->database()->insert($this->_sTable, array_merge($aExtra, $aVals));
		
		return $iId;
	}
	
	public function update($iPurchaseId, $iPackageId, $sStatus, $iUserId, $iUserGroupId, $iFailUserGroupId)
	{		
		$sLink = Phpfox_Url::instance()->makeUrl('subscribe.view', array('id' => $iPurchaseId));
		switch ($sStatus)
		{
			case 'completed':
                Phpfox::getService('user.process')->updateUserGroup($iUserId, $iUserGroupId);
				Phpfox::log('Moving user "' . $iUserId . '" to user group "' . $iUserGroupId . '"');
				$sSubject = array('subscribe.membership_successfully_updated_site_title', array('site_title' => Phpfox::getParam('core.site_title')));
				$sMessage = array('subscribe.your_membership_on_site_title_has_successfully_been_updated', array(
						'site_title' => Phpfox::getParam('core.site_title'),
						'link' => $sLink
					)
				);
				$this->database()->updateCounter('subscribe_package', 'total_active', 'package_id', $iPackageId);
				$this->database()->update(Phpfox::getT('user_field'), array('subscribe_id' => '0'), 'user_id = ' . (int) $iUserId);
				break;
			case 'pending':
				$sSubject = array('subscribe.membership_pending_site_title', array('site_title' => Phpfox::getParam('core.site_title')));
				$sMessage = array('subscribe.your_membership_subscription_on_site_title_is_currently_pending', array(
						'site_title' => Phpfox::getParam('core.site_title'),
						'link' => $sLink
					)
				);
				$this->database()->update(Phpfox::getT('user_field'), array('subscribe_id' => $iPurchaseId), 'user_id = ' . (int) $iUserId);
				break;
			case 'cancel':
				// Store in the log that this user cancelled the subscription.
				$this->database()->insert(Phpfox::getT('api_gateway_log'), array(
					'log_data' => 'cancelled_subscription user_' . (int)($iUserId) . ' purchaseid_' . (int)$iPurchaseId . ' packageid_' . (int)$iPackageId,
					'time_stamp' => PHPFOX_TIME
				));
				break;
		}
		if ($sPlugin = Phpfox_Plugin::get('subscribe.service_purchase_process_update_pre_log'))
		{
			eval($sPlugin);
		}
		Phpfox::log('Updating status of purchase order');
		
		$this->database()->update($this->_sTable, array('status' => $sStatus), 'purchase_id = ' . (int) $iPurchaseId);	
		
		Phpfox::log('Sending user an email');
		Phpfox::getLib('mail')->to($iUserId)
			->subject(isset($sSubject) ? $sSubject : '')
			->message(isset($sMessage) ? $sMessage : '')
			->notification('subscribe.payment_update')
			->send();		
		Phpfox::log('Email sent');
	}
	
	/* This function is called from a cron job.
	*	It searches the database for users who cancelled their subscription before their time was up
	*	and moves them to the correct user group.
	*	It is called once a day and gets the soonest subscription time
	*/
	public function downgradeExpiredSubscribers()
	{
	    //Purchase more points if recurring is available
	    $this->recurringWithPoint();

		// 1. The shortest term is 1 month
		$iOneMonthAgo = PHPFOX_TIME - (60 *60 * 24 * 30);
		
		// 3. Find records in api_gateway_log for people that have cancelled their subscription.
		$aExpiredRecords = $this->database()->select('*')
			->from(Phpfox::getT('api_gateway_log'))
			->where('log_data LIKE "cancelled_subscription%" AND time_stamp < ' . $iOneMonthAgo)
			->execute('getSlaveRows');
			
		// 4. Find their subscription.
		$aSubscriptionsRows = $this->database()->select('*')
			->from(Phpfox::getT('subscribe_package'))
			->execute('getSlaveRows');
		
		$iCount = 0;
		foreach ($aExpiredRecords as $aExpired)
		{
			// parse the log
			if (preg_match('/user_(?P<user_id>[0-9]+) purchaseid_(?P<purchase_id>[0-9]+) packageid_(?P<package_id>[0-9]+)/', $aExpired['log_data'], $aRecord))
			{
				// find when should this subscription expire
				$iThisExpires = Phpfox::getService('subscribe.purchase')->getExpireTime($aRecord['purchase_id']);
				
				if ($iThisExpires > PHPFOX_TIME)
				{
					continue;
				}
				// find the fail user group
				foreach ($aSubscriptionsRows as $aSubs)
				{
					if ($aSubs['package_id'] == $aRecord['package_id'])
					{
						// Move user to the on fail user group
                        Phpfox::getService('user.process')->updateUserGroup($aRecord['user_id'], $aSubs['fail_user_group']);
						
						// Update this record so we dont process it again
						$this->database()->update(Phpfox::getT('api_gateway_log'), array('log_data' => 'processed ' . $aExpired['log_data']), 'log_id = ' . $aExpired['log_id']);
						$this->database()->update(Phpfox::getT('user_field'), array('subscribe_id' => $aRecord['purchase_id']), 'user_id = ' . (int)$aRecord['user_id']);
						$iCount++;
					}
				}
			}
		}
		
		return $iCount;
	}

    public function recurringWithPoint()
    {
        $aSubscriptionPackages = Phpfox::getLib('database')->select('*')
            ->from(':subscribe_package')
            ->where('recurring_period > 0')
            ->executeRows();

        if (count($aSubscriptionPackages)) {
            foreach ($aSubscriptionPackages as $aSubscriptionPackage) {
                switch ($aSubscriptionPackage['recurring_period']) {
                    case 1:
                        $iDays = 30;
                        break;
                    case 2:
                        $iDays = 90;
                        break;
                    case 3:
                        $iDays = 180;
                        break;
                    case 4:
                        $iDays = 365;
                        break;
                    default:
                        $iDays = 0;
                        break;
                }
                if ($iDays == 0) {
                    //safety check
                    continue;
                }
                $iExpiredTime = PHPFOX_TIME - ($iDays * 24 * 3600);
                //Get user_id is expired
                $aListsExpired = Phpfox::getLib('database')->select('user_id, currency_id, status')
                    ->from(':subscribe_purchase')
                    ->where('package_id=' . (int)$aSubscriptionPackage['package_id'] . ' ANd (status="completed" OR status="cancel")')
                    ->group('user_id')
                    ->having('max(time_stamp) < ' . $iExpiredTime)
                    ->executeRows();
                foreach ($aListsExpired as $aListExpired) {
                    //If latest payment is not success
                    if ($aListExpired['status'] == 'cancel') {
                        continue;
                    }
                    $aCost = unserialize($aSubscriptionPackage['recurring_cost']);
                    $iPurchaseId = Phpfox::getService('subscribe.purchase.process')->add(array(
                        'package_id' => $aSubscriptionPackage['package_id'],
                        'currency_id' => $aListExpired['currency_id'],
                        'price' => $aCost[$aListExpired['currency_id']]
                    ), $aListExpired['user_id']
                    );
                    $bStatus = Phpfox::getService('user.process')->purchaseWithPoints('subscribe', $iPurchaseId,
                        $aCost[$aListExpired['currency_id']], $aListExpired['currency_id'], $aListExpired['user_id']);
                    //Does not enough point
                    if ($bStatus == false) {
                        PhpFox::getLib('mail')->to($aListExpired['user_id'])
                            ->subject(_p('your_subscription_is_canceled'))
                            ->message(_p('subscription_auto_cancel_message'))
                            ->send();
                        Phpfox::getService('user.process')->updateUserGroup($aListExpired['user_id'],
                            $aSubscriptionPackage['fail_user_group']);
                        Phpfox::getLib('database')->update(':subscribe_purchase', ['status' => 'cancel'],
                            'purchase_id=' . (int)$iPurchaseId);
                    }
                }
            }
        }
    }

    public function updatePurchase($iId, $sStatus)
    {
        Phpfox::isUser(true);
        Phpfox::getUserParam('admincp.has_admin_access', true);

        $aStatus = array(
            'completed',
            'cancel',
            'pending'
        );

        $aPurchase = $this->database()->select('sp.*, spack.*')
            ->from($this->_sTable, 'sp')
            ->join(Phpfox::getT('subscribe_package'), 'spack', 'spack.package_id = sp.package_id')
            ->where('sp.purchase_id = ' . (int)$iId)
            ->execute('getSlaveRow');

        if (!isset($aPurchase['purchase_id'])) {
            return Phpfox_Error::set(_p('unable_to_find_the_purchase_you_are_editing'));
        }

        if (empty($sStatus)) {
            // update purchase status
            $this->database()->update($this->_sTable, array('status' => '0'), 'purchase_id = ' . (int)$iId);
        } else {
            if (!in_array($sStatus, $aStatus)) {
                return Phpfox_Error::set(_p('not_a_valid_purchase_status'));
            }

            $this->update($aPurchase['purchase_id'], $aPurchase['package_id'], $sStatus, $aPurchase['user_id'],
                $aPurchase['user_group_id'], $aPurchase['fail_user_group']);
        }
        // update total active
        Phpfox::getService('subscribe.process')->updateTotalActive($aPurchase['package_id']);

        return true;
    }
	
	public function delete($iId)
	{
		Phpfox::isUser(true);
		Phpfox::getUserParam('admincp.has_admin_access', true);

		$aPurchase = $this->database()->select('sp.*, spack.*')
			->from($this->_sTable, 'sp')
			->join(Phpfox::getT('subscribe_package'), 'spack', 'spack.package_id = sp.package_id')
			->where('sp.purchase_id = ' . (int) $iId)
			->execute('getSlaveRow');
			
		if (!isset($aPurchase['purchase_id']))
		{
			return Phpfox_Error::set(_p('unable_to_find_the_purchase_you_are_trying_to_delete'));
		}			
		
		$this->database()->updateCounter('subscribe_package', 'total_active', 'package_id', $aPurchase['package_id'], true);
		$this->database()->delete($this->_sTable, 'purchase_id = ' . $aPurchase['purchase_id']);
		
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
        if ($sPlugin = Phpfox_Plugin::get('subscribe.service_purchase_process__call')) {
            eval($sPlugin);
            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}