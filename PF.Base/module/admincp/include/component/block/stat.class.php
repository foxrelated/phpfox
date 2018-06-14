<?php
/**
 * @author  OvalSky
 * @license phpfox.com
 */
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class Admincp_Component_Block_Stat
 */
class Admincp_Component_Block_Stat extends Phpfox_Component
{
    public function process()
    {
        $aItems = [];
        $aIcons = [
            'photo.photos' => 'ico-photos-alt-o',
            'videos' => 'ico-videocam-o',
            'event.events' => 'ico-calendar-star-o',
            'blog.blogs' => 'ico-compose-alt',
            'comment.comment_on_items' => 'ico-comment-square-o',
            'user.users' => 'ico-user-man-three-o'
        ];
        $stats = Phpfox::getService('core.stat')->getSiteStatsForAdmin(0, time());
        $counter = 0;

        usort($stats, function ($a, $b) {
            if ($a['phrase'] == 'user.users') {
                return -2;
            }

            return ($a['total'] > $b['total']) ? -1 : 1;
        });

        $stats = array_filter($stats, function ($a) use (&$counter) { // limit 4 items in selected array
            $a['phrase'] = isset($a['phrase']) ? $a['phrase'] : '';

            return in_array($a['phrase'],
                    ['user.users', 'photo.photos', 'videos', 'event.events', 'blog.blogs', 'comment.comment_on_items'])
                and $counter++ < 4;
        });

        foreach ($stats as $stat) {
            $key = $stat['phrase'];

            if ($key == 'user.users') {
                $aUser = [
                    'phrase' => _p($key),
                    'value' => $stat['total'],
                    'icon' => empty($aIcons[$key]) ? '' : 'ico ' . $aIcons[$key]
                ];

                continue;
            }

            $aItems[] = [
                'phrase' => _p($key),
                'value' => $stat['total'],
                'icon' => empty($aIcons[$key]) ? '' : 'ico ' . $aIcons[$key]
            ];
        }

        if (isset($aUser)) {
            $aItems[] = $aUser;
        }

        $this->template()->assign([
            'aItems' => $aItems,
        ]);

        return 'block';
    }
}
