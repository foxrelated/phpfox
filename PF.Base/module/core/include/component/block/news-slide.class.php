<?php

defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Core_Component_Block_News_Slide
 */
class Core_Component_Block_News_Slide extends Phpfox_Component
{
    public function process()
    {
        $aNews = Phpfox::getService('core.admincp')->getNews(true);

        if ($aNews === false) {
            return false;
        }

        if (!Phpfox::getUserParam('core.can_view_news_updates')) {
            return false;
        }

        if ((is_array($aNews) && !count($aNews)) || is_bool($aNews)) {
            return false;
        }

        $this->template()->assign(array(
                'aPhpfoxNewsSlide' => $aNews
            )
        );

        return 'block';
    }

    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('core.component_block_news_slide_clean')) ? eval($sPlugin) : false);
    }
}