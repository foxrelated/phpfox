<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Admincp_Component_Controller_Setting_Edit
 */
class Admincp_Component_Controller_Setting_Edit extends Phpfox_Component
{
    private function scanValidation($sModuleId, $sGroupId, &$aSettings)
    {
        $aScanPluginNames = [];
        $aSortedValidation = [];
        $aValidation = [];

        if ($sModuleId) {
            $aScanPluginNames[$sModuleId] = 'validator.admincp_settings_' . $sModuleId;
        }

        if ($sGroupId) {
            array_map(function ($row) use (&$aScanPluginNames) {
                $aScanPluginNames[$row['module_id']] = 'validator.admincp_settings_' . $row['module_id'];
            }, Phpfox::getLib('database')
                ->select('distinct(module_id)')
                ->from(':setting')
                ->where("group_id='{$sGroupId}'")
                ->execute('getSlaveRows'));
        }
        foreach ($aScanPluginNames as $sScanModuleId => $sScanPluginName) {
            $aValidation = [];
            (($sPlugin = Phpfox_Plugin::get($sScanPluginName)) ? eval($sPlugin) : false);
            $aSortedValidation [$sScanModuleId] = $aValidation;
        }

        // reset validation array
        $aValidation = [];
        $aExists = [];

        foreach ($aSettings as $aSetting) {
            $tempModuleId = $aSetting['module_id'];
            $tempVarName = $aSetting['var_name'];
            $aExists[$tempVarName] = 1;
            if (isset($aSortedValidation[$tempModuleId]) and isset($aSortedValidation[$tempModuleId][$tempVarName])) {
                $aValidation[$tempVarName] = $aSortedValidation[$tempModuleId][$tempVarName];
            }
        }

        $sPluginName = 'validator.admincp_settings' . ($sModuleId ? '_' . $sModuleId : '') . ($sGroupId ? '_group_' . $sGroupId : '');

        (($sPlugin = Phpfox_Plugin::get($sPluginName)) ? eval($sPlugin) : false);

        $aValidation = array_intersect_key($aValidation, $aExists);

        return $aValidation;
    }

    /**
     * Controller
     */
    public function process()
    {
        list($aGroups, $aModules, $aProductGroups) = Phpfox::getService('admincp.setting.group')->get();

        $aCond = [];
        $sSettingTitle = '';
        $bTestEmail = false;
        $aInvalid = [];
        $sModuleId = $this->request()->get('module-id');
        $iGroupId = $this->request()->get('group-id');
        $oDb = Phpfox::getLib('database');

        if ($this->request()->get('setting-id')) {
            $this->url()->send('admincp');
        }

        $sRealAppId = null;
        if (Phpfox::isAppAlias($sModuleId)) {
            $sRealAppId = Phpfox::getAppId($sModuleId);
            $App = \Core\Lib::appInit($sRealAppId);
            Phpfox::getService('admincp.setting.process')->importFromApp($App);
        } elseif (Phpfox::isApps($sModuleId)) {
            $sRealAppId = $sModuleId;
            $App = \Core\Lib::appInit($sModuleId);
            Phpfox::getService('admincp.setting.process')->importFromApp($App);
        }

        if (!$sModuleId and !$iGroupId) {
            $this->url()->send('admincp');
        }

        if (($sSettingId = $this->request()->get('setting-id'))) {
            $aCond[] = " AND setting.setting_id = " . (int)$sSettingId;
        }

        if (($sGroupId = $this->request()->get('group-id'))) {
            $aCond[] = " AND setting.group_id = '" . $oDb->escape($sGroupId) . "' AND setting.is_hidden = 0 ";
            foreach ($aGroups as $aGroup) {
                if ($aGroup['group_id'] == $sGroupId) {
                    $sSettingTitle = $aGroup['var_name'];
                    break;
                }
            }
        }

        if (($iModuleId = $this->request()->get('module-id'))) {
            $aCond[] = " AND setting.module_id = '" . $oDb->escape($iModuleId) . "' AND setting.is_hidden = 0 ";
            foreach ($aModules as $aModule) {
                if ($aModule['module_id'] == $iModuleId) {
                    $sSettingTitle = $aModule['module_id'];
                    break;
                }
            }
        }

        if (($sProductId = $this->request()->get('product-id'))) {
            $aCond[] = " AND setting.product_id = '" . $oDb->escape($sProductId) . "' AND setting.is_hidden = 0 ";
            foreach ($aProductGroups as $aProduct) {
                if ($aProduct['product_id'] == $sProductId) {
                    $sSettingTitle = $aProduct['var_name'];
                    break;
                }
            }
        }

        $isValid = true;
        $oValidator = Phpfox::getLib('validator');
        $aSettings = Phpfox::getService('admincp.setting')->get($aCond);
        $aValidation = $this->scanValidation($sModuleId, $sGroupId, $aSettings);

        if ($aValidation) {
            $oValidator = $oValidator->set(['sFormName' => 'js_form', 'aParams' => $aValidation]);
        }

        if ($sGroupId == 'mail' && $this->request()->get('test')) {
            $bTestEmail = true;
            $aVals = $this->request()->getArray('val');
            if (isset($aVals['email_send_test']) && $aValidation && !empty($aVals['value']) && $oValidator->isValid($aVals['value'])) {
                if (filter_var($aVals['email_send_test'], FILTER_VALIDATE_EMAIL)) {
                    define('PHPFOX_MAIL_DEBUG', true);
                    //Save coolie test email
                    Phpfox::setCookie('email_send_test', $aVals['email_send_test']);
                    $oMail = Phpfox::getLib('mail')
                        ->test($aVals['value'])
                        ->to($aVals['email_send_test'])
                        ->fromEmail(Phpfox::getParam('core.email_from_email'))
                        ->fromName(Phpfox::getParam('core.mail_from_name'))
                        ->subject(_p("Test setup email"))
                        ->message(_p("Congratulations, your configuration worked"));

                    if ($oMail->send(false, true)) {
                        $aVals = $this->request()->getArray('val');
                        Phpfox::removeCookie('email_send_data');
                        Phpfox::getService('admincp.setting.process')->update($aVals);
                        Phpfox::addMessage(_p("Email sent."));
                    } else {
                        //Save cookie all params in case fail
                        Phpfox::setCookie('email_send_data', json_encode($aVals['value']), 1600);
                        Phpfox::addMessage(_p("Email can't send."));
                    }

                    $this->url()->send('admincp.setting.edit', ['group-id' => 'mail']);

                } else {
                    Phpfox_Error::set(_p("Not a valid test email address"));
                }
            }
        }

        if (!$bTestEmail && $aVals = $this->request()->getArray('val')) {

            if ($aValidation && !empty($aVals['value']) && !$oValidator->isValid($aVals['value'])) {
                $aInvalid = $oValidator->getInvalidate();
                $isValid = false;

            } elseif (Phpfox::getService('admincp.setting.process')->update($aVals)) {
                Phpfox::removeCookie('email_send_data');
                Phpfox::addMessage(_p('Your changes have been saved!'));
            }
        }

        $aSettings = Phpfox::getService('admincp.setting')->get($aCond);

        if ($sRealAppId) {
            $oApp = Core\Lib::app()->get($sRealAppId);
            $sSettingTitle = ($oApp && $oApp->name) ? $oApp->name : Phpfox_Locale::instance()->translate($sSettingTitle,
                'module');
        }
        if (empty($sSettingTitle) && Phpfox::isModule($sSettingTitle)) {
            $oApp = Core\Lib::app()->get('__module_' . $sSettingTitle);
            $sSettingTitle = ($oApp && $oApp->name) ? $oApp->name : Phpfox_Locale::instance()->translate($sSettingTitle,
                'module');
        }
        if (empty($sSettingTitle) && Phpfox::isApps($iModuleId)) {
            $oApp = Core\Lib::app()->get($iModuleId);
            $sSettingTitle = ($oApp && $oApp->name) ? $oApp->name : Phpfox_Locale::instance()->translate($sSettingTitle,
                'module');
        }
        $group_class = $this->request()->get('group');
        if ($group_class) {
            foreach ($aSettings as $iKey => $aSetting) {
                $aGroupOptions = [
                    'pf_core_cache_driver' => [
                        'group_class' => 'core_cache_driver',
                        'option_class' => ''
                    ],
                    'pf_core_cache_redis_host' => [
                        'group_class' => 'core_cache_driver',
                        'option_class' => 'pf_core_cache_driver=redis'
                    ],
                    'pf_core_cache_redis_port' => [
                        'group_class' => 'core_cache_driver',
                        'option_class' => 'pf_core_cache_driver=redis'
                    ],
                    'pf_core_cache_memcached_host' => [
                        'group_class' => 'core_cache_driver',
                        'option_class' => 'pf_core_cache_driver=memcached'
                    ],
                    'pf_core_cache_memcached_port' => [
                        'group_class' => 'core_cache_driver',
                        'option_class' => 'pf_core_cache_driver=memcached'
                    ],
                    'pf_core_bundle_js_css' => [
                        'group_class' => 'core_redis',
                        'option_class' => ''
                    ]
                ];
                if (array_key_exists($aSetting['var_name'], $aGroupOptions)) {
                    $aSettings[$iKey]['group_class'] = $aGroupOptions[$aSetting['var_name']]['group_class'];
                    $aSettings[$iKey]['option_class'] = $aGroupOptions[$aSetting['var_name']]['option_class'];
                }
                //plugin for 3rd would like to use this feature
                (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_setting_group_class')) ? eval($sPlugin) : false);
            }
        }

        if (!$bTestEmail && isset($App) && $aVals && $isValid) {
            try {
                $settings = $aVals['value'];
                Core\Event::trigger('app_settings', $settings);
            } catch (\Exception $e) {
                return [
                    'error' => $e->getMessage()
                ];
            }

            Phpfox::addMessage(_p('Your changes have been saved!'));
        }
        $aDangerSettings = Phpfox::getService('admincp.setting')->getDangerSettings();

        $aCookieValue = json_decode(Phpfox::getCookie('email_send_data'), true);
        foreach ($aSettings as $index => $aSetting) {
            if (isset($aInvalid[$aSetting['var_name']])) {
                $aSettings[$index]['error'] = $aInvalid[$aSetting['var_name']];
            }
            if (in_array($aSetting['module_id'] . '.' . $aSetting['var_name'], $aDangerSettings)) {
                $aSettings[$index]['is_danger'] = true;
            }

            if (isset($aCookieValue[$aSetting['var_name']])) {
                if ($aSetting['var_name'] == 'mail_smtp_secure') {
                    $aSettings[$index]['value_actual'] = $aCookieValue[$aSetting['var_name']]['real'];
                } else {
                    $aSettings[$index]['value_actual'] = $aCookieValue[$aSetting['var_name']];
                }
            }
        }

        if ($sGroupId) {
            $this->template()
                ->setActiveMenu('admincp.settings.' . $sGroupId);
        } elseif ($group_class) {
            $this->template()
                ->setActiveMenu('admincp.settings.' . $group_class);
        } elseif (!empty($sModuleId) && $sModuleId == 'user') {
            $this->template()
                ->setActiveMenu('admincp.member.settings');
        }
        $test_email = Phpfox::getCookie('email_send_test');
        $this->template()->setSectionTitle($sSettingTitle)
            ->setBreadCrumb(_p('settings'), '#')
            ->setTitle(_p('manage_settings'))
            ->assign([
                'aGroups' => $aGroups,
                'aModules' => $aModules,
                'aProductGroups' => $aProductGroups,
                'aSettings' => $aSettings,
                'sSettingTitle' => $sSettingTitle,
                'sGroupId' => $sGroupId,
                'group_class' => $group_class,
                'test_email' => $test_email,
                'admincp_help' => isset($App) && !empty($App->admincp_help) ? $App->admincp_help : null
            ]);

        if ($sGroupId) {
            $n = _p('setting_group_label_' . $sGroupId);
            $this->template()->clearBreadCrumb()->setBreadCrumb($n);
        } elseif ($group_class) {
            $n = _p('setting_group_label_' . $group_class);
            $this->template()->clearBreadCrumb()->setBreadCrumb($n);
        } elseif ($sModuleId) {
            $sAppName = (!empty($App) && !empty($App->name)) ? $App->name : Phpfox::getLib('locale')->translate($sModuleId,
                'module');
            $sAppId = (!empty($App) && !empty($App->id)) ? $App->id : '__module_' . $sModuleId;
            $this->template()
                ->clearBreadCrumb()
                ->setBreadCrumb(_p('Apps'), $this->url()->makeUrl('admincp.apps'))
                ->setBreadCrumb($sAppName, $this->url()->makeUrl('admincp.app', ['id' => $sAppId]))
                ->setBreadCrumb(_p('Settings'));
        }
        $this->template()->setHeader([
            'seo.js' => 'module_admincp'
        ]);


        (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_setting_edit_process')) ? eval($sPlugin) : false);

        return null;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('admincp.component_controller_setting_edit_clean')) ? eval($sPlugin) : false);
    }
}