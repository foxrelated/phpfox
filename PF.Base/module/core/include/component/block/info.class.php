<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Info extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
        $aViewUser = $this->getParam('aUser');
        if ($aViewUser['user_id'] != Phpfox::getUserId()) {
            return false;
        }
		$aUser = Phpfox::getService('user')->get(Phpfox::getUserId(), true);
		$aInfo = array(
			_p('activity_points') => $aUser['activity_points'],
			_p('space_used') => (Phpfox::getUserParam('user.total_upload_space') === 0 ? _p('space_total_out_of_unlimited', array('space_total' => Phpfox_File::instance()->filesize($aUser['space_total']))) : _p('space_total_out_of_total', array('space_total' => Phpfox_File::instance()->filesize($aUser['space_total']), 'total' => Phpfox::getUserParam('user.total_upload_space')))),
		);

		$this->template()->assign(array(
				'aInfos' => $aInfo
			)
		);
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_block_info_clean')) ? eval($sPlugin) : false);
	}
}