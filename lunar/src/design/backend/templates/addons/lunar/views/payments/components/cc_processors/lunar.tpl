<div class="control-group">
    <label class="control-label cm-required" for="payment_method">{__("lunar.payment_method")}:</label>
    <label class="control-label" for="lunar_card">{__("lunar.payment_method.card")}
        <input type="radio" name="payment_data[processor_params][payment_method]" id="lunar_card" value="card" class="input-radio" {if not $processor_params.payment_method or $processor_params.payment_method == "card"}checked{/if}/>
    </label>

    <label class="control-label" for="lunar_mobilepay">{__("lunar.payment_method.mobilepay")}
        <input type="radio" name="payment_data[processor_params][payment_method]" id="lunar_mobilepay" value="mobilePay" class="input-radio" {if $processor_params.payment_method == "mobilePay"}checked{/if}/>
    </label>
</div>

<div class="control-group">
    <label class="control-label cm-required" for="mo_app_key">{__("lunar.app_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][app_key]" id="mo_app_key" value="{$processor_params.app_key}" class="input-text" size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label cm-required" for="mo_public_key">{__("lunar.public_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][public_key]" id="mo_public_key" value="{$processor_params.public_key}" class="input-text" size="60" />
    </div>
</div>

<div class="control-group" style="display: none;">
    <label class="control-label" for="mo_configuration_id">{__("lunar.configuration_id")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][configuration_id]" id="mo_configuration_id" value="{$processor_params.configuration_id}" class="input-text" size="60" />
    </div>
</div>


<script type="text/javascript">
{literal}
    if (jQuery('#lunar_mobilepay').is(':checked')) {
        jQuery('#mo_configuration_id').parents('.control-group').show();
        jQuery('[for="mo_configuration_id"]').addClass('cm-required');
    } else {
        jQuery('#mo_configuration_id').parents('.control-group').hide();
        jQuery('#mo_configuration_id').val('');
        jQuery('[for="mo_configuration_id"]').removeClass('cm-required');
    }

    jQuery('[name="payment_data[processor_params][payment_method]"]').on('click', (e) => {
        if ('lunar_mobilepay' === e.target.id) {
            jQuery('#mo_configuration_id').parents('.control-group').show();
            jQuery('[for="mo_configuration_id"]').addClass('cm-required');
        } else {
            jQuery('#mo_configuration_id').parents('.control-group').hide();
            jQuery('#mo_configuration_id').val('');
            jQuery('[for="mo_configuration_id"]').removeClass('cm-required');
        }
    })
{/literal}
</script>

<div class="control-group">
    <label class="control-label cm-required" for="mo_logo_url">{__("lunar.logo_url")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][logo_url]" id="mo_logo_url" value="{$processor_params.logo_url}" class="input-text" size="60" />
    </div>
</div>


{$shop_title = $processor_params.shop_title|default:$settings.Company.company_name}
<div class="control-group">
    <label class="control-label" for="mo_shop_title">{__("lunar.shop_title")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][shop_title]" id="mo_shop_title" value="{$shop_title}" class="input-text" size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mo_checkout_mode">{__("lunar.checkout_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][checkout_mode]" id="mo_checkout_mode">
            <option value="delayed" {if $processor_params.checkout_mode=='delayed'}selected="selected"{/if}>Delayed</option>
            <option value="instant" {if $processor_params.checkout_mode=='instant'}selected="selected"{/if}>Instant</option>
        </select>

    </div>
</div>

{$delayed_status=$processor_params.delayed_status|default:"P"}
<div class="control-group">
    <label class="control-label" for="mo_delayed_status">{__("lunar.delayed_status")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][delayed_status]" id="mo_delayed_status">
            {foreach from=lunar_get_order_statuses_list() item="n" key="k"}
                <option value="{$k}" {if $delayed_status==$k}selected="selected"{/if}>{$n}</option>
            {/foreach}
        </select>
        <p>{__("lunar.delayed_status_help")}</p>
    </div>
</div>

{$capture_status=$processor_params.capture_status|default:"C"}
<div class="control-group">
    <label class="control-label" for="mo_capture_status">{__("lunar.capture_status")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][capture_status]" id="mo_capture_status">
            {foreach from=lunar_get_order_statuses_list() item="n" key="k"}
            <option value="{$k}" {if $capture_status==$k}selected="selected"{/if}>{$n}</option>
            {/foreach}
        </select>
        <p>{__("lunar.capture_status_help")}</p>
    </div>
</div>

{$void_status=$processor_params.void_status|default:"I"}
<div class="control-group">
    <label class="control-label" for="mo_capture_status">{__("lunar.void_status")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][void_status]" id="mo_void_status">
            {foreach from=lunar_get_order_statuses_list() item="n" key="k"}
                <option value="{$k}" {if $void_status==$k}selected="selected"{/if}>{$n}</option>
            {/foreach}
        </select>
        <p>{__("lunar.void_status_help")}</p>
    </div>
</div>


<script type="text/javascript">
{literal}
    jQuery('[id="elm_payment_name_0"]').val('{/literal}{__("lunar.payment_method.card")}{literal}');
    jQuery('[id="elm_payment_description_0"]').val('{/literal}{__("lunar.description")}{literal}');
{/literal}
</script>