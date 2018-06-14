<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Block_Moderation extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		if (PHPFOX_IS_AJAX) {
			return false;
		}

		$aParams = $this->getParam('global_moderation');
		$sView = $this->template()->getVar('sView' , '');

		$sPattern = empty($sView) ? '/js_item_m_/i' : '/js_' . $sView . '_item_m_/i';

		$iTotalInputFields = 0;
		$sInputFields = '';
		foreach ((array) $_COOKIE as $sCookieName => $sCookieValue)
		{
			if (preg_match($sPattern, $sCookieName) && strpos($sCookieValue, '_'))
			{
				$aParts = explode('_', $sCookieValue);
				if ($aParts[0] == $aParams['name'])
				{
					$sInputFields .= '<div class="js_item_m_' . $aParts[0] . '_' . $aParts[1] . '"><input class="js_global_item_moderate" type="hidden" name="item_moderate[]" value="' . $aParts[1] . '" /></div>';
					$iTotalInputFields++;
				}
			}
		}

		$this->template()->assign(array(
				'sInputFields' => $sInputFields,
				'iTotalInputFields' => $iTotalInputFields,
				'aModerationParams' => $aParams,		
				'sCustomModerationFields' => (isset($aParams['custom_fields']) ? $aParams['custom_fields'] : ''),
                'sView' => $sView
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
		(($sPlugin = Phpfox_Plugin::get('core.component_block_moderation_clean')) ? eval($sPlugin) : false);
	}
}