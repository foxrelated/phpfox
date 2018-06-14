<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
{if $aAd.type_id == 1}
	<a href="{url link='ad' id=$aAd.ad_id}" target="_blank">{img file=$aAd.image_path path='ad.url_image' server_id=$aAd.server_id}</a>
{else}
	{$aAd.html_code}
{/if}

{plugin call='ad.template_block_display__end'}