<?php 
defined('PHPFOX') or exit('NO DICE!');
?>

{if $iFolder}
<div style="position:absolute; right:0px; top:-15px;">
	<a href="#" onclick="$Core.jsConfirm({l}message: '{_p var='are_you_sure'}'{r}, function(){l} $.ajaxCall('mail.deleteFolder', 'id={$iFolder}'); {r},function(){l}{r}); return false;">{_p var='delete_this_list'}</a>
</div>
{/if}
{if count($aMails)}
<div class="item-container" id="collection-mails">
{foreach from=$aMails item=aMail name=mail}
<div id="js_message_{$aMail.thread_id}" class="mail_holder{if !$bIsSentbox && !$bIsTrash && $aMail.viewer_is_new} mail_is_new{/if} moderation_row">
	<div class="mail_moderation">
		<a href="#{$aMail.thread_id}" class="moderate_link" rel="mail" data-id="mod">{_p var='moderate'}</a>
	</div>
	<div class="mail_image">
		{if $aMail.user_id == Phpfox::getUserId()}
			{img user=$aMail suffix='_50_square' max_width=50 max_height=50}
		{else}
			{if (isset($aMail.user_id) && !empty($aMail.user_id))}
				{img user=$aMail suffix='_50_square' max_width=50 max_height=50}
			{/if}
		{/if}
	</div>
	<div class="mail_content">
		<div class="mail_action {if $bIsSentbox && isset($aMail.users_is_read) && count($aMail.users_is_read)}not-has-unready
		{/if}">
			<ul>
				<li>{$aMail.time_stamp|convert_time}</li>
				{if !$bIsSentbox && !$bIsTrash}
				<li class="js_mail_mark_read"{if !$aMail.viewer_is_new} style="display:none;"{/if}><a href="#" class="mail_read js_hover_title" onclick="$.ajaxCall('mail.toggleRead', 'id={$aMail.thread_id}', 'GET'); $(this).parent().hide(); $(this).parents('ul:first').find('.js_mail_mark_unread').show(); $(this).parents('.mail_holder:first').removeClass('mail_is_new'); return false;"><span class="js_hover_info">{_p var='mark_as_read'}</span></a></li>
				<li class="js_mail_mark_unread"{if $aMail.viewer_is_new} style="display:none;"{/if}><a href="#" class="mail_read js_hover_title" onclick="$.ajaxCall('mail.toggleRead', 'id={$aMail.thread_id}', 'GET'); $(this).parent().hide(); $(this).parents('ul:first').find('.js_mail_mark_read').show(); $(this).parents('.mail_holder:first').addClass('mail_is_new'); return false;"><span class="js_hover_info">{_p var='mark_as_unread'}</span></a></li>
				{/if}
				{if $bIsTrash}
				
				{else}
				<li><a href="#" class="mail_delete js_hover_title" onclick="$.ajaxCall('mail.delete', 'id={$aMail.thread_id}{if $bIsSentbox}&amp;type=sentbox{/if}{if $bIsTrash}&amp;type=trash{/if}', 'GET'); return false;"><span class="js_hover_info">{_p var="archive"}</span></a></li>
				{/if}
			</ul>
			<div class="clear"></div>
		</div>	
		<a href="{url link='mail.thread' id=$aMail.thread_id}{if $bIsSentbox}view_sent/{/if}" class="mail_link">
            {$aMail.thread_name}
		</a>		

		{if Phpfox::getParam('mail.show_preview_message')}
		<div class="mail_preview item_view_content">
			{if isset($aMail.last_user_id) && $aMail.last_user_id == Phpfox::getUserId()}{img theme='layout/arrow_left.png' class='v_middle'} {/if}{$aMail.preview|cleanbb|clean}
		</div>
		{/if}		
		
	</div>	
</div>
{/foreach}
</div>
{elseif !PHPFOX_IS_AJAX}

<div class="extra_info mail_duplication_content">
	{_p var='no_messages_found_here'}
</div>
{/if}
<input type="button" value="{_p var='mark_all_read'}" class="button button_off mail_duplication_content" onclick="$.ajaxCall('mail.markallread', 'reload=1')"/>
{if $iTotalMessages}
{moderation}
{/if}
{pager}