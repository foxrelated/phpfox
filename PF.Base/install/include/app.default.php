<?php
$aLiteApps = [
    'Core_Announcement' => [
        'name' => 'Announcement',
        'dir' => 'core-announcement',
    ],
    'Core_Newsletter' => [
        'name' => 'Newsletter',
        'dir' => 'core-newsletter',
    ],
    'Core_Poke' => [
        'name' => 'Poke',
        'dir' => 'core-poke',
    ],
    'Core_Pages' => [
        'name' => 'Pages',
        'dir' => 'core-pages',
    ],
    'PHPfox_Twemoji_Awesome' => [
        'name' => 'Emoji',
        'dir' => 'core-twemoji-awesome',
    ],
    'Core_Music' => [
        'name' => 'Music',
        'dir' => 'core-music',
    ],
    'PHPfox_Facebook' => [
        'name' => 'Facebook Connect',
        'dir' => 'core-facebook',
    ],
    'PHPfox_CDN_Service' => [
        'name' => 'CDN Service',
        'dir' => 'core-cdn-service',
    ],
    'PHPfox_CDN' => [
        'name' => 'phpFox CDN',
        'dir' => 'core-cdn',
    ],
    'PHPfox_AmazonS3' => [
        'name' => 'Amazon CDN',
        'dir' => 'core-amazon-s3',
    ],


    'Core_Captcha' => [
        'name' => 'Captcha',
        'dir' => 'core-captcha',
    ],
    'Core_Events' => [
        'name' => 'Events',
        'dir' => 'core-events',
    ],
    'PHPfox_Groups' => [
        'name' => 'Groups',
        'dir' => 'core-groups',
    ],
    'Core_eGifts' => [
        'name' => 'Egift',
        'dir' => 'core-egift',
    ],
    'Core_RSS' => [
        'name' => 'RSS Feed',
        'dir' => 'core-rss',
    ],
];
$aBasicApps = [
    'Core_Blogs' => [
        'name' => 'Blogs',
        'dir' => 'core-blogs',
    ],
    'Core_Quizzes' => [
        'name' => 'Quizzes',
        'dir' => 'core-quizzes',
    ],
    'Core_Polls' => [
        'name' => 'Polls',
        'dir' => 'core-polls',
    ],
    'Core_Forums' => [
        'name' => 'Forum',
        'dir' => 'core-forums',
    ],
    'Core_Marketplace' => [
        'name' => 'Marketplace',
        'dir' => 'core-marketplace',
    ],
];

$aProApps = [
    'PHPfox_IM' => [
        'name' => 'Instant Messaging',
        'dir' => 'core-im',
    ],
    'PHPfox_Videos' => [
        'name' => 'Videos',
        'dir' => 'core-videos',
    ],
//    'SE Importer'                   => [
//        'name'      => 'SE Importer',
//        'dir'       => 'core-se-importer',
//    ],
//    'Core_BetterAds'                => [
//        'name'      => 'Better Ads',
//        'dir'       => 'core-better-ads',
//    ],
//    'phpFox_Backup_Restore'         => [
//        'name'      => 'Backup and Restore',
//        'dir'       => 'core-backup-restore',
//    ],
    'phpFox_Shoutbox' => [
        'name' => 'Shoutbox',
        'dir' => 'core-shoutbox',
    ],
    'phpFox_CKEditor' => [
        'name' => 'CKEditor',
        'dir' => 'core-CKEditor',
    ],
//    'phpFox_Single_Device_Login'    => [
//        'name'      => 'Single Device Login',
//        'dir'       => 'core-single-device-login',
//    ],
];
$iPackageId = defined('PHPFOX_PACKAGE_ID') ? PHPFOX_PACKAGE_ID : 3;
if ($iPackageId == 1) {
    return $aLiteApps;
} elseif ($iPackageId == 2) {
    return array_merge($aLiteApps, $aBasicApps);
} else {
    return array_merge($aLiteApps, $aBasicApps, $aProApps);
}