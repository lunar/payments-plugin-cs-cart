{if lunar_can_refund_order($order_info)}
    <a 
        class="btn cm-dialog-opener cm-dialog-auto-size cm-ajax"
        href="{"lunar.refund?order_id=`$order_info.order_id`"|fn_url}"
        data-ca-dialog-title="Refund"
    >
        {__("lunar.refund")}
    </a>
{/if}