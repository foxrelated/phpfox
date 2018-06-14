<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * @author Neil J. <neil@phpfox.com>
 *
 * Class Share_Component_Controller_Admincp_Index
 */
class Share_Component_Controller_Admincp_Index extends Phpfox_Component
{
    public function process()
    {
        Phpfox::getLib('url')->send('admincp.setting.edit', ['module-id' => 'share']);
    }
}
