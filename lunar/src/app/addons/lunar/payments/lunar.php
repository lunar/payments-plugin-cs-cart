<?php

if (!defined('BOOTSTRAP')) die('Access denied');

if (!class_exists('\\Lunar\\Lunar')) {
    require_once(dirname(__DIR__) . '/vendor/autoload.php');
}

// BEFORE REDIRECT
if (!defined('PAYMENT_NOTIFICATION')) {

    $paymentParams = $order_info['payment_method']['processor_params'];

    $args = lunarGetArgs($order_info);

    $test_mode = !! fn_get_cookie('lunar_testmode');

    $api_client = new \Lunar\Lunar($paymentParams['app_key'], null, $test_mode);

    $payment_intent_id = $api_client->payments()->create($args);

    $remote_url = 'https://pay.lunar.money/?id=';
    if ($test_mode) {
        $remote_url = 'https://hosted-checkout-git-develop-lunar-app.vercel.app/?id=';
    }

    header('Location: ' . $remote_url . $payment_intent_id);

    exit(0);

// CALLBACK
} elseif (defined('PAYMENT_NOTIFICATION')) {

    $pp_response = [];
    $pp_response['order_status'] = 'F';
    $pp_response['reason_text'] = __('text_transaction_declined');
    $order_id = !empty($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;

    if ($mode == 'cancel') {
        $pp_response['reason_text'] = __('text_transaction_canceled');

    } elseif ($mode == 'payed') {
        $order_info = fn_get_order_info($order_id);

        if (empty($processor_data)) {
            $processor_data = fn_get_processor_data($order_info['payment_id']);
        }

        $app_key = $processor_data['processor_params']['app_key'];

        if ($order_info) {
            if ($processor_data['processor_params']['checkout_mode'] == 'delayed') {
                // $fetch = \Lunar\Payment\Transaction::fetch($txnId);

                // @TODO make a function to process this data

                // if (is_array($fetch) && !isset($fetch['transaction'])) {
                //     $pp_response['order_status'] = 'F';
                //     $pp_response['reason_text'] = implode(',', $fetch);
                // } elseif (is_array($fetch) && $fetch['transaction']['custom']['order_id'] == $order_id) {
                    // $total = $fetch['transaction']['amount'];
                    // $amount = $fetch['transaction']['amount'];
                    // $pp_response['order_status'] = $processor_data['processor_params']['delayed_status'];

                    // $pp_response['reason_text'] = __("delayed");
                    // $pp_response['transaction_id'] = $txnId;
                    // $pp_response['lunar.order_time'] = lunar_datetime_to_human($fetch['transaction']['created']);
                    // $pp_response['lunar.currency_code'] = $fetch['transaction']['currency'];
                    // $pp_response['authorized_amount'] = $fetch['transaction']['amount'];

                    $total = $order_info['total'];
                    $pp_response['order_status'] = $processor_data['processor_params']['delayed_status'];

                    $pp_response['reason_text'] = __("delayed");
                    $pp_response['transaction_id'] = $order_info;
                    $pp_response['lunar.order_time'] = lunar_datetime_to_human($order_info['timestamp']);
                    $pp_response['lunar.currency_code'] = $order_info['secondary_currency'];
                    // $pp_response['authorized_amount'] = $order_info['amount'];
                    $pp_response['authorized_amount'] = "36";



                    //$pp_response['captured_amount'] = $fetch['transaction']['capturedAmount'];
                    $pp_response['captured'] = 'N';
                    array_filter($pp_response);
                // }
            } else {
                $data = array(
                    'currency'   => $order_info['secondary_currency'],
                    'amount'     => $order_info['total'],
                );
                $capture = \Lunar\Payment\Transaction::capture($txnId, $data);

                if (is_array($capture) && !isset($capture['transaction'])) {
                    $message = implode(',', $capture);
                    $pp_response['order_status'] = 'F';
                    $pp_response['reason_text'] = $message;
                } elseif (!empty($capture['transaction'])) {
                    $pp_response['order_status'] = 'P';
                    $pp_response['reason_text'] = __("captured");
                    $pp_response['transaction_id'] = $txnId;
                    $pp_response['lunar.order_time'] = lunar_datetime_to_human($capture['transaction']['created']);
                    $pp_response['lunar.currency_code'] = $capture['transaction']['currency'];
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

}


/**
 * 
 */
function lunarGetArgs($order_info)
{
    $order = $order_info;
    $paymentParams = $order_info['payment_method']['processor_params'];

    $customer = [
        'firstname' => !empty($order['b_firstname']) ? $order['b_firstname'] : $order['s_firstname'],
        'lastname' => !empty($order['b_lastname']) ? $order['b_lastname'] : $order['s_lastname'],
        'address' => !empty($order['b_address']) ? $order['b_address'] : $order['s_address'],
        'city' => !empty($order['b_city']) ? $order['b_city'] : $order['s_city'],
        'state' => !empty($order['b_state']) ? $order['b_state'] : $order['s_state'],
        'zip' => !empty($order['b_zipcode']) ? $order['b_zipcode'] : $order['s_zipcode'],
        'country' => !empty($order['b_country']) ? $order['b_country'] : $order['s_country'],
    ];

    $args = [
        'integration' => [
            'key' => $paymentParams['public_key'],
            'name' => $paymentParams['shop_title'],
            'logo' => $paymentParams['logo_url'],
        ],
        'amount'     => [
            'currency' => $order['secondary_currency'], // @TODO get currency from elsewhere if possible 
            'decimal' => $order['total'],
        ],
        'custom' => [
            'orderId'    => $order['order_id'],
            'products'   => lunarGetFormattedProducts($order),
            'customer'   => [
                'name'    => $customer['firstname'] . ' ' . $customer['lastname'],
                'address' => $customer['address'] . ', ' . $customer['city'] . ', ' . $customer['state'] . ', ' 
                                . $customer['zip'] . ', ' . $customer['country'],
                'email'   => $order['email'],
                'phoneNo' => $order['phone'],
                'ip'      => !empty(fn_get_ip()['host']) ? fn_get_ip()['host'] : $_SERVER['REMOTE_ADDR'],
            ],
            'platform' => [
                'name' => PRODUCT_NAME,
                'version' => PRODUCT_VERSION,
            ],
            'lunarPluginVersion' => fn_get_addon_version('lunar'),
        ],
        'redirectUrl' => fn_url("payment_notification.payed?payment=lunar&order_id={$order['order_id']}", AREA, 'current'),
        'preferredPaymentMethod' => 'card',
    ];

    if (!empty($paymentParams['configuration_id'])) {
        $args['mobilePayConfiguration'] = [
            'configurationID' => $paymentParams['configuration_id'],
            'logo' => $paymentParams['logo_url'],
        ];
    }

    if (!! fn_get_cookie('lunar_testmode')) {
        $args['test'] = [
            "card"        => [
                "scheme"  => "supported",
                "code"    => "valid",
                "status"  => "valid",
                "limit"   => [
                    "decimal"  => "25000.99",
                    "currency" => $order['secondary_currency'],
                ],
                "balance" => [
                    "decimal"  => "25000.99",
                    "currency" => $order['secondary_currency'],
                ]
            ],
            "fingerprint" => "success",
            "tds"         => array(
                "fingerprint" => "success",
                "challenge"   => true,
                "status"      => "authenticated"
            ),
        ];
    }

    return $args;
}

/**
 * 
 */
function lunarGetFormattedProducts($order)
{
    $products = [];
    foreach ( $order['products'] as $product ) {
        $products[] = [
            'ID'       => $product['product_code'],
            'name'     => $product['product'],
            'quantity' => $product['amount'],
        ];
    }

    return $products;
}