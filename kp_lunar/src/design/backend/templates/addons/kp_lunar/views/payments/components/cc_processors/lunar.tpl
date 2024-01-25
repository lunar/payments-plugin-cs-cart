<div class="control-group">
    <label class="control-label" for="mo_app_key">{__("kp_lunar.app_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][app_key]" id="mo_app_key" value="{$processor_params.app_key}" class="input-text" size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mo_public_key">{__("kp_lunar.public_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][public_key]" id="mo_public_key" value="{$processor_params.public_key}" class="input-text" size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mo_logo_url">{__("kp_lunar.logo_url")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][logo_url]" id="mo_logo_url" value="{$processor_params.logo_url}" class="input-text" size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mo_description">{__("kp_lunar.description")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][description]" id="mo_description" value="{$processor_params.logo_url}" class="input-text" size="60" />
    </div>
</div>


{$p_shop_title = $processor_params.shop_title|default:$settings.Company.company_name}
<div class="control-group">
    <label class="control-label" for="mo_shop_title">{__("kp_lunar.shop_title")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][shop_title]" id="mo_shop_title" value="{$p_shop_title}" class="input-text" size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mo_checkout_mode">{__("kp_lunar.checkout_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][checkout_mode]" id="mo_checkout_mode">
            <option value="delayed" {if $processor_params.checkout_mode=='delayed'}selected="selected"{/if}>Delayed</option>
            <option value="instant" {if $processor_params.checkout_mode=='instant'}selected="selected"{/if}>Instant</option>
        </select>

    </div>
</div>

{$p_delayed_status=$processor_params.delayed_status|default:"P"}
<div class="control-group">
    <label class="control-label" for="mo_delayed_status">{__("kp_lunar.delayed_status")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][delayed_status]" id="mo_delayed_status">
            {foreach from=kp_lunar_get_order_statuses_list() item="n" key="k"}
                <option value="{$k}" {if $p_delayed_status==$k}selected="selected"{/if}>{$n}</option>
            {/foreach}
        </select>
        <p>{__("kp_lunar.delayed_status_help")}</p>
    </div>
</div>

{$p_capture_status=$processor_params.capture_status|default:"C"}
<div class="control-group">
    <label class="control-label" for="mo_capture_status">{__("kp_lunar.capture_status")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][capture_status]" id="mo_capture_status">
            {foreach from=kp_lunar_get_order_statuses_list() item="n" key="k"}
            <option value="{$k}" {if $p_capture_status==$k}selected="selected"{/if}>{$n}</option>
            {/foreach}
        </select>
        <p>{__("kp_lunar.capture_status_help")}</p>
    </div>
</div>

{$p_void_status=$processor_params.void_status|default:"I"}
<div class="control-group">
    <label class="control-label" for="mo_capture_status">{__("kp_lunar.void_status")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][void_status]" id="mo_void_status">
            {foreach from=kp_lunar_get_order_statuses_list() item="n" key="k"}
                <option value="{$k}" {if $p_void_status==$k}selected="selected"{/if}>{$n}</option>
            {/foreach}
        </select>
        <p>{__("kp_lunar.void_status_help")}</p>
    </div>
</div>
