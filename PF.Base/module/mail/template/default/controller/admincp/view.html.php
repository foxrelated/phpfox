<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
{foreach from=$aMessage item=aMail}
<div class="row1 mail_thread_holder">
    <div class="row_title">
        <div class="row_title_image">
            {img user=$aMail suffix='_50_square' max_width=50 max_height=50}
        </div>
        <div class="row_title_info">
            <div class="mail_action">
                <ul>
                    <li><span class="extra_info">{$aMail.time_stamp|convert_time}</span></li>
                </ul>
            </div>
            <div class="mail_thread_user">
                {$aMail|user}
            </div>
            <div>
                {$aMail.text|parse|split:200}
            </div>
        </div>
    </div>
</div>
{/foreach}
<div class="t_right">
	<ul class="item_menu">
		<li><a href="{url link='admincp.mail.private' delete=$aMessage[0].thread_id}" class="sJsConfirm">{_p var='delete'}</a></li>
	</ul>
	<div class="clear"></div>
</div>