<?php
defined('PHPFOX') or exit('NO DICE!');

?>
<div class="block news-updates-container">
    <div class="title">
        {_p var='more_news_and_update'}
    </div>
    <div class="content">
        {foreach from=$aPhpfoxNews name=news item=aNews}
        <div class="item-separated">
            <a href="{$aNews.link}" target="_blank">{$aNews.title|clean}</a>
            <div class="text-muted">
                <span>{_p var='posted_by'} {$aNews.creator}</span>
                <span>{$aNews.time_stamp}</span>
            </div>
        </div>
        {/foreach}
    </div>
    <div class="bottom">
        <ul>
            <li id="js_block_bottom_1" class="first">
                <a href="https://www.phpfox.com/blog/category/community-roundup/" target="_blank" id="js_block_bottom_link_1">
                    {_p var='view_all'}
                </a>
            </li>
        </ul>
    </div>
</div>