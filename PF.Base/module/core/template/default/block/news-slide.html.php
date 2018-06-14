<?php 
defined('PHPFOX') or exit('NO DICE!');
?>

<div id="carousel-fox-news" class="carousel slide block" data-ride="carousel">
    <ol class="carousel-indicators">
        {foreach from=$aPhpfoxNewsSlide name=news item=aNewsSlide key=iKey}
        <li data-target="#carousel-fox-news" data-slide-to="{$iKey}" class="{if $iKey==0} active {/if}"></li>
        {/foreach}
    </ol>
    <div class="carousel-inner slider-fox-news-container">
        {foreach from=$aPhpfoxNewsSlide key=iKey name=news item=aNewsSlide}
        <div class="item {if $iKey==0} active {/if}">
            <div class="item-outer">
                {if $aNewsSlide.image}
                <div class="item-media">
                    <span style="background-image: url({$aNewsSlide.image})"></span>
                </div>
                {/if}
                <div class="item-inner">
                    <div class="carousel-caption">
                        <div class="item-title">
                            <a href="{$aNewsSlide.link}" target="_blank">{$aNewsSlide.title|clean}</a>
                        </div>
                        <div class="item-info">
                            <span>{_p var='by'} {$aNewsSlide.creator}</span>
                            <span>{$aNewsSlide.time_stamp}</span>
                        </div>
                        <div class="item-desc">
                            {$aNewsSlide.description|striptag|stripbb}
                        </div>
                    </div>
                </div>

            </div>
        </div>
        {/foreach}

    </div>
    <div class="controllers">
        <!-- Controls -->
        <a class="left carousel-control" href="#carousel-fox-news" data-slide="prev">
            <span class="ico ico-angle-left"></span>
        </a>
        <a class="right carousel-control" href="#carousel-fox-news" data-slide="next">
            <span class="ico ico-angle-right"></span>
        </a>
    </div>
</div>
