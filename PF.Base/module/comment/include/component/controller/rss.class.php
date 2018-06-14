<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Comment_Component_Controller_Rss extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if (!Phpfox::isModule('rss')) {
            $this->url()->send('');
        }
        $sType = $this->request()->get('type');
        $iItemId = $this->request()->getInt('item');
        $aRss = Phpfox::getService('comment')->getForRss($sType, $iItemId);
        Phpfox::getService('rss')->output($aRss);

        return null;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('comment.component_controller_rss_clean')) ? eval($sPlugin) : false);
    }
}
