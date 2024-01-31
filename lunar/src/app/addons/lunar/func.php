<?php

if (!class_exists('\\Lunar\\Payment\\Transaction')) {
    require_once('Transaction.php');
}

use Lunar\Payment\Transaction;


function lunar_get_order_statuses_list()
{
    $statuses = fn_get_statuses();
    $data = array();
    foreach ($statuses as $k => $status) {
        $data[$k] = $status['description'];
    }
    return $data;
}

function fn_lunar_change_order_status($status_to, $status_from, &$order_info, $force_notification, $order_statuses, $place_order)
{
    $doCapture = false;
    $doVoid = false;
    $transaction_id = null;

    if ($order_info['payment_method']['processor'] == 'Lunar Payment Gateway') {
        if ($order_info['payment_method']['processor_params']['checkout_mode'] == 'delayed') {
            if ($order_info['payment_method']['processor_params']['capture_status'] == $status_to && $order_info['payment_method']['processor_params']['delayed_status'] == $status_from) {
                $captured = !empty($order_info['payment_info']['captured']) ? $order_info['payment_info']['captured'] : 'Y';
                $transaction_id = !empty($order_info['payment_info']['transaction_id']) ? $order_info['payment_info']['transaction_id'] : '';
                if ($captured == 'N' && !empty($transaction_id)) {
                    $doCapture = true;
                }
            } elseif ($order_info['payment_method']['processor_params']['void_status'] == $status_to && $order_info['payment_method']['processor_params']['delayed_status'] == $status_from) {
                $captured = !empty($order_info['payment_info']['captured']) ? $order_info['payment_info']['captured'] : 'Y';
                $transaction_id = !empty($order_info['payment_info']['transaction_id']) ? $order_info['payment_info']['transaction_id'] : '';
                if ($captured == 'N' && !empty($transaction_id)) {
                    $doVoid = true;
                }
            }
        }
    }

    if ($doCapture) {
        Transaction::capture($order_info, $transaction_id);
    } 
    if ($doVoid) {
        Transaction::void($order_info, $transaction_id);
    }
}

function lunar_can_refund_order($order_info)
{
    $out = false;
    if ($order_info['payment_method']['processor'] == 'Lunar Payment Gateway') {
        $captured = !empty($order_info['payment_info']['captured']) ? $order_info['payment_info']['captured'] : 'Y';
        $refunded = !empty($order_info['payment_info']['refunded']) ? $order_info['payment_info']['refunded'] : 'N';
        $transaction_id = !empty($order_info['payment_info']['transaction_id']) ? $order_info['payment_info']['transaction_id'] : '';
        $captured_amount = !empty($order_info['payment_info']['captured_amount']) ? floatval($order_info['payment_info']['captured_amount']) : 0;
        $refunded_amount = !empty($order_info['payment_info']['refunded_amount']) ? floatval($order_info['payment_info']['refunded_amount']) : 0;
        if ($captured == 'Y' && !empty($transaction_id) && $captured_amount > 0 && ($refunded == 'N' || ($refunded == 'Y' && $refunded_amount < $captured_amount))) {
            $out = true;
        }
    }
    return $out;
}

function lunar_delete_payment_processors()
{
    db_query("UPDATE ?:payments SET processor_id = 0, processor_params='', status='D' WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('lunar.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('lunar.php')");
}

function lunar_datetime_to_human($date_time)
{
    $timestamp = is_string($date_time) ? strtotime($date_time) : $date_time;
    $out = sprintf(
        "%s %s",
        fn_date_format($timestamp, \Tygh\Registry::get('settings.Appearance.date_format')),
        fn_date_format($timestamp, \Tygh\Registry::get('settings.Appearance.time_format'))
    );
    return $out;
}
