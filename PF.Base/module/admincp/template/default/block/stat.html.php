<div id="admincp_stat" class="block">
    <div class="content stats-me">
        {foreach from=$aItems item=aItem}
        <div class="stat-item clearfix">
            <div class="item-outer">
                <div class="item-icon">
                    <span class="{$aItem.icon}"></span>
                </div>
                <div class="item-info">
                    <div class="item-number">{$aItem.value|number_format}</div>
                    <div class="item-text">{$aItem.phrase}</div>
                </div>
            </div>
        </div>
        {/foreach}
    </div>
</div>