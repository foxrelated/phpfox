<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Admincp_Country_Child_Add extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		$bIsEdit = false;
		$mCountry = '';
		if (($sIso = $this->request()->get('iso')))
		{
			$mCountry = Phpfox::getService('core.country')->getCountry($sIso);
			
			if ($mCountry === false)
			{
				return Phpfox_Error::display(_p('not_a_valid_country'));
			}
		}
		elseif (($iChild = $this->request()->getInt('id')))
		{
			if (($aChild = Phpfox::getService('core.country')->getChildEdit($iChild)))
			{
				$bIsEdit = true;
				$this->template()->assign(array(
						'aForms' => $aChild
					)
				);
			}
		}
		
		if (($aVals = $this->request()->getArray('val')))
		{
			if ($bIsEdit)
			{
				if (Phpfox::getService('core.country.child.process')->update($aChild['child_id'], $aVals))
				{					
					$this->url()->send('admincp.core.country.child', array('id' => $aChild['country_iso']), _p('state_province_successfully_updated'));
				}				
			}
			else 
			{
				if (Phpfox::getService('core.country.child.process')->add($aVals))
				{					
					$this->url()->send('admincp.core.country.child', array('id' => $aVals['country_iso']), _p('state_province_successfully_added'));
				}
			}
		}
		
		$this->template()->setTitle(_p('country_manager'))
			->setBreadCrumb(_p('country_manager'), $this->url()->makeUrl('admincp.core.country'))
			->setBreadCrumb(($bIsEdit ? _p('editing_state_province') . ': ' : _p('adding_state_province') . ': ' . $mCountry), null, true)
			->assign(array(
					'bIsEdit' => $bIsEdit,
					'sIso' => $sIso
				)
			)->setActiveMenu('admincp.globalize.country');
        return null;
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_controller_admincp_country_child_add_clean')) ? eval($sPlugin) : false);
	}
}