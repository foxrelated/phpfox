<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="item_info">
	<ul class="extra_info_middot">
		<li>{$aMail.time_stamp|convert_time}</li>
		<li>&middot;</li>
		<li>
			<span class="js_hover_title">
		{if $aMail.owner_user_id == Phpfox::getUserId()}
			{if $aMail.owner_user_id == $aMail.viewer_user_id && $aMail.owner_user_id == Phpfox::getUserId()}
			{_p var='you'} <span class="js_hover_info">{_p var='this_message_was_sent_from_you'}</span>
			{else}
			{$aMail|user:'viewer_'}<span class="js_hover_info">{_p var='this_message_was_sent_to_full_name' full_name=$aMail|user:'viewer_'}</span>
			{/if}	
		{elseif $aMail.owner_user_id != 0}
			{$aMail|user:'owner_'}<span class="js_hover_info">{_p var='this_message_was_sent_from_full_name' full_name={$aMail|user:'owner_'}</span>
		{else}
			{_p var='site_sent_you_a_message' site=$sSite}<span class="js_hover_info">{_p var='this_message_was_sent_from_full_name' full_name=$sSite}</span>
		{/if}		
			</span>
		</li>
	</ul>
</div>
<div class="item_bar">
    <div class="mail_next_prev">
        <ul>
            {if $iNextId != ""}
                <li class="previous_message"><a href="{url link='mail.view' id=$iNextId}">{_p var='previous'}</a></li>
            {/if}
            {if $iPrevId != ""}
                <li class="next_message"><a href="{url link='mail.view' id=$iPrevId}">{_p var='next'}</a></li>
            {/if}
        </ul>
        <div class="clear"></div>
    </div>
</div>

{if isset($bMass)}
<div class="p_top_8">
    {_p var='mass_message_to'}:
    {foreach from=$aMails name=mass item=aMass}{if $phpfox.iteration.mass != 1}, {/if}{$aMass|user}{/foreach}
</div>
{/if}
<div>
    {$aMail.text|parse|split:100}
    {if $aMail.parent_id && $aMail.text_reply}
    <div class="quote">
        <div class="quote_body">
            {$aMail.text_reply|parse|split:80}
        </div>
    </div>
    {/if}
</div>

{if isset($aAttachments)}
{module name='attachment.list' sType='mail' attachments=$aAttachments}
{/if}