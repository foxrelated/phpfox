<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * @author Neil <neil@phpfox.com>
 * Class Mail_Component_Controller_Admincp_Index
 */
class Mail_Component_Controller_Admincp_Index extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $this->url()->send('admincp.setting.edit', ['module-id' => 'mail']);
    }
}