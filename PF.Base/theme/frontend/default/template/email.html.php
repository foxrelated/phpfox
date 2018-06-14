<?php 
 
defined('PHPFOX') or exit('NO DICE!'); 

?>
{if $bHtml}	
{if $bMessageHeader}
	{if isset($sMessageHello)}
	{$sMessageHello}
	{else}
	{_p var='hello'}
	{/if},
	<br />
	<br />
{/if}
	{$sMessage}
	<br />
	<br />
	{$sEmailSig}	
{else}	
{if $bMessageHeader}
	{if isset($sMessageHello)}
	{$sMessageHello}
	{else}
	{_p var='hello'}
	{/if},
{/if}	
	{$sMessage}

	{$sEmailSig}	
{/if}