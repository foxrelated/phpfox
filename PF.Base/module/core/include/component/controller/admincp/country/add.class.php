<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Component_Controller_Admincp_Country_Add extends Phpfox_Component
{
	/**
	 * Controller
	 */
	public function process()
	{
		$bIsEdit = false;
		if (($sIso = $this->request()->get('id')) && ($aCountry = Phpfox::getService('core.country')->getForEdit($sIso)))
		{
			$bIsEdit = true;
			$this->template()->assign(array(
					'aForms' => $aCountry
				)
			);
		}
		
		if (($aVals = $this->request()->getArray('val')))
		{
			if ($bIsEdit)
			{
				if (Phpfox::getService('core.country.process')->update($aCountry['country_iso'], $aVals))
				{
					$this->url()->send('admincp.core.country', null, _p('country_successfully_updated'));
				}
			}
			else 
			{
				if (Phpfox::getService('core.country.process')->add($aVals))
				{
					$this->url()->send('admincp.core.country', null, _p('country_successfully_added'));
				}				
			}
		}
		
		$this->template()
            ->setTitle(($bIsEdit ? _p('editing_country') . ': ' : _p('add_a_country')))
			->setBreadCrumb(_p('country_manager'), $this->url()->makeUrl('admincp.core.country'))
			->setBreadCrumb(($bIsEdit ? _p('editing_country') . ': ' : _p('add_a_country')), $this->url()->current(), true)
			->assign(array(
					'bIsEdit' => $bIsEdit
				)
			)->setActiveMenu('admincp.globalize.country');
	}
	
	/**
	 * Garbage collector. Is executed after this class has completed
	 * its job and the template has also been displayed.
	 */
	public function clean()
	{
		(($sPlugin = Phpfox_Plugin::get('core.component_controller_admincp_country_add_clean')) ? eval($sPlugin) : false);
	}
}