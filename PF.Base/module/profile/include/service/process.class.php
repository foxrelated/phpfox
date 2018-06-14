<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * @deprecated
 * Class Profile_Service_Process
 */
class Profile_Service_Process extends Phpfox_Service
{
    /**
     * @deprecated
     */
    public function clearProfileCache()
    {
    }

    public function saveTempFileToLocalServer($profileImage)
    {
        $aProfileImage = explode('.', $profileImage);
        if (!is_dir(Phpfox::getParam('core.dir_file_temp'))) {
            Phpfox_File::instance()->mkdir(Phpfox::getParam('core.dir_file_temp'), true, 777);
        }

        $tempFile = Phpfox::getParam('core.dir_file_temp') . 'temp_profile_image_' . uniqid() . '.' . end($aProfileImage);
        Phpfox_File::instance()->write($tempFile, fox_get_contents($profileImage));
        Phpfox::getService('core.temp-file')->add([
            'type' => 'profile',
            'size' => filesize($tempFile),
            'path' => str_replace(Phpfox::getParam('core.dir_file_temp'), '', $tempFile),
            'server_id' => 1
        ]);

        return str_replace(Phpfox::getParam('core.dir_file_temp'), Phpfox::getParam('core.url_file_temp'), $tempFile);
    }

    /**
     * @param $sMethod
     * @param $aArguments
     * @return null
     */
    public function __call($sMethod, $aArguments)
    {
        /**
         * Check if such a plug-in exists and if it does call it.
         */
        if ($sPlugin = Phpfox_Plugin::get('profile.service_process__call')) {
            eval($sPlugin);

            return null;
        }

        /**
         * No method or plug-in found we must throw a error.
         */
        Phpfox_Error::trigger('Call to undefined method ' . __CLASS__ . '::' . $sMethod . '()', E_USER_ERROR);
    }
}
