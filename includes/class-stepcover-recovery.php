<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://steppay.kr
 * @since      1.0.0
 *
 * @package    Stepcover_Recovery
 * @subpackage Stepcover_Recovery/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Stepcover_Recovery
 * @subpackage Stepcover_Recovery/includes
 * @author     StepPay <dev@steppay.kr>
 */
class Stepcover_Recovery {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Stepcover_Recovery_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'STEPCOVER_RECOVERY_VERSION' ) ) {
			$this->version = STEPCOVER_RECOVERY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'stepcover-recovery';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_public_shortcodes();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Stepcover_Recovery_Loader. Orchestrates the hooks of the plugin.
	 * - Stepcover_Recovery_i18n. Defines internationalization functionality.
	 * - Stepcover_Recovery_Admin. Defines all hooks for the admin area.
	 * - Stepcover_Recovery_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-stepcover-recovery-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-stepcover-recovery-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-stepcover-recovery-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-stepcover-recovery-public.php';

		$this->loader = new Stepcover_Recovery_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Stepcover_Recovery_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Stepcover_Recovery_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Stepcover_Recovery_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Stepcover_Recovery_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action( 'steppay_subscription_payment_error', $plugin_public, 'steppay_subscription_payment_error', 10, 5 );
        $this->loader->add_action( 'woocommerce_payment_complete', $plugin_public, 'woocommerce_payment_complete', 10, 1);
        $this->loader->add_action('woocommerce_subscription_status_changed', $plugin_public, 'woocommerce_subscription_status_changed', 10, 4);
        $this->loader->add_action('woocommerce_order_status_cancelled', $plugin_public, 'woocommerce_order_status_cancelled', 10, 1);
        $this->loader->add_action('wp_ajax_stepcover_change_date', $plugin_public, 'changeDate');
        $this->loader->add_action('wp_ajax_nopriv_stepcover_change_date', $plugin_public, 'changeDate');
        $this->loader->add_action('wp_ajax_stepcover_payment_complete', $plugin_public, 'paymentComplete');
        $this->loader->add_action('wp_ajax_nopriv_stepcover_payment_complete', $plugin_public, 'paymentComplete');
        $this->loader->add_action('wp_ajax_stepcover_payment_failed', $plugin_public, 'paymentFailed');
        $this->loader->add_action('wp_ajax_nopriv_stepcover_payment_failed', $plugin_public, 'paymentFailed');
        $this->loader->add_action('wp_ajax_stepcover_change_method', $plugin_public, 'changeMethod');
        $this->loader->add_action('wp_ajax_nopriv_stepcover_change_method', $plugin_public, 'changeMethod');
	}

	private function define_public_shortcodes() {
        add_shortcode('stepcover-recovery', function ($attrs) {
            $this->enqueueAssets('message-listener.js');
            wp_localize_script('stepcover-recovery-assets', 'stepcover', [
                'origin' => get_home_url(),
                'nonce' => wp_create_nonce(),
                'token' => $_REQUEST['token']
            ]);

            $indexUrl = STEPCOVER_RECOVERY_PAGE_URL . '?token=' . $_REQUEST['token'];

            return '<iframe src="' . $indexUrl . '" class="recovery-page"></iframe>';
        });
        add_shortcode('stepcover-recovery-complete', function ($attrs) {
            $this->enqueueAssets('message-listener.js');

            $paymentInfo = $_REQUEST['paymentInfo'];
            $paymentDate = $_REQUEST['paymentDate'];

            wp_localize_script('stepcover-recovery-assets', 'stepcover', [
                'origin' => get_home_url(),
                'nonce' => wp_create_nonce(),
                'payment' => $_REQUEST['payment']
            ]);

            $paidDate = date("Y/m/d H:i:s", strtotime($paymentDate));

            $indexUrl = STEPCOVER_RECOVERY_PAGE_URL . 'recovery-complete/?'
                . 'token=' . $_REQUEST['token']
                . '&paymentDate=' . $paidDate
                . '&paymentInfo=' . $paymentInfo
                . '&useChange=true';

            return '<iframe src="' . $indexUrl . '" class="recovery-page"></iframe>';
        });
        add_shortcode('stepcover-change-date', function ($attrs) {
            $this->enqueueAssets('message-listener.js');

            wp_localize_script('stepcover-recovery-assets', 'stepcover', [
                'origin' => get_home_url(),
                'nonce' => wp_create_nonce(),
                'token' => $_REQUEST['token']
            ]);

            $indexUrl = STEPCOVER_RECOVERY_PAGE_URL . 'change-date?token=' . $_REQUEST['token'];

            return '<iframe src="' . $indexUrl . '" class="recovery-page"></iframe>';
        });
        add_shortcode( 'stepcover-change-date-complete', function ($attrs) {
            $this->enqueueAssets('message-listener.js');
            wp_localize_script('stepcover-recovery-assets', 'stepcover', [
                'origin' => get_home_url(),
                'nonce' => wp_create_nonce()
            ]);

            $subscription = wcs_get_subscription($_REQUEST['subscriptionId']);
            $changedDate = (new DateTime($subscription->get_date('payment_retry')))->add(DateInterval::createFromDateString(get_option('gmt_offset') . ' hours'));;

            $indexUrl = STEPCOVER_RECOVERY_PAGE_URL . 'change-date-complete?date=' . $changedDate->format('Y-m-d')
                . '&token=' . $_REQUEST['token'];

            return '<iframe src="' . $indexUrl . '" class="recovery-page"></iframe>';
        });
        add_shortcode( 'stepcover-delay', function ($attrs) {
            $this->enqueueAssets('message-listener.js');
            wp_localize_script('stepcover-recovery', 'stepcover', [
                'origin' => get_home_url(),
                'nonce' => wp_create_nonce(),
                'token' => $_REQUEST['token']
            ]);

            $indexUrl = STEPCOVER_RECOVERY_PAGE_URL . 'delay?token=' . $_REQUEST['token'] . '&useDelay=true';

            return '<iframe src="' . $indexUrl . '" class="recovery-page"></iframe>';
        });
        add_shortcode( 'stepcover-recover-failed', function ($attrs) {
            $this->enqueueAssets('message-listener.js');
            wp_localize_script('stepcover-recovery', 'stepcover', [
                'origin' => get_home_url(),
                'nonce' => wp_create_nonce(),
                'token' => $_REQUEST['token']
            ]);

            $indexUrl = STEPCOVER_RECOVERY_PAGE_URL . 'payment-failed?token=' . $_REQUEST['token']
                . '&reason=' . $_REQUEST['reason']
                . '&number=' . $_REQUEST['number'];

            return '<iframe src="' . $indexUrl . '" class="recovery-page"></iframe>';
        });
    }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Stepcover_Recovery_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

    function make_product_info( $order ) {
        $product_info = '';

        $items = $order->get_items();

        if ( ! empty( $items ) ) {
            if ( count( $items ) == 1 ) {
                $keys = array_keys( $items );

                $product_info = wc_get_product(current($items)->get_product_id())->get_name();
            } else {
                $keys = array_keys( $items );

                $product_info = sprintf( __( '%s 외 %d건', 'pgall-for-woocommerce' ), wc_get_product(current($items)->get_product_id())->get_name(), count( $items ) - 1 );
            }
        }

        return $product_info;
    }

    function enqueueAssets($jsFilename) {
        wp_enqueue_style("stepcover-recovery-assets", plugin_dir_url(__FILE__) . '../public/css/hide.css');
        wp_enqueue_script('stepcover-recovery-assets', plugin_dir_url(__FILE__) . '../public/js/' . $jsFilename, ['jquery']);
    }

}
