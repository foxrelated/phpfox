<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Core_Service_Admincp_Process extends Phpfox_Service 
{
	/**
	 * Class constructor
	 */	
	public function __construct() {}
    
    /**
     * @param string $sNote
     */
	public function updateNote($sNote)
	{
		Phpfox::isAdmin(true);
		
		$this->database()->update(Phpfox::getT('setting'), array('value_actual' => $this->preParse()->clean($sNote)), 'module_id = \'core\' AND var_name = \'global_admincp_note\'');	
		
		$this->cache()->remove('admincp_note');
	}
    
    /**
     * If a call is made to an unknown method attempt to connect
     * it to a specific plug-in with the same name thus allowing
     * plug-in developers the ability to extend classes.
     *
     * @param string $sMethod    is the name of the method
     * @param array  $aArguments is the array of arguments of being passed
     *
     * @return null
     */
	public function __call($sMethod, $aArguments)
	{
		/**
		 * Check if such a plug-in exists and if it does call it.
		 */
        if ($sPlugin = Phpfox_Plugin::get('core.service_admincp_process__call')) {
            eval($sPlugin);
            return null;
        }
			
		/**
		 * No method or plug-in found we must throw a error.
		 */
		Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
	}	
}