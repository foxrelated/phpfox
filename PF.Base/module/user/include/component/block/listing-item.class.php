<?php
defined('PHPFOX') or exit('NO DICE!');

class User_Component_Block_Listing_Item extends Phpfox_Component
{
    public function process()
    {
        $iUserId = $this->getParam('user_id');

        if (empty($iUserId)) {
            return false;
        }

        $aUser = Phpfox::getService('user')->getUser($iUserId);
        $bIsFriend = Phpfox::getService('friend')->isFriend(Phpfox::getUserId(), $iUserId);
        $this->template()->assign(compact('aUser', 'bIsFriend'));

        ($sPlugin = Phpfox_Plugin::get('user.component_block_listing_item_end')) && eval($sPlugin);

        return 'block';
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_block_listing_item_clean')) ? eval($sPlugin) : false);
    }
}
