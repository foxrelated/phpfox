<?php

namespace Core;

defined('PHPFOX') or exit('NO DICE!');

class Objectify
{
    private $__toString = '';

    public function __construct($objects = null)
    {
        if ($objects instanceof \Closure) {
            $this->__toString = $objects;
            return;
        }
        if ($objects) {
            if (property_exists($this, 'user')) {
                if (is_object($objects) && isset($objects->user) && is_object($objects->user)) {
                    $this->user = $objects->user;
                } else {
                    $this->user = (object)$this->_build($objects);
                }
            } else {
                if ((is_array($objects) && isset($objects['user_id']))) {
                    if (isset($objects['full_name'])) {
                        $objects = $this->_build($objects);
                    } else {
                        if ($objects['user_id'] == 0) {
                            $objects = $this->_buildGuest($objects);
                        }
                    }
                }
            }

            foreach ($objects as $key => $value) {
                if ($key == 'user') {
                    continue;
                }

                if (!property_exists($this, $key)) {
                    continue;
                }

                $this->$key = $value;
            }
        }
    }

    protected function _build($row)
    {
        $row = (array)$row;
        if (isset($row['user']) && is_array($row['user'])) {
            $row = $row['user'];
        }

        $age = (isset($row['birthday']) ? $row['birthday'] : null);
        $day = '';
        $month = '';
        $year = '';
        if (!empty($age)) {
            $month = substr($age, 0, 2);
            $day = substr($age, 2, 2);
            $year = substr($age, -4);
        }

        $image_50px = '';
        $imageLink = '';
        if (!empty($row['user_image'])) {
            $image_50px = \Phpfox_Image_Helper::instance()->display([
                'user'       => $row,
                'suffix'     => '_50_square',
                'return_url' => true,
            ]);
        }

        $imageLink = \Phpfox_Image_Helper::instance()->display([
            'user'   => $row,
            'suffix' => '_120_square',
        ]);
        $link = \Phpfox_Url::instance()->makeUrl($row['user_name']);
        if (empty($row['user_name']) && $row['profile_page_id']) {
            //replace user link  by pages link
            $sType = \Phpfox::getLib('pages.facade')->getItemType();
            if ($sType == 'pages' && \Phpfox::isModule('pages')) {
                $link = (new \Apps\Core_Pages\Service\Pages())->getUrl($row['profile_page_id']);
            }
        }

        return [
            'id'         => (int)$row['user_id'],
            'email'      => (isset($row['email']) ? $row['email'] : null),
            'group'      => (object)[
                'id' => (int)$row['user_group_id'],
            ],
            'name'       => $row['full_name'],
            'name_link'  => '<span id="js_user_name_link_' . $row['user_name'] . '" class="user_profile_link_span"><a href="' . $link . '">' . $row['full_name'] . '</a></span>',
            'url'        => $link,
            'gender'     => [
                'id'   => $row['gender'],
                'name' => \Phpfox::getService('user')->gender($row['gender']),
            ],
            'photo_link' => $imageLink,
            'photo'      => [
                '50px'     => $image_50px,
                '120px'    => str_replace('_50_square', '_120_square', $image_50px),
                '200px'    => str_replace('_50_square', '_200_square', $image_50px),
                'original' => str_replace('_50_square', '', $image_50px),
            ],
            'location'   => [
                'iso' => $row['country_iso'],
            ],
            'dob'        => [
                'day'   => $day,
                'month' => $month,
                'year'  => $year,
            ],
        ];
    }

    protected function _buildGuest($row)
    {
        $row = (array)$row;
        if (isset($row['user']) && is_array($row['user'])) {
            $row = $row['user'];
        }

        return [
            'id'    => (int)$row['user_id'],
            'group' => (object)[
                'id' => (int)$row['user_group_id'],
            ],
        ];
    }

    public function __toString()
    {
        return (string)call_user_func($this->__toString);
    }
}