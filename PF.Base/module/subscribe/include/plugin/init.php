<?php
defined('PHPFOX') or exit('NO DICE!');
$req1 = Phpfox::getLib('request')->get('req1');
$req2 = Phpfox::getLib('request')->get('req2');

$bLogout = ($req1 == 'logout') || (($req1 == 'user') && ($req2 == 'logout'));
if (!PHPFOX_IS_AJAX && !$bLogout)
{
	$mRedirectId = Phpfox::getService('subscribe.purchase')->getRedirectId();
	if (is_numeric($mRedirectId) && $mRedirectId > 0)
	{
		Phpfox_Url::instance()->send('subscribe.register', array('id' => $mRedirectId), _p('please_complete_your_purchase'));
	}

    $mRedirectId = Phpfox::getService('subscribe.purchase')->isCompleteSubscribe();
	if (is_numeric($mRedirectId) && $mRedirectId > 0)
	{
		Phpfox_Url::instance()->send('subscribe.register', array('id' => $mRedirectId), _p('please_complete_your_purchase'));
	}
}
?>