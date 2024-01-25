<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) die('Access denied');

if (defined('PAYMENT_NOTIFICATION')) {

    $pp_response = array();
    $pp_response['order_status'] = 'F';
    $pp_response['reason_text'] = __('text_transaction_declined');
    $order_id = !empty($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;

    if ($mode == 'cancel') {
        $pp_response['reason_text'] = __('text_transaction_canceled');
    } elseif ($mode == 'payed') {
        $order_info = fn_get_order_info($order_id);
        $txnId = $_REQUEST['txn'];
        if (empty($processor_data)) {
            $processor_data = fn_get_processor_data($order_info['payment_id']);
        }

        $app_key = $processor_data['processor_params']['app_key'];

        Lunar\Client::setKey($app_key);

        if ($order_info && !empty($txnId)) {
            if ($processor_data['processor_params']['checkout_mode'] == 'delayed') {
                $fetch = Lunar\Transaction::fetch($txnId);

                if (is_array($fetch) && !isset($fetch['transaction'])) {
                    $pp_response['order_status'] = 'F';
                    $pp_response['reason_text'] = implode(',', $fetch);
                } elseif (is_array($fetch) && $fetch['transaction']['custom']['order_id'] == $order_id) {
                    $total = $fetch['transaction']['amount'];
                    $amount = $fetch['transaction']['amount'];
                    $pp_response['order_status'] = $processor_data['processor_params']['delayed_status'];

                    $pp_response['reason_text'] = __("delayed");
                    $pp_response['transaction_id'] = $txnId;
                    $pp_response['kp_lunar.order_time'] = kp_lunar_datetime_to_human($fetch['transaction']['created']);
                    $pp_response['kp_lunar.currency_code'] = $fetch['transaction']['currency'];
                    $pp_response['authorized_amount'] = $fetch['transaction']['amount'];
                    //$pp_response['captured_amount'] = $fetch['transaction']['capturedAmount'];
                    $pp_response['captured'] = 'N';
                    array_filter($pp_response);
                }
            } else {

                $data = array(
                    'currency'   => $order_info['currency'],
                    'amount'     => $order_info['total'],
                );
                $capture = Lunar\Transaction::capture($txnId, $data);

                if (is_array($capture) && !isset($capture['transaction'])) {
                    $message = implode(',', $capture);
                    $pp_response['order_status'] = 'F';
                    $pp_response['reason_text'] = $message;
                } elseif (!empty($capture['transaction'])) {
                    $pp_response['order_status'] = 'P';
                    $pp_response['reason_text'] = __("captured");
                    $pp_response['transaction_id'] = $txnId;
                    $pp_response['kp_lunar.order_time'] = kp_lunar_datetime_to_human($capture['transaction']['created']);
                    $pp_response['kp_lunar.currency_code'] = $capture['transaction']['currency'];
                    $pp_response['authorized_amount'] = $capture['transaction']['amount'] / $currency_multiplier;
                    $pp_response['captured_amount'] = $capture['transaction']['capturedAmount'] / $currency_multiplier;
                    $pp_response['captured'] = 'Y';
                    array_filter($pp_response);
                } else {
                    $transaction_failed = true;
                }
            }
        }
    }

    if (fn_check_payment_script('lunar.php', $order_id)) {
        fn_finish_payment($order_id, $pp_response);
        fn_order_placement_routines('route', $order_id);
    }

} else {

    $view = Tygh::$app['view'];
    $view->assign('processor_data', $processor_data);
    $view->assign('order_info', $order_info);
    $view->assign('order_id', $order_id);
    $view->assign('public_key', $processor_data['processor_params']['public_key']);
    $view->assign('total',$order_info['total']);
    $view->assign('canceled_url', fn_url("payment_notification.cancel?payment=lunar&order_id=$order_id", AREA, 'current'));
    $view->assign('payed_url', fn_url("payment_notification.payed?payment=lunar&order_id=$order_id", AREA, 'current'));
    $customer = [
        'email' => $order_info['email'],
        'phone' => $order_info['phone'],
        'address' => !empty($order_info['b_address']) ? $order_info['b_address'] : $order_info['s_address'],
        'city' => !empty($order_info['b_city']) ? $order_info['b_city'] : $order_info['s_city'],
        'state' => !empty($order_info['b_state']) ? $order_info['b_state'] : $order_info['s_state'],
        'zip' => !empty($order_info['b_zipcode']) ? $order_info['b_zipcode'] : $order_info['s_zipcode'],
        'country' => !empty($order_info['b_country']) ? $order_info['b_country'] : $order_info['s_country'],
    ];
    $view->assign('customer', $customer);
    $platform = [
        'name' => 'CS-Cart ' . PRODUCT_NAME,
        'version' => PRODUCT_VERSION,
        'addon_name' => 'kp_lunar',
        'addon_version' => fn_get_addon_version('kp_lunar'),
    ];
    $view->assign('platform', $platform);
    // $view->display('addons/kp_lunar/components/payment_page.tpl');
    die(1);
}
