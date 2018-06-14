<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Custom_Component_Block_Display
 */
class Custom_Component_Block_Display extends Phpfox_Component
{
    private $_sTemplate = null;

    /**
     * Controller
     */
    public function process()
    {
        static $iUserGroupId = 0;
        static $bIsCustom = false;
        static $aCustomMain = array();

        $iUserId = defined('PHPFOX_CURRENT_TIMELINE_PROFILE') ? PHPFOX_CURRENT_TIMELINE_PROFILE : $this->getParam('item_id');
        if (!Phpfox::getService('user.privacy')->hasAccess($iUserId, 'profile.profile_info')) {
            return false;
        }

        if ($iUserGroupId === 0) {
            $aUser = (PHPFOX_IS_AJAX ? array('user_group_id' => $this->getParam('user_group_id')) : $this->getParam('aUser'));

            $bIsCustom = Phpfox::getService('user.group.setting')->getGroupParam($aUser['user_group_id'],
                'custom.has_special_custom_fields');
            $iUserGroupId = $aUser['user_group_id'];
        }

        if (!isset($aCustomMain[$this->getParam('type_id')])) {
            $aCustomMain[$this->getParam('type_id')] = Phpfox::getService('custom')->getForDisplay($this->getParam('type_id'),
                $iUserId, ($bIsCustom ? $iUserGroupId : null));
        }

        if (($sCustomFieldName = $this->getParam('custom_field_id'))) {
            if (!isset($aCustomMain[$this->getParam('type_id')]['cf_' . $sCustomFieldName])) {
                return false;
            }

            $aOutput = array($aCustomMain[$this->getParam('type_id')]['cf_' . $sCustomFieldName]);
        } else {
            $aOutput = $aCustomMain[$this->getParam('type_id')];
        }
        if (empty($aOutput)) {
            return false;
        }
        $this->_sTemplate = $this->getParam('template');

        $this->template()->assign(array(
                'aCustomMain' => $aOutput,
                'sTemplate' => $this->getParam('template')
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
        $this->template()->clean(array(
                'aCustomMain'
            )
        );

        (($sPlugin = Phpfox_Plugin::get('custom.component_block_display_clean')) ? eval($sPlugin) : false);
    }
}
