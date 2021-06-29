{if $errors}
<div class="alert alert-danger d-print-none" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true"><i class="material-icons">close</i></span>
    </button>
    <div class="alert-text">
        <ul>
            {foreach from=$errors item=error}
                <li>{$error}</li>
            {/foreach}
        </ul>
    </div>
</div>
{/if}
{if $is_updated}
<div class="alert alert-success d-print-none" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true"><i class="material-icons">close</i></span>
    </button>
    <div class="alert-text">
        <p>
            {l s='Updated' mod='egio_reassurance'}
        </p>
    </div>
</div>
{/if}
<div style="height: 51px;">
{if $show_back_btn}
	<a href="{$back_link}" class="btn btn-default">
		<i class="icon-close"></i> {l s='Back' mod='egio_reassurance'}
	</a>
{/if}
{if $show_add_btn && $can_add}
	<a href="{$add_link}" class="btn btn-default pull-right">
		<i class="icon-plus"></i> {l s='Add new' mod='egio_reassurance'}
	</a>
{/if}
</div>
{addJsDef egio_images_uri=$images_uri}