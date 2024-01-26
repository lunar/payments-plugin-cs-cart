<?php

if (!defined('BOOTSTRAP')) die('Access denied');


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'refund') {
        $order_id = $_REQUEST['order_id'];
        $amount = $_REQUEST['amount'];

        $order_info = fn_get_order_info($order_id);
        $txnId = !empty($order_info['payment_info']['transaction_id']) ? $order_info['payment_info']['transaction_id'] : '';
        if ($amount <= $order_info['total']) {
            $cc = \lunar\Transaction::refund($order_info, $txnId, $amount);
        }
        return array(CONTROLLER_STATUS_OK, 'orders.details?order_id=' . $order_id);
    }
} else {
    if ($mode == 'refund') {
        $order_id = $_REQUEST['order_id'];
        $order_info = fn_get_order_info($order_id);
        $captured_amount = !empty($order_info['payment_info']['captured_amount']) ? floatval($order_info['payment_info']['captured_amount']) : 0;
        $refunded_amount = !empty($order_info['payment_info']['refunded_amount']) ? floatval($order_info['payment_info']['refunded_amount']) : 0;

        $view = Tygh::$app['view'];
        $view->assign('order_info', $order_info);
        $view->assign('order_id', $order_id);
        $view->assign('amount', $captured_amount - $refunded_amount);
    }
}
