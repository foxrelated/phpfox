<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Activity extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		$aUser = Phpfox::getService('user')->get(Phpfox::getUserId(), true);
		
		$aModules = Phpfox::massCallback('getDashboardActivity');
		
		$aActivites = array(
			_p('total_items') => $aUser['activity_total'],
			_p('activity_points') => $aUser['activity_points'] . (Phpfox::getParam('user.can_purchase_activity_points') ? '<span id="purchase_points_link">(<a href="#" onclick="$Core.box(\'user.purchasePoints\', 500); return false;">' . _p('purchase_points') . '</a>)</span>' : ''),
		);
		foreach ($aModules as $aModule)
		{
			foreach ($aModule as $sPhrase => $sLink)
			{
				$aActivites[$sPhrase] = $sLink;				
			}			
		}
		
		$this->template()->assign(array(
				'aActivites' => $aActivites
			)
		);
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_block_activity_clean')) ? eval($sPlugin) : false);
	}
}