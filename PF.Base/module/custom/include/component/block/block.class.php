<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Custom_Component_Block_Block
 */
class Custom_Component_Block_Block extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $aData = $this->getParam('data');
        $sTemplate = $this->getParam('template');

        if (!is_array($aData) || !$aData['is_active']) {
            return false;
        }

        //Check is parent group active
        if (isset($aData['group_id'])) {
            $aCustomGroup = Phpfox::getService('custom.group')->getGroup($aData['group_id']);
            if (!$aCustomGroup['is_active']) {
                return false;
            }
        }

        if (!defined('PHPFOX_IN_DESIGN_MODE') && Phpfox::getParam('custom.hide_custom_fields_when_empty') && empty($aData['value'])) {
            return false;
        }

        $sEditLink = '';
        $sJsClick = ' $(\'#js_custom_content_' . $aData['field_id'] . '\').hide();';
        $sJsClick .= ' $(this).parent().removeClass(\'extra_info\');';
        $sJsClick .= ' $.ajaxCall(\'custom.edit\', \'field_id=' . $aData['field_id'] . '&amp;item_id=' . $this->getParam('item_id') . '&amp;edit_user_id=' . $this->getParam('edit_user_id') . '\');';
        $sJsClick .= ' return false;';

        if (($this->getParam('edit_user_id') && empty($aData['value']) && $this->getParam('edit_user_id') != Phpfox::getUserId())) {
            return false;
        }

        if ($this->getParam('edit_user_id') && $this->getParam('edit_user_id') == Phpfox::getUserId() && empty($aData['value'])) {
            $aData['value'] = '<div class="js_custom_content_holder">';
            $aData['value'] .= '<div id="js_custom_content_' . $aData['field_id'] . '" class="extra_info js_custom_content">' .
                _p('nothing_added_yet_click_to_edit', ['link' => $sJsClick]) . '</div>';
            $aData['value'] .= '<div id="js_custom_field_' . $aData['field_id'] . '" class="js_custon_field" style="display:none;"></div>';
            $aData['value'] .= '</div>';
        } else {
            $oParseOutput = Phpfox::getLib('parse.output');

            switch ($aData['var_type']) {
                case 'select':
                case 'radio':
                    $sValue = _p($aData['value']);
                    $aData['value'] = '<div class="js_custom_content_holder">';
                    $aData['value'] .= '<div id="js_custom_content_' . $aData['field_id'] . '" class="js_custom_content">' . $sValue . '</div>';
                    $aData['value'] .= '<div id="js_custom_field_' . $aData['field_id'] . '" class="js_custon_field" style="display:none;"></div>';
                    $aData['value'] .= '</div>';
                    break;
                case 'multiselect':
                case 'checkbox':
                    $aValues = is_array($aData['value']) ? $aData['value'] : unserialize($aData['value']);
                    $aPhrases = array();
                    foreach ($aValues as $sPhrase) {
                        $aPhrases[] = _p($sPhrase);
                    }
                    $aData['value'] = '<div class="js_custom_content_holder">';
                    $aData['value'] .= '<div id="js_custom_content_' . $aData['field_id'] . '" class="js_custom_content">' . implode(', ',
                            $aPhrases) . '</div>';
                    $aData['value'] .= '<div id="js_custom_field_' . $aData['field_id'] . '" class="js_custon_field" style="display:none;"></div>';
                    $aData['value'] .= '</div>';

                    //$sJsClick = 'window.location=\'' . Phpfox_Url::instance()->makeUrl('user.profile') .'\'';
                    break;
                default:
                    if ($aData['type_id'] == 'profile_panel') {
                        Phpfox::getLib('parse.output')->setImageParser(array(
                                'width' => 300,
                                'height' => 260
                            )
                        );
                    }
                    $sValue = $oParseOutput->parse($aData['value']);

                    $aData['value'] = '<div class="js_custom_content_holder">';
                    $aData['value'] .= '<div id="js_custom_content_' . $aData['field_id'] . '" class="js_custom_content">';
                    $aData['value'] .= $oParseOutput->shorten($sValue, 100, _p('view_more'), true);
                    $aData['value'] .= '</div>';
                    $aData['value'] .= '<div id="js_custom_field_' . $aData['field_id'] . '" class="js_custon_field" style="display:none;"></div>';
                    $aData['value'] .= '</div>';
                    break;
            }
        }

        if ($this->getParam('edit_user_id') && ($this->getParam('edit_user_id') == Phpfox::getUserId() && Phpfox::getUserParam('custom.can_edit_own_custom_field')) || (Phpfox::getUserParam('custom.can_edit_other_custom_fields'))) {
            $sEditLink = '<div class="js_edit_header_bar">';
            $sEditLink .= '<span id="js_custom_loader_' . $aData['field_id'] . '" style="display:none;"><img src="' . $this->template()->getStyle('image',
                    'ajax/small.gif') . '" alt="" class="v_middle" /></span>';
            $sEditLink .= '<a href="#" onclick="' . $sJsClick . '" id="js_custom_link_' . $aData['field_id'] . '">';
            $sEditLink .= '<span><i class="fa fa-edit"></i></span>';
            $sEditLink .= '</a>';
            $sEditLink .= '</div>';
        }

        $this->template()->assign(array(
                'sHeader' => $sEditLink . _p($aData['phrase_var_name']),
                'sBlockBorderJsId' => str_replace('.', '_', $aData['phrase_var_name']),
                'sContent' => $aData['value'],
                'sTemplate' => $sTemplate,
                'sCustomValue' => $aData['value']
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
        (($sPlugin = Phpfox_Plugin::get('custom.component_block_block_clean')) ? eval($sPlugin) : false);
    }
}
