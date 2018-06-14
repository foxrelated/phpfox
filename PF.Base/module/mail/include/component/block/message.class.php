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
 * @version 		$Id: folder.class.php 225 2009-02-13 13:24:59Z Raymond_Benc $
 */
class Mail_Component_Block_Message extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
		$iId = $this->request()->get('id');
		$aMessage = Phpfox::getService('mail')->getMail($iId);

		$this->template()->assign(array(
				'aMessage' => $aMessage
			)
		);

		return 'block';
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