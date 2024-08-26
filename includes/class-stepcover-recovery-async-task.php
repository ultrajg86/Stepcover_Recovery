<?php

class StepCover_Async_Task extends WP_Async_Request
{
    protected $prefix = 'stepcover';
    protected $action = 'async_task';

    protected function handle()
    {
        $action = $_REQUEST['command'];

	error_log('[LOG::action] '.$action);

        switch ($action) {
            case 'payment_error':
                $this->paymentError();
                break;
            case 'payment_complete':
                $this->patchOrder('success', '[결제 완료] ');
                break;
            case 'cancel_subscription':
                $this->cancelSubscription();
                break;
            case 'cancel_order':
                $this->patchOrder('cancel', '[결제 취소] ');
                break;
            case 'change_method':
                $this->changeMethod();
                break;
            case 'active_subscription':
                $this->activeSubscription();
                break;
        }
    }

    private function paymentError()
    {
        $subscriptionId = $_REQUEST['subscriptionId'];
        $orderId = $_REQUEST['orderId'];
        $errorCode = $_REQUEST['errorCode'];
        $errorMessage = $_REQUEST['errorMessage'];
        $paymentInfo = $_REQUEST['paymentInfo'];
        $subscription = wcs_get_subscription($subscriptionId);

        if (!$this->updateCustomerFromOrder($subscription, '[결제 실패 처리] ')) {
            return;
        }
        if (!$this->updateSubscription($subscription, $paymentInfo, '[결제 실패 처리] ')) {
            return;
        }
        if (!$this->updateOrder(wc_get_order($orderId), $paymentInfo, '[결제 실패 처리] ')) {
            return;
        }

        $response = wp_remote_post(STEPCOVER_API_BASE_URL . 'recovery', [
            'body' => json_encode([
                'subscriptionId' => get_post_meta($subscriptionId, 'stepcover_subscription_id', true),
                'orderId' => get_post_meta($orderId, 'stepcover_order_id', true),
                'errorCode' => $errorCode,
                'errorMessage' => $errorMessage
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'Secret-Token' => $this->getSecretKey()
            ],
            'timeout' => 60
        ]);
        $success = intdiv(wp_remote_retrieve_response_code($response), 100) == 2;

        if ($success) {
            $subscription->add_order_note('[결제 실패 처리] 요청 완료.');
            $retries = WCS_Retry_Manager::store()->get_retries([
                'status' => 'pending',
                'order_id' => $orderId
            ]);
            foreach ($retries as $retry) {
                WCS_Retry_Manager::store()->delete_retry($retry->get_id());
            }

            $result = json_decode(wp_remote_retrieve_body($response), true);

            update_post_meta($subscriptionId, 'stepcover_id', $result['coverId']);
            if ($result['retryDate'] != null) {
                $retryDate = new DateTime($result['retryDate']);

                WCS_Retry_Manager::store()->save(new WCS_Retry(array(
                    'order_id' => $orderId,
                    'date_gmt' => $retryDate->format('Y-m-d H:i:s'),
                    'status' => 'pending'
                )));
                $subscription->update_dates(array('payment_retry' => $retryDate->format('Y-m-d H:i:s')));
            }
        } else {
            $subscription->add_order_note('[결제 실패 처리] 요청 실패.');
        }
    }

    private function patchOrder($status, $context = '') {
        $orderId = $_REQUEST['orderId'];
        $order = wc_get_order($orderId);

        $paymentInfo = get_post_meta($orderId, '_steppay_payment_info', true);

        if (!$this->updateCustomerFromOrder($order, $context)) {
            return;
        }
        if ($this->updateOrder($order, $paymentInfo, $context)) {
            if ($status == 'success') {
                $subscriptions = wcs_get_subscriptions_for_order($order);

                if (count($subscriptions) > 0) {
                    foreach ($subscriptions as $subscription) {
                        if (!$this->updateSubscription($subscription, $paymentInfo, $context)) {
                            return;
                        }
                    }
                }
            }
        } else {
            return;
        }
        if (!empty(get_post_meta($orderId, 'stepcover_order_id', true))) {
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, STEPCOVER_API_BASE_URL . 'order/' . $status . '/' . get_post_meta($orderId, 'stepcover_order_id', true) );
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Secret-Token: ' . $this->getSecretKey()
            ]);
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
            $success = intdiv($httpcode, 100) == 2;

            if ($success) {
                $order->add_order_note($context . ' 복구되었습니다.');
            } else {
                $order->add_order_note($context . ' 요청이 실패했습니다.');
            }
        }
    }

    private function cancelSubscription() {
        $subscriptionId = $_REQUEST['subscriptionId'];
        $coverSubscriptionId = get_post_meta($subscriptionId, 'stepcover_subscription_id', true);

        if (!empty($coverSubscriptionId)) {
            $subscription = wcs_get_subscription($subscriptionId);
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, STEPCOVER_API_BASE_URL . 'subscription/' . $coverSubscriptionId . '/cancel');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Secret-Token: ' . $this->getSecretKey()
            ]);
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
            $success = intdiv($httpcode, 100) == 2;

            if ($success) {
                $subscription->add_order_note('[구독 취소] 취소되었습니다.');
            } else {
                $subscription->add_order_note('[구독 취소] 요청이 실패했습니다.');
            }
        }
    }

    private function activeSubscription() {
        $subscriptionId = $_REQUEST['subscriptionId'];
        $coverSubscriptionId = get_post_meta($subscriptionId, 'stepcover_subscription_id', true);

        if (!empty($coverSubscriptionId)) {
            $subscription = wcs_get_subscription($subscriptionId);
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, STEPCOVER_API_BASE_URL . 'subscription/' . $coverSubscriptionId . '/active');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Secret-Token: ' . $this->getSecretKey()
            ]);
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
            $success = intdiv($httpcode, 100) == 2;

            if ($success) {
                $subscription->add_order_note('[구독 활성] 활성화되었습니다.');
            } else {
                $subscription->add_order_note('[구독 활성] 요청이 실패했습니다.');
            }
        }
    }

    private function updateCustomerFromOrder(WC_Order $order, $context = ''): bool
    {
        $response = wp_remote_post(STEPCOVER_API_BASE_URL . 'customer', [
            'body' => json_encode([
                'name' => get_post_meta($order->get_id(), '_billing_last_name', true) . get_post_meta($order->get_id(), '_billing_first_name', true),
                'email' => get_post_meta($order->get_id(), '_billing_email', true),
                'phone' => get_post_meta($order->get_id(), '_billing_phone', true),
                'partnerCustomerId' => $order->get_customer_id(),
                'replaceIfExists' => true
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'Secret-Token' => $this->getSecretKey()
            ],
            'timeout' => 60
        ]);
        $success = intdiv(wp_remote_retrieve_response_code($response), 100) == 2;

        if (!$success) {
            $order->add_order_note($context . '고객 정보를 업데이트 하지 못했습니다.');
        } else if (empty(get_user_meta($order->get_customer_id(), 'stepcover_customer_id', true))) {
            $result = json_decode(wp_remote_retrieve_body($response), true);

            update_user_meta($order->get_customer_id(), 'stepcover_customer_id', $result['id']);
        }

        return $success;
    }

    private function updateSubscription(WC_Subscription $subscription, $paymentInfo, $context = ''): bool
    {
        $customerId = get_user_meta($subscription->get_customer_id(), 'stepcover_customer_id', true);
        $startDate = $subscription->get_date('start');
        $trialEnd = $subscription->get_date('trial_end');
        $lastPayment = $subscription->get_date('last_payment');
        $nextPayment = $subscription->get_date('next_payment');
        $endDate = $subscription->get_date('end');
        /** @var WC_Order $parentOrder */
        $parentOrder = $subscription->get_parent();

        if (empty(get_post_meta($parentOrder->get_id(), 'stepcover_order_id', true))) {
            if (!$this->updateOrder($parentOrder, $paymentInfo)) {
                $subscription->add_order_note($context . '주문 정보를 업데이트 하지 못했습니다.');

                return false;
            }
        }

        $body = [
            'customerId' => $customerId,
            'start' => $this->trimDateString(date('c', strtotime($startDate))),
            'currentPeriodStart' => $this->trimDateString(date('c', strtotime($lastPayment))),
            'initialOrderId' => get_post_meta($parentOrder->get_id(), 'stepcover_order_id', true),
            'partnerSubscriptionId' => $subscription->get_id(),
            'replaceIfExists' => true,
            'recurringInterval' => strtoupper($subscription->get_billing_period()),
            'recurringIntervalCount' => $subscription->get_billing_interval()
        ];
        if (!empty($trialEnd)) {
            $body['trialStart'] = $this->trimDateString(date('c', strtotime($startDate)));
            $body['trialEnd'] = $this->trimDateString(date('c', strtotime($trialEnd)));
        }
        if (empty($nextPayment)) {
            $body['currentPeriodEnd'] = $this->trimDateString(date('c', strtotime($endDate)));
            $body['cancelAtPeriodEnd'] = true;
        } else {
            $body['currentPeriodEnd'] = $this->trimDateString(date('c', strtotime($nextPayment)));
        }
        $response = wp_remote_post(STEPCOVER_API_BASE_URL . 'subscription', [
            'body' => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Secret-Token' => $this->getSecretKey()
            ],
            'timeout' => 60
        ]);
        $success = intdiv(wp_remote_retrieve_response_code($response), 100) == 2;

        if (!$success) {
            $subscription->add_order_note($context . '구독 정보를 업데이트 하지 못했습니다.');
        } else if (empty(get_post_meta($subscription->get_id(), 'stepcover_subscription_id', true))) {
            $result = json_decode(wp_remote_retrieve_body($response), true);

            update_post_meta($subscription->get_id(), 'stepcover_subscription_id', $result['id']);
        }

        return $success;
    }

    private function updateOrder(WC_Order $order, $paymentInfo, $context = ''): bool
    {
        $customerId = get_user_meta($order->get_customer_id(), 'stepcover_customer_id', true);
        $items = [];

        foreach ($order->get_items() as $item) {
            if ($item instanceof WC_Order_Item_Product) {
                $product = $item->get_product();
                if (empty(get_post_meta($product->get_id(), 'stepcover_price_id', true))) {
                    if (!$this->updatePriceFromProduct($product)) {
                        $order->add_order_note($context . '상품/가격 플랜 정보를 업데이트 하지 못했습니다.');

                        return false;
                    }
                }
                $items[] = [
                    'priceId' => get_post_meta($product->get_id(), 'stepcover_price_id', true),
                    'quantity' => $item->get_quantity()
                ];
            }
        }
        $body = [
            'customerId' => $customerId,
            'amount' => $order->get_total(),
            'amountReturned' => 0,
            'items' => $items,
            'replaceIfExists' => true,
            'partnerOrderId' => $order->get_id(),
            'paymentInfo' => $paymentInfo,
            'paymentGateway' => $this->getPaymentGatewayFromPaymentMethodId($order->get_payment_method())
        ];

        if ($order->get_date_paid() != NULL) {
            $body['paymentDate'] = $this->trimDateString(date('c', $order->get_date_paid()->getTimestamp()));
            $body['idKey'] = $order->get_transaction_id();
        }

        $subscriptions = wcs_get_subscriptions_for_renewal_order($order);
        if (count($subscriptions) > 0) {
            $subscription = $subscriptions[array_keys($subscriptions)[0]];
            if (empty(get_post_meta($subscription->get_id(), 'stepcover_subscription_id', true))) {
                if (!$this->updateSubscription($subscription, $paymentInfo, $context)) {
                    return false;
                }
            }
            $body['subscriptionId'] = get_post_meta($subscription->get_id(), 'stepcover_subscription_id', true);
        }

        $response = wp_remote_post(STEPCOVER_API_BASE_URL . 'order', [
            'body' => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Secret-Token' => $this->getSecretKey()
            ],
            'timeout' => 60
        ]);
        $success = intdiv(wp_remote_retrieve_response_code($response), 100) == 2;

        if (!$success) {
            $order->add_order_note($context . '주문 정보를 업데이트 하지 못했습니다.');
        } else if (empty(get_post_meta($order->get_id(), 'stepcover_order_id', true))) {
            $result = json_decode(wp_remote_retrieve_body($response), true);

            update_post_meta($order->get_id(), 'stepcover_order_id', $result['id']);
        }

        return $success;
    }

    private function updatePriceFromProduct(WC_Product $product): bool
    {
        if (empty(get_post_meta($product->get_id(), 'stepcover_product_id', true))) {
            if (!$this->updateProduct($product)) {
                return false;
            }
        }

        $body = [
            'name' => $product->get_name(),
            'partnerPriceId' => $product->get_id(),
            'productId' => get_post_meta($product->get_id(), 'stepcover_product_id', true),
            'price' => $product->get_price(),
            'replaceIfExists' => true
            ];

        if ($product instanceof WC_Product_Subscription || $product instanceof WC_Product_Subscription_Variation) {
            $body['isRecurring'] = true;
            $body['recurringInterval'] = strtoupper(WC_Subscriptions_Product::get_period($product));
            $body['recurringIntervalCount'] = WC_Subscriptions_Product::get_interval($product);
        } else {
            $body['isRecurring'] = false;
        }
        $response = wp_remote_post(STEPCOVER_API_BASE_URL . 'price', [
            'body' => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Secret-Token' => $this->getSecretKey()
            ],
            'timeout' => 60
        ]);
        $success = intdiv(wp_remote_retrieve_response_code($response), 100) == 2;

        if ($success && empty(get_post_meta($product->get_id(), 'stepcover_price_id', true))) {
            $result = json_decode(wp_remote_retrieve_body($response), true);

            update_post_meta($product->get_id(), 'stepcover_price_id', $result['id']);
        }

        return $success;
    }

    private function updateProduct(WC_Product $product)
    {
        $body = [
            'name' => $product->get_name(),
            'featuredImageUrl' => wp_get_attachment_url($product->get_image_id()),
            'partnerProductId' => $product->get_id(),
            'replaceIfExists' => true
        ];
        $response = wp_remote_post(STEPCOVER_API_BASE_URL . 'product', [
            'body' => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Secret-Token' => $this->getSecretKey()
            ],
            'timeout' => 60
        ]);
        $success = intdiv(wp_remote_retrieve_response_code($response), 100) == 2;

        if ($success && empty(get_post_meta($product->get_id(), 'stepcover_product_id', true))) {
            $result = json_decode(wp_remote_retrieve_body($response), true);

            update_post_meta($product->get_id(), 'stepcover_product_id', $result['id']);
        }

        return $success;
    }

    private function changeMethod() {
        $token = $_REQUEST['token'];

        $response = wp_remote_get(STEPCOVER_API_BASE_URL . 'order/' . $token . '/idKey', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Secret-Token' => $this->getSecretKey()
            ],
            'timeout' => 60
        ]);
        $success = intdiv(wp_remote_retrieve_response_code($response), 100) == 2;

        if ($success) {
            $result = json_decode(wp_remote_retrieve_body($response), true);
            $idKey = $result['idKey'];
            $paymentGateway = $result['paymentGateway'];
            $order = wc_get_order($_REQUEST['orderId']);

            if (get_post_meta($order->get_id(), 'stepcover_order_id', true) == $result['orderId']) {
                $subscriptions = wcs_get_subscriptions_for_renewal_order($order);
                if (count($subscriptions) > 0) {
                    $subscription = $subscriptions[array_keys($subscriptions)[0]];
                    update_post_meta($subscription->get_id(), '_stepcover_id_key', $idKey);
                    update_post_meta($subscription->get_id(), '_payment_method', 'steppay_' . strtolower($paymentGateway));
                    $subscription->save();
                }
            }
        }
    }

    private function getSettingValue($key)
    {
        $globalSettings = get_option('stepcover_global');

        return $globalSettings[$key];
    }

    private function getSecretKey()
    {
        return $this->getSettingValue('secret_key');
    }

    private function getPaymentGatewayFromPaymentMethodId($paymentMethodId): string
    {
	    $components = explode('_', $paymentMethodId);
	    if ($components[0] == 'kakaopay') {
            return 'KAKAO';
	    } else if ($components[0] == 'nicepay') {
		    return 'NICE';
	    }
        $components = explode('-', $components[1]);

        return strtoupper($components[0]);
    }

    private function trimDateString($dateString) {
        $pos = strpos($dateString, '+');

        if ($pos) {
            return substr($dateString, 0, $pos);
        }

        return $dateString;
    }
}
