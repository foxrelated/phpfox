<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Index
 */
class User_Component_Controller_Admincp_Index extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		Phpfox_Url::instance()->send('admincp.user.browse');
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('user.component_controller_admincp_index_clean')) ? eval($sPlugin) : false);
	}
}
