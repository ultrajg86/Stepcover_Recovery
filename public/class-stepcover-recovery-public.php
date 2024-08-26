<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://steppay.kr
 * @since      1.0.0
 *
 * @package    Stepcover_Recovery
 * @subpackage Stepcover_Recovery/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Stepcover_Recovery
 * @subpackage Stepcover_Recovery/public
 * @author     StepPay <dev@steppay.kr>
 */

define('STEPCOVER_TEST_USER_EMAILS', [
	'bhythmmaker@gmail.com',
	'wony@steppay.kr',
	'slee@steppay.kr',
	'tae@steppay.kr',
	'suk@steppay.kr',
	'sehee@steppay.kr',
	'garam0422@kakao.com'
]);

class Stepcover_Recovery_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	
	private $asyncTask;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

        require_once plugin_dir_path(__FILE__) . '../includes/class-stepcover-recovery-async-task.php';

        $this->asyncTask = new StepCover_Async_Task();
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Stepcover_Recovery_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Stepcover_Recovery_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/stepcover-recovery-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Stepcover_Recovery_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Stepcover_Recovery_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/stepcover-recovery-public.js', array( 'jquery' ), $this->version, false );

	}

    public function steppay_subscription_payment_error($subscriptionId, $orderId, $errorCode, $errorMessage, $paymentInfo) {
        $subscription = wcs_get_subscription($subscriptionId);
        $customer = new WP_User($subscription->get_customer_id());
        $email = $customer->user_email;

#        if (in_array($email, STEPCOVER_TEST_USER_EMAILS)) {
            $this->asyncTask->data([
                'command' => 'payment_error',
                'subscriptionId' => $subscriptionId,
                'orderId' => $orderId,
                'errorCode' => $errorCode,
                'errorMessage' => $errorMessage,
                'paymentInfo' => $paymentInfo
            ])->dispatch();
#        }
    }

    public function woocommerce_payment_complete($orderId) {
        $order = wc_get_order($orderId);
        $customer = new WP_User($order->get_customer_id());
        $email = $customer->user_email;

 #       if (in_array($email, STEPCOVER_TEST_USER_EMAILS)) {
            $this->asyncTask->data([
                'command' => 'payment_complete',
                'orderId' => $orderId
            ])->dispatch();
 #       }
    }

    public function woocommerce_subscription_status_changed($subscriptionId, $transitionFrom, $transitionTo, $subscription) {
        $subscription = wcs_get_subscription($subscriptionId);
        $customer = new WP_User($subscription->get_customer_id());
        $email = $customer->user_email;

 #       if (in_array($email, STEPCOVER_TEST_USER_EMAILS)) {
            switch ($transitionTo) {
                case 'cancelled':
                case 'pending-cancel':
                    $this->asyncTask->data([
                        'command' => 'cancel_subscription',
                        'subscriptionId' => $subscriptionId
                    ])->dispatch();
                    break;
                case 'active':
                    $this->asyncTask->data([
                        'command' => 'active_subscription',
                        'subscriptionId' => $subscriptionId
                    ])->dispatch();
                    break;
            }
 #       }
    }

    public function woocommerce_order_status_cancelled($orderId) {
        $order = wc_get_order($orderId);
        $customer = new WP_User($order->get_customer_id());
        $email = $customer->user_email;

 #       if (in_array($email, STEPCOVER_TEST_USER_EMAILS)) {
            $this->asyncTask->data([
                'command' => 'cancel_order',
                'orderId' => $orderId
            ]);
 #       }
    }

    public function changeDate() {
        if (wp_verify_nonce($_POST['nonce'])) {
            $lastDate = (new DateTime())->add(DateInterval::createFromDateString('31 days'));
            $selectedDate = new DateTime($_REQUEST['selectedDate']);

            if ($selectedDate->getTimestamp() < $lastDate->getTimestamp()) {
                $orderId = $_REQUEST['orderId'];
                $subscription = current(wcs_get_subscriptions_for_renewal_order($orderId));
                $createdDate = (new DateTime($_REQUEST['selectedDate']))->add(DateInterval::createFromDateString(-get_option('gmt_offset') . ' hours'));
                $subscription->update_dates(array('payment_retry' => $createdDate->format('Y-m-d H:i:s')));
                $subscription->save();

                wp_redirect(get_home_url() . '/change-date-complete/?'
                    . 'subscriptionId=' . $subscription->get_id()
                    . '&token=' . $_POST['token']
                );
                exit();
            }
        }
    }

    public function paymentComplete() {
        if (wp_verify_nonce($_POST['nonce'])) {
            $paymentInfo = $_POST['paymentInfo'];
            $paymentDate = $_POST['paymentDate'];
            $token = $_POST['token'];
            $idKey = $_POST['idKey'];
            $orderId = $_POST['orderId'];
            $order = wc_get_order($orderId);
            $secret_key = get_option('stepcover_global')['secret_key'];

            $response = wp_remote_get(PAYMENT_SERVER_URL . '/api/payment/receipt/' . $idKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Secret-Token' => $secret_key
                ],
                'timeout' => 60
            ]);
            $result = json_decode(wp_remote_retrieve_body($response));
            if ($order->get_id() == $result->partnerOrderId &&
                $order->get_customer_id() == $result->partnerUserId &&
                $result->paymentStatus == 'COMPLETE' &&
                $order->get_total() == $result->amount - $result->discount) {
                $order->payment_complete($idKey);
                update_post_meta($order->get_id(), '_steppay_payment_info', $result->cardNumber);
                update_post_meta($order->get_id(), '_payment_method', 'steppay_' . strtolower($result->paymentGateway));
                $order->save();

                wp_redirect(get_home_url() . '/recover-complete/?'
                    . 'token=' . $token
                    . '&paymentInfo=' . $paymentInfo
                    . '&paymentDate=' . date('Y-m-d H:i:s', strtotime($paymentDate . ' ' . get_option('gmt_offset') . ' hours'))
                    . '&useChange=true'
                );
                exit();
            }
        }
    }

    public function paymentFailed() {
        if (wp_verify_nonce($_POST['nonce'])) {
            $token = $_POST['token'];
            $reason = $_POST['reason'];

            wp_redirect(get_home_url() . '/recover-failed/?token=' . $token
                . '&reason=' . $reason
            );
            exit();
        }
    }

    public function changeMethod() {
        if (wp_verify_nonce($_POST['nonce'])) {
            $token = $_POST['token'];
            $orderId = $_POST['orderId'];

            $this->asyncTask->data([
                'command' => 'change_method',
                'orderId' => $orderId,
                'token' => $token
            ])->dispatch();
        }
    }

}
