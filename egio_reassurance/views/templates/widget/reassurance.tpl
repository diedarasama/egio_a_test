<div class="egio_reassurances">
    {foreach from=$reassurances item=reassurance}
        <div class="egio_block">
            {if !empty($reassurance.link)}<a href="{$reassurance.link}" title="{$reassurance.link_title}" {if $reassurance.blank}target="_blank"{/if} >{/if}
            {if !empty($reassurance.icon)}<img src="{$images_uri}{$reassurance.icon}" class="egio_icon" alt="{$reassurance.icon_alt}" />{/if}
            <div class="egio_description">{$reassurance.description nofilter}</div>
            {if !empty($reassurance.link)}</a>{/if}
        </div>
    {/foreach}
</div>