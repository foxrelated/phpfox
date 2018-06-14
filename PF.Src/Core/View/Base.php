<?php

namespace Core\View;

abstract class Base extends \Twig_Template
{
    public function displayBlock($name, array $context, array $blocks = [], $useBlocks = true)
    {

        if ($name == 'left') {
            $this->_loadBlocks(1);
        } else {
            if ($name == 'right') {
                $this->_loadBlocks(3);
                echo '<div id="end_right"></div>';
            } else {
                if ($name == 'content') {
                    $this->_loadBlocks(2);
                } else {
                    if ($name == 'top') {
                        $this->_loadBlocks(11);
                    }
                }
            }
        }

        $baseBlocks = ['h1', 'breadcrumb', 'top', 'content'];
        if (in_array($name, $baseBlocks)) {
            echo '<div class="_block_' . $name . '">';
        }

        parent::displayBlock($name, $context, $blocks, $useBlocks);

        if (in_array($name, $baseBlocks)) {
            echo '</div>';
        }

        if ($name == 'content') {
            $this->_loadBlocks(4);
        } else {
            if ($name == 'top') {
                $this->_loadBlocks(7);
            }
        }
    }

    private function _loadBlocks($location)
    {
        echo '<div class="_block" data-location="' . $location . '">';
        $blocks = \Phpfox_Module::instance()->getModuleBlocks($location);
        foreach ($blocks as $block) {
            \Phpfox::getBlock($block);
        }
        echo '</div>';
    }
}