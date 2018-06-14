<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Admincp_Ip extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $this->template()->setTitle(_p('ip_address'))
            ->setBreadCrumb(_p('ip_address'), $this->url()->makeUrl('admincp.core.ip'));

        $aResults = array();
        if (($sSearch = $this->request()->get('search')) && !empty($sSearch)) {
            if (($aResults = Phpfox::getService('core')->ipSearch($sSearch)) !== false) {
                $this->template()->setBreadCrumb(_p('search') . ': ' . str_replace('-', '.', $sSearch), null, true);
            }
        }

        $this->template()->assign(array(
                'aResults' => $aResults
            )
        )->setActiveMenu('admincp.member.search_ip_address');
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_controller_admincp_ip_clean')) ? eval($sPlugin) : false);
    }
}
