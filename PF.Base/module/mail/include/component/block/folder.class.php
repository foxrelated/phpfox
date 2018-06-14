<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Mail_Component_Block_Folder
 */
class Mail_Component_Block_Folder extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
        return false;
	}

	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('mail.component_block_folder_clean')) ? eval($sPlugin) : false);
	}
}