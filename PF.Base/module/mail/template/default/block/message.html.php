<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{foreach from=$aMessage name=messages item=aMail}
    {template file='mail.block.entry'}
{/foreach}