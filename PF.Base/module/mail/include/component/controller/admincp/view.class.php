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
 * @package 		Phpfox_Component
 * @version 		$Id: view.class.php 4857 2012-10-09 06:32:38Z Raymond_Benc $
 */
class Mail_Component_Controller_Admincp_View extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		$aMessage = Phpfox::getService('mail')->getMail($this->request()->getInt('id'));
		
		if (!count($aMessage))
		{
			return Phpfox_Error::display(_p('message_not_found'));
		}
		
        $this->template()->setHeader(array('mail.css' => 'style_css'));

        $this->template()->setTitle(_p('viewing_private_message'))
			->setBreadCrumb(_p('private_messages'), $this->url()->makeUrl('admincp.mail.private'))
			->setBreadCrumb(_p('viewing_private_message'), null, true)
			->assign(array(
					'aMessage' => $aMessage
			)
		);
        return null;
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('mail.component_controller_admincp_view_clean')) ? eval($sPlugin) : false);
	}
}