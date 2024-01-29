<?php

namespace Lunar\Payment;

if (!class_exists('\\Lunar\\Lunar')) {
    require_once(dirname(__FILE__) . '/vendor/autoload.php');
}

use Lunar\Exception\ApiException;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\OrderDataTypes;


class Transaction
{

    /**  */
    public static function create($order_info, $args)
    {
        $api_client = self::buildApiClientUsingOrderPayment($order_info);

        try {
            return $api_client->payments()->create($args);
        } catch (ApiException $e) {
            fn_log_event('general', 'lunar_create_payment_intent', ['errors' => $e->getMessage()]);
    
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('lunar.create_payment_error'));
            
            fn_redirect('checkout.checkout');
        }
    }

    /**  */
    public static function fetch($order_info, $transaction_id)
    {
        $api_client = self::buildApiClientUsingOrderPayment($order_info);

        try {
            return $api_client->payments()->fetch($transaction_id);
        } catch (ApiException $e) {
            fn_log_event('general', 'lunar_fetch', ['errors' => $e->getMessage()]);
            return [];
        }
    }

    /**  */
    public static function capture(&$order_info, $transaction_id)
    {
        $data = [
            'amount' => [
                'currency' => $order_info['secondary_currency'],
                'decimal' => (string) $order_info['total'],
            ]
        ];

        $api_client = self::buildApiClientUsingOrderPayment($order_info);
        
        try {
            $api_response = $api_client->payments()->capture($transaction_id, $data);
        } catch (ApiException $e) {
            fn_log_event('general', 'lunar_capture', ['errors' => $e->getMessage()]);
            return null;
        }

        $update = false;
        $payment_info = [];

        if (isset($api_response['captureState']) && 'completed' === $api_response['captureState']) {
            $payment_info['reason_text'] = __("captured");
            $payment_info['transaction_id'] = $transaction_id;
            $payment_info['lunar.order_time'] = lunar_datetime_to_human($api_response['transaction']['created']);
            $payment_info['lunar.currency_code'] = $api_response['amount']['currency'];
            $payment_info['lunar.authorized_amount'] = $api_response['amount']['decimal'];
            $payment_info['captured_amount'] = $api_response['amount']['decimal'];
            $payment_info['captured'] = 'Y';
            array_filter($payment_info);
            $update = true;
        } else {
            $message = isset($api_response['declinedReason']) ? $api_response['declinedReason']['error'] : '';
            $payment_info['reason_text'] = $message;
            $update = true;
        }

        if ($update) {
            fn_update_order_payment_info($order_info['order_id'], $payment_info);
            $order_info['payment_info'] = self::reloadPaymentInfo($order_info['order_id']);
        }
    }

    /**  */
    public static function refund(&$order_info, $transaction_id, $amount)
    {
        $data = array(
            'amount' => [
                'currency' => $order_info['secondary_currency'],
                'decimal' => (string) $amount,
            ]
        );

        $api_client = self::buildApiClientUsingOrderPayment($order_info);

        try {
            $api_response = $api_client->payments()->refund($transaction_id, $data);
        } catch (ApiException $e) {
            fn_log_event('general', 'lunar_refund', ['errors' => $e->getMessage()]);
            return null;
        }

        if (isset($api_response['refundState']) && 'completed' === $api_response['refundState']) {
            $payment_info['reason_text'] = __("refunded");
            $payment_info['transaction_id'] = $transaction_id;
            $payment_info['lunar.order_time'] = lunar_datetime_to_human($api_response['transaction']['created']);
            $payment_info['lunar.currency_code'] = $api_response['amount']['currency'];
            $payment_info['lunar.authorized_amount'] = $api_response['amount']['decimal'];
            $payment_info['captured_amount'] = $api_response['amount']['decimal'];
            $payment_info['refunded_amount'] = $amount;
            $payment_info['captured'] = 'Y';
            $payment_info['refunded'] = 'Y';
            array_filter($payment_info);
            $update = true;
        } else {
            $message = isset($api_response['declinedReason']) ? $api_response['declinedReason']['error'] : '';
            $payment_info['reason_text'] = $message;
            $update = true;
        }

        if ($update) {
            fn_update_order_payment_info($order_info['order_id'], $payment_info);
            $order_info['payment_info'] = self::reloadPaymentInfo($order_info['order_id']);
        }
    }

    /**  */
    public static function void(&$order_info, $transaction_id)
    {
        $data = array(
            'amount' => [
                'currency' => $order_info['secondary_currency'],
                'decimal' => (string) $order_info['total'],
            ]
        );

        $api_client = self::buildApiClientUsingOrderPayment($order_info);

        try {
            $api_response = $api_client->payments()->cancel($transaction_id, $data);
        } catch (ApiException $e) {
            fn_log_event('general', 'lunar_void', ['errors' => $e->getMessage()]);
            return null;
        }

        if (isset($api_response['cancelState']) && 'completed' === $api_response['cancelState']) {
            $payment_info['reason_text'] = __("voided");
            $payment_info['transaction_id'] = $transaction_id;
            $payment_info['lunar.order_time'] = lunar_datetime_to_human($api_response['transaction']['created']);
            $payment_info['lunar.currency_code'] = $api_response['amount']['currency'];
            $payment_info['lunar.authorized_amount'] = $api_response['amount']['decimal'];
            $payment_info['voided_amount'] = $api_response['transaction']['voidedAmount'];
            $payment_info['captured'] = 'N';
            $payment_info['voided'] = 'Y';
            array_filter($payment_info);
            $update = true;
        } else {
            $message = isset($api_response['declinedReason']) ? $api_response['declinedReason']['error'] : '';
            $payment_info['reason_text'] = $message;
            $update = true;
        }

        if ($update) {
            fn_update_order_payment_info($order_info['order_id'], $payment_info);
            $order_info['payment_info'] = self::reloadPaymentInfo($order_info['order_id']);
        }
    }

    /** */
    private static function reloadPaymentInfo($order_id)
    {
        $paymentInfo = false;
        $additional_data = db_get_hash_single_array("SELECT type,data FROM ?:order_data WHERE order_id = ?i", ['type', 'data'], $order_id);

        if (!empty($additional_data[OrderDataTypes::PAYMENT])) {
            $paymentInfo = unserialize(fn_decrypt_text($additional_data[OrderDataTypes::PAYMENT]));
        }

        return $paymentInfo;
    }

    /** */
    private static function buildApiClientUsingOrderPayment($order_info)
    {
        $lunar_settings = fn_get_processor_data($order_info['payment_id']);
        $app_key = $lunar_settings['processor_params']['app_key'];

        return new \Lunar\Lunar($app_key, null, !!fn_get_cookie('lunar_testmode'));
    }
}
