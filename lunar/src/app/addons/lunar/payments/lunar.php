<?php

if (!defined('BOOTSTRAP')) die('Access denied');

if (!class_exists('\\Lunar\\Payment\\Transaction')) {
    require_once(dirname(__DIR__) . '/Transaction.php');
}

use Lunar\Payment\Transaction;

!defined('ORDER_STATUS_FAILED') ? define('ORDER_STATUS_FAILED', 'F') : null;

// BEFORE REDIRECT
if (!defined('PAYMENT_NOTIFICATION')) {

    $args = fn_lunar_get_args($order_info);

    $payment_intent_id = Transaction::create($order_info, $args);

    $remote_url = 'https://pay.lunar.money/?id=';
    if (!!fn_get_cookie('lunar_testmode')) {
        $remote_url = 'https://hosted-checkout-git-develop-lunar-app.vercel.app/?id=';
    }

    fn_redirect($remote_url . $payment_intent_id, true);

// CALLBACK
} elseif (defined('PAYMENT_NOTIFICATION')) {

    $response_data = [];
    $response_data['order_status'] = ORDER_STATUS_FAILED;
    $response_data['reason_text'] = __('text_transaction_declined');
    $order_id = !empty($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;

    if ($mode == 'cancel') {
        $response_data['reason_text'] = __('text_transaction_canceled');

    } elseif ($mode == 'payed') {
        $order_info = fn_get_order_info($order_id);

        if (empty($processor_data)) {
            $processor_data = fn_get_processor_data($order_info['payment_id']);
        }

        $transaction_id = !empty($order_info['payment_info']['transaction_id']) ? $order_info['payment_info']['transaction_id'] : '';

        $fetch = Transaction::fetch($order_info, $transaction_id);

        if (empty($fetch['authorisationCreated']) || !$order_info) {
            fn_lunar_finalize_order($order_id, $response_data);
        }

        if ($processor_data['processor_params']['checkout_mode'] == 'delayed') {
            $response_data['order_status'] = $processor_data['processor_params']['delayed_status'];
            $response_data['reason_text'] = __("delayed");
            $response_data['transaction_id'] = $transaction_id;
            $response_data['lunar.order_time'] = lunar_datetime_to_human(time());
            $response_data['lunar.currency_code'] = $fetch['amount']['currency'];
            $response_data['authorized_amount'] = $fetch['amount']['decimal'];
            $response_data['captured'] = 'N';
            array_filter($response_data);

        } else {
            $response_data = Transaction::capture($order_info, $transaction_id);
            $response_data['order_status'] = $processor_data['processor_params']['capture_status'];
        }
    }
    
    if (fn_check_payment_script('lunar.php', $order_id)) {
        fn_lunar_finalize_order($order_id, $response_data);
    }
}


/**
 * 
 */
function fn_lunar_finalize_order($order_id, $response_data)
{
    fn_finish_payment($order_id, $response_data);
    fn_order_placement_routines('route', $order_id);
}


/**
 * 
 */
function fn_lunar_get_args($order_info)
{
    $order = $order_info;
    $payment_params = $order_info['payment_method']['processor_params'];

    $customer = fn_lunar_get_customer_data($order);

    $args = [
        'integration' => [
            'key' => $payment_params['public_key'],
            'name' => $payment_params['shop_title'],
            'logo' => $payment_params['logo_url'],
        ],
        'amount'     => [
            'currency' => $order['secondary_currency'], // primary currency means base store currency, secondary - used one 
            'decimal' => $order['total'],
        ],
        'custom' => [
            'orderId'    => $order['order_id'],
            'products'   => fn_lunar_get_formatted_products($order),
            'customer'   => [
                'name'    => $customer['firstname'] . ' ' . $customer['lastname'],
                'address' => $customer['address'] . ', ' . $customer['city'] . ', ' . $customer['state'] . ', ' 
                                . $customer['zip'] . ', ' . $customer['country'],
                'email'   => $order['email'],
                'phoneNo' => $order['phone'],
                'ip'      => $order['ip_address'],
            ],
            'platform' => [
                'name' => PRODUCT_NAME,
                'version' => PRODUCT_VERSION,
            ],
            'lunarPluginVersion' => fn_get_addon_version('lunar'),
        ],
        'redirectUrl' => fn_url("payment_notification.payed?payment=lunar&order_id={$order['order_id']}", AREA, 'current'),
        'preferredPaymentMethod' => $payment_params['payment_method'],
    ];

    if (!empty($payment_params['configuration_id'])) {
        $args['mobilePayConfiguration'] = [
            'configurationID' => $payment_params['configuration_id'],
            'logo' => $payment_params['logo_url'],
        ];
    }

    if (!! fn_get_cookie('lunar_testmode')) {
        $args['test'] = fn_lunar_get_test_object($order);
    }

    return $args;
}

/**
 * 
 */
function fn_lunar_get_customer_data($order)
{
    return [
        'firstname' => !empty($order['b_firstname'])   ?   $order['b_firstname']  :  (!empty($order['s_firstname']) ? $order['s_firstname'] : ''),
        'lastname' => !empty($order['b_lastname'])   ?   $order['b_lastname']  :  (!empty($order['s_lastname']) ? $order['s_lastname'] : ''),
        'address' => !empty($order['b_address'])   ?   $order['b_address']  :  (!empty($order['s_address']) ? $order['s_address'] : ''),
        'city' => !empty($order['b_city'])   ?   $order['b_city']  :  (!empty($order['s_city']) ? $order['s_city'] : ''),
        'state' => !empty($order['b_state'])   ?   $order['b_state']  :  (!empty($order['s_state']) ? $order['s_state'] : ''),
        'zip' => !empty($order['b_zipcode'])   ?   $order['b_zipcode']  :  (!empty($order['s_zipcode']) ? $order['s_zipcode'] : ''),
        'country' => !empty($order['b_country'])   ?   $order['b_country']  :  (!empty($order['s_country']) ? $order['s_country'] : ''),
    ];
}

/**
 * 
 */
function fn_lunar_get_formatted_products($order)
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

/**
 * 
 */
function fn_lunar_get_test_object($order)
{
    return [
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