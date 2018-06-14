<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr{*$sLocaleDirection*}" lang="{$sLocaleCode}">
    {if !isset($bShowClearCache)}
    {assign var='bShowClearCache' value=false}
    {/if}
	<head>
    <title>{title}</title>
	{header}
	</head>
	<body class="admincp-fixed-menu {if !empty($sBodyClass)}{$sBodyClass}{/if}">
		<div id="admincp_base"></div>
		<div id="global_ajax_message"></div>
		<div id="header" {if !empty($flavor_id)}class="theme-{$flavor_id}"{/if}>
            {logo}
            <div class="admincp_header_form admincp_search_settings">
                <span class="remove"><i class="fa fa-remove"></i></span>
                <input type="text" name="setting" placeholder="Search settings..." autocomplete="off">
                <div class="admincp_search_settings_results hide">
                </div>
            </div>
            <div class="admincp_right_group">
                <div class="admincp_alert">
                    <a href="{url link='admincp.alert'}">
                        {if $badge}
                        <span class="badge">{$badge}</span>
                        {/if}
                        <i class="ico ico-bell2-o"></i>

                    </a>
                </div>
                <div class="admincp_user">
                    <div class="admincp_user_image">
                        {img user=$aUserDetails suffix='_50_square'}
                    </div>
                    <div class="admincp_user_content">
                        {$aUserDetails|user}
                        {$aUserDetails.user_group_title}
                    </div>
                </div>

                {if !Phpfox::demoModeActive()}
                <div class="admincp_view_site">
                    <a target="_blank" href="{url link=''}">{_p var='view_site'}&nbsp;<i class="fas fa fa-external-link"></i></a>
                </div>
                {/if}
            </div>

		</div>
		<aside class="">
            <ul class="">
                {php}
                    $this->_aVars['aAdminMenus'] =  Phpfox::getService('admincp.sidebar')->prepare()->get();
                {/php}
                {foreach from=$aAdminMenus key=sPhrase item=sLink}
                    {if is_array($sLink)}
                    {assign var='menuId' value="id_menu_item_"$sPhrase}
                    <li id="{$menuId}" {if $sLastOpenMenuId == $menuId}class="open"{/if}>
                <a href="{$sLink.link}" data-tags="{if isset($sLink.tags)}{$sLink.tags}{/if}"
                           {if isset($sLink.items) and !empty($sLink.items)}class="item-header {if isset($sLink.is_active)}is_active{/if}" data-cmd="admincp.open_sub_menu"{else}{if isset($sLink.is_active)}class="is_active"{/if}{/if}>
                        {if !empty($sLink.icon)}<i class="{$sLink.icon}"></i>{/if}
                        {$sLink.label}
                        {if isset($sLink.items) and !empty($sLink.items)}
                        <i class="fa fa-caret"></i>
                        {/if}
                        {if isset($sLink.badge) && $sLink.badge > 0}
                        <span class="badge">{$sLink.badge}</span>
                        {/if}
                        </a>

                    {if isset($sLink.items) and !empty($sLink.items)}
                        <ul>
                            {foreach from=$sLink.items item=sLink2}
                            <li><a data-tags="{if isset($sLink2.tags)}{$sLink2.tags}{/if}" href="{$sLink2.link}" class="{if isset($sLink2.class)}{$sLink2.class}{/if}{if isset($sLink2.is_active)}is_active{/if}">{if !empty($sLink2.icon)}<i class="{$sLink2.icon}"></i>{/if}{$sLink2.label}</a></li>
                            {/foreach}
                        </ul>
                    {/if}
                </li>
                {/if}
                {/foreach}
            </ul>
            <div id="global_remove_site_cache_item">
                <a href="{url link='admincp.maintain.cache' all=1 return=$sCacheReturnUrl}">
                    <i class="ico ico-trash-o"></i>
                    {_p var='clear_all_caches'}
                </a>
            </div>
            <div id="copyright">
                {param var='core.site_copyright'}
            </div>
            <br/>
            <br/>
            <br/>
            <br/>
		</aside>

        <!-- end action menu-->
        <div class="main_holder">
            {if !empty($aAdmincpBreadCrumb) || !empty($sSectionTitle)}
            <div class="breadcrumbs">
                {if !empty($aAdmincpBreadCrumb)}
                    {if count($aAdmincpBreadCrumb) > 1}
                        {foreach from=$aAdmincpBreadCrumb key=sUrl item=sPhrase}
                        <a href="{if !empty($sUrl)}{$sUrl}{else}#{/if}">{$sPhrase}</a>
                        {/foreach}
                    {/if}
                {elseif !empty($sSectionTitle)}
                    <a href="#">{$sSectionTitle}</a>
                {/if}
            </div>
            {/if}

            {if !empty($sLastBreadcrumb)}
                <h1 class="page-title">{$sLastBreadcrumb}</h1>
            {elseif !empty($sSectionTitle)}
                <h1 class="page-title">{$sSectionTitle}</h1>
            {/if}

            {if !empty($aActionMenu) or !empty($aSectionAppMenus)}
            <div class="toolbar-top">
                {if !empty($aSectionAppMenus) && count($aSectionAppMenus) <= 6}
                <div class="btn-group acp-header-section">
                    {foreach from=$aSectionAppMenus key=sPhrase item=aMenu}
                    <a {if isset($aMenu.cmd)}data-cmd="{$aMenu.cmd}"{/if}  href="{if (substr($aMenu.url, 0, 1) == '#')}{$aMenu.url}{else}{url link=$aMenu.url}{/if}"
                       class="{if isset($aMenu.is_active) && $aMenu.is_active}active{/if}">{$sPhrase}</a>
                    {/foreach}
                </div>
                {/if}

                {if isset($aSectionAppMenus) && count($aSectionAppMenus) > 6}
                <div class="btn-group acp-header-section">
                    {foreach from=$aSectionAppMenus key=sPhrase item=aMenu name=fkey}
                    {if $phpfox.iteration.fkey < 6}
                    <a {if isset($aMenu.cmd)}data-cmd="{$aMenu.cmd}"{/if}  href="{if (substr($aMenu.url, 0, 1) == '#')}{$aMenu.url}{else}{url link=$aMenu.url}{/if}"
                    class="{if isset($aMenu.is_active) && $aMenu.is_active}active{/if}">{$sPhrase}</a>
                    {/if}
                    {if $phpfox.iteration.fkey == 6}
                    <div class="acp-menu-dropdown"> <!-- div dropdown -->
                        <a class="dropdown-toggle" id="dropdownMenu1" href="" data-toggle="dropdown" aria-expanded="true" aria-haspopup="true">
                            {_p var="more"}
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu1">
                    {/if}
                    {if $phpfox.iteration.fkey >= 6}
                            <li role="menuitem">
                                <a {if isset($aMenu.cmd)}data-cmd="{$aMenu.cmd}"{/if}  href="{if (substr($aMenu.url, 0, 1) == '#')}{$aMenu.url}{else}{url link=$aMenu.url}{/if}"
                            class="{if isset($aMenu.is_active) && $aMenu.is_active}active{/if}">{$sPhrase}</a>
                            </li>
                    {/if}
                    {/foreach}
                        </ul>
                    </div> <!-- end div dropdown -->
                </div>
                {/if}
                {if isset($aActionMenu)}
                <div class="btn-group acp-action-menus">
                    {if $bMoreThanOneActionMenu}
                    <a role="button" class="btn btn-primary" data-toggle="dropdown">{_p var='actions'} <span class="ico ico-caret-down"></span></a>
                    <ul class="dropdown-menu dropdown-menu-right">
                    {/if}
                    {foreach from=$aActionMenu key=sPhrase item=sUrl}
                        {if is_array($sUrl)}
                            {if $bMoreThanOneActionMenu}
                            <li>
                            {/if}
                            <a {if isset($sUrl.cmd)}data-cmd="{$sUrl.cmd}"{/if}  href="{$sUrl.url}" class="{if $bMoreThanOneActionMenu}{$sUrl.dropdown_class}{else}btn {$sUrl.class}{/if}" {if isset($sUrl.custom)} {$sUrl.custom}{/if}>{$sPhrase}</a>
                            {if $bMoreThanOneActionMenu}
                            </li>
                            {/if}
                        {else}
                            {if $bMoreThanOneActionMenu}
                            <li>
                            {/if}
                            <a href="{$sUrl}">{$sPhrase}</a>
                            {if $bMoreThanOneActionMenu}
                            </li>
                            {/if}
                        {/if}
                    {/foreach}
                    {if $bMoreThanOneActionMenu}
                    </ul>
                    {/if}
                </div>
                {/if}
            </div>
            {/if}

            {if (isset($has_upgrade) && $has_upgrade)}
            <br/>
            <div class="alert alert-danger mb-base">
                {_p var="There is an update available for this product."} <a class="btn btn-link" href="{$store.install_url}">{_p var="Update Now"}</a>
            </div>
            {/if}
            <div id="js_content_container">
                <div id="main">
                    {if isset($aSectionAppMenus)}
                    <div class="apps_content">
                        {/if}

                        {error}
                        <div class="_block_content">
                            {content}
                        </div>

                        {if isset($aSectionAppMenus)}
                    </div>
                    {/if}
                </div>
            </div>
        </div>
		{plugin call='theme_template_body__end'}	
        {loadjs}
	</body>
</html>