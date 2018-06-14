<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Ad_Component_Block_Display
 */
class Ad_Component_Block_Display extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        (($sPlugin = Phpfox_Plugin::get('ad.component_block_display_process__start')) ? eval($sPlugin) : false);

        if (!Phpfox::getParam('ad.enable_ads')) {
            return false;
        }

        $this->setParam('block_id', 50);

        if (Phpfox::getParam('ad.multi_ad') != true && $this->getParam('adId') !== null && is_numeric($this->getParam('adId')) && $this->getParam('adId') > 0) {
            $aAds = Phpfox::getService('ad')->get(array('ad_id = ' . (int)$this->getParam('adId')));
        } else {
            $aAds = Phpfox::getService('ad')->getForBlock($this->getParam('block_id'), true);
        }

        if (!is_array($aAds)) {
            $aAds = array();
        }

        if ($this->getParam('bIsIframe') === true && is_array($aAds) && count($aAds) > 1) {
            foreach ($aAds as $iKey => $aAd) {
                if (!is_array($aAd)) {
                    unset($aAds[$iKey]);
                }
            }

            $aAds = $aAds[array_rand($aAds)];
        }

        if (!is_array($aAds)) {
            $aAds = array();
        }

        foreach ($aAds as $iKey => $aAd) {
            if (!is_array($aAd)) {
                $aAds = array($aAds);
                break;
            }
        }

        $bBlockIdForAds = false;

        foreach ($aAds as $iKey => $aAd) {
            if (!empty($aAd['disallow_controller'])) {
                $sControllerName = Phpfox_Module::instance()->getFullControllerName();
                $aParts = explode(',', $aAd['disallow_controller']);
                foreach ($aParts as $sPart) {
                    $sPart = trim($sPart);
                    // str_replace for marketplace.invoice/index
                    // str_replace for music.browse/album
                    if ($sControllerName == $sPart || (str_replace('/index', '',
                                $sControllerName) == $sPart) || (str_replace('/', '.', $sControllerName) == $sPart)) {
                        unset($aAds[$iKey]);
                    }
                }
            }
            if (!empty($aAd[$iKey]['html_code'])) {
                $aAds[$iKey]['html_code'] = str_replace('target="_blank"', 'target="_blank" class="no_ajax_link"',
                    $aAd['html_code']);
            }
            if (PHPFOX_IS_AJAX && $this->getParam('block_id') == 'photo_theater'
                && ($aGetRequest = $this->request()->get('core'))
                && isset($aGetRequest['call'])
                && $aGetRequest['call'] == 'photo.view'
            ) {
                $bBlockIdForAds = true;
            }

            if ($aAd['is_active'] != 1) {
                unset($aAds[$iKey]);
            }
        }

        if (!count($aAds)) {
            return false;
        }

        $iBlockId = Phpfox::getParam('ad.multi_ad') ? 50 : $this->getParam('block_id');

        $this->template()->assign(array(
                'aBlockAds' => $aAds,
                'bBlockIdForAds' => $bBlockIdForAds,
                'iBlockId' => $iBlockId,
                'sCustomClassName' => 'no-toggle'
            )
        );

        if (Phpfox::getParam('ad.multi_ad')) {
            if (!$this->getParam('bNoTitle')) {
                $this->template()->assign('sHeader', _p('sponsored'));
                if (Phpfox::isUser() && Phpfox::getUserParam('ad.can_create_ad_campaigns')) {
                    $this->template()->assign([
                        'aFooter' => [
                            _p('create_an_ad') => $this->url()->makeUrl('ad.add')
                        ]
                    ]);
                }
            }

            return 'block';
        }

        (($sPlugin = Phpfox_Plugin::get('ad.component_block_display_process__end')) ? eval($sPlugin) : false);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('ad.component_block_display_clean')) ? eval($sPlugin) : false);
    }
}
