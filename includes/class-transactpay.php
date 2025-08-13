<?php
/**
 * Main Class of the Plugin.
 *
 * @package    TransactPay
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Main Class
 *
 * @since 1.0.0
 */
class TransactPay {
	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public string $version = '1.0.0';

	/**
	 * Plugin API version.
	 *
	 * @var string
	 */
	public string $api_version = 'v2';

	/**
	 * Plugin Instance.
	 *
	 * @var TransactPay|null
	 */
	public static ?TransactPay $instance = null;

	/**
	 * TransactPay Constructor
	 */
	public function __construct() {
		$this->define_constants();
		$this->load_plugin_textdomain();
		$this->includes();
		$this->init();
	}

	/**
	 * Main Instance.
	 */
	public static function instance(): TransactPay {
		self::$instance = is_null( self::$instance ) ? new self() : self::$instance;

		return self::$instance;
	}

	/**
	 * Define general constants.
	 *
	 * @param string      $name  constant name.
	 * @param string|bool $value constant value.
	 */
	private function define( string $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Define TransactPay Constants.
	 */
	private function define_constants() {
		$this->define( 'TRANSACTPAY_VERSION', $this->version );
		$this->define( 'TRANSACTPAY_MINIMUM_WP_VERSION', '5.8' );
		$this->define( 'TRANSACTPAY_PLUGIN_URL', plugin_dir_url( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) );
		$this->define( 'TRANSACTPAY_PLUGIN_BASENAME', plugin_basename( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) );
		$this->define( 'TRANSACTPAY_PLUGIN_DIR', plugin_dir_path( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) );
		$this->define( 'TRANSACTPAY_DIR_PATH', plugin_dir_path( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) );
		$this->define( 'TRANSACTPAY_MIN_WC_VER', '6.9.1' );
		$this->define( 'TRANSACTPAY_URL', trailingslashit( plugins_url( '/', TRANSACTION_PAY_MAIN_PLUGIN_FILE ) ) );
		$this->define( 'TRANSACTPAY_EPSILON', 0.01 );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		$locale = determine_locale();

		load_plugin_textdomain( 'transactpay', false, dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/languages' );
	}

	/**
	 * Initialize the plugin.
	 * Checks for an existing instance of this class in the global scope and if it doesn't find one, creates it.
	 *
	 * @return void
	 */
	private function init() {
		$notices = new TransactPay_Notices();

		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $notices, 'woocommerce_not_installed' ) );
			return;
		}

		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		if ( version_compare( WC_VERSION, TRANSACTPAY_MIN_WC_VER, '<' ) ) {
			add_action( 'admin_notices', array( $notices, 'woocommerce_wc_not_supported' ) );
			return;
		}

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'admin_head', array( $this, 'transactpay_favicon' ) );
		add_action( 'admin_menu', array( $this, 'add_wc_admin_menu' ) );
		$this->register_transactpay_wc_page_items();
		$this->register_payment_gateway();

		include_once TRANSACTPAY_PLUGIN_DIR . 'includes/api/class-transactpay-settings-rest-controller.php';
		$settings__endpoint = new Transactpay_Settings_Rest_Controller();
		add_action( 'rest_api_init', array( $settings__endpoint, 'register_routes' ) );
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {}

	/**
	 * Register the WooCommerce Settings Page.
	 *
	 * @since 1.0.0
	 */
	public function add_wc_admin_menu() {
		wc_admin_register_page(
			array(
				'id'       => 'transactpay-wc-page',
				'title'    => __( 'Transactpay', 'transactpay' ),
				'path'     => '/transactpay',
				'nav_args' => array(
					'parent'       => 'woocommerce',
					'is_top_level' => true,
					'menuId'       => 'plugins',
				),
				'position' => 3,
				'icon'     => 'dashicons-transactpay',
			)
		);
	}

	/**
	 * Include transactpay Icon for Sidebar Setup.
	 */
	public static function transactpay_favicon() {
		echo '
			<style>
				.dashicons-transactpay {
					background-image: url("' . esc_url( plugins_url( 'assets/img/favicon.ico', TRANSACTION_PAY_MAIN_PLUGIN_FILE ) ) . '");
					background-repeat: no-repeat;
					background-position: center; 
					width: 6px;
					height: 6px;
					background-size: 20px 20px; /* Scale the background image to 20x20px */
				}
			</style>
		';
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		// Include classes that can run on WP Freely.
		include_once dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/includes/admin/notices/class-transactpay-notices.php';
	}

	/**
	 * This handles actions on plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$notices = new TransactPay_Notices();
			add_action( 'admin_notices', array( $notices, 'woocommerce_not_installed' ) );
		}
	}

	/**
	 * This handles actions on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Deactivation logic.
	}

	/**
	 * Handle TransactPay WooCommerce Page Items.
	 */
	public function register_transactpay_wc_page_items() {
		if ( ! method_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu', 'add_plugin_category' ) ||
				! method_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu', 'add_plugin_item' )
			) {
			return;
		}
			\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_category(
				array(
					'id'         => 'transactpay-root',
					'title'      => 'Transactpay',
					'capability' => 'view_woocommerce_reports',
				)
			);
			\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
				array(
					'id'         => 'transactpay-1',
					'parent'     => 'transactpay-root',
					'title'      => 'Transactpay 1',
					'capability' => 'view_woocommerce_reports',
					'url'        => 'https://www.transactworld.com.ng/',
				)
			);
			\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
				array(
					'id'         => 'transactpay-2',
					'parent'     => 'transactpay-root',
					'title'      => 'Transactpay 2',
					'capability' => 'view_woocommerce_reports',
					'url'        => 'https://www.transactworld.com.ng/',
				)
			);
			\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_category(
				array(
					'id'              => 'sub-menu',
					'parent'          => 'transactpay-root',
					'title'           => 'Transactpay Menu',
					'capability'      => 'view_woocommerce_reports',
					'backButtonLabel' => 'Transactpay',
				)
			);
			\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
				array(
					'id'         => 'sub-menu-child-1',
					'parent'     => 'sub-menu',
					'title'      => 'Sub Menu Child 1',
					'capability' => 'view_woocommerce_reports',
					'url'        => 'http//:www.google.com',
				)
			);
			\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
				array(
					'id'         => 'sub-menu-child-2',
					'parent'     => 'sub-menu',
					'title'      => 'Sub Menu Child 2',
					'capability' => 'view_woocommerce_reports',
					'url'        => 'https://www.transactworld.com.ng/',
				)
			);
	}

	/**
	 * Register TransactPay as a Payment Gateway.
	 *
	 * @return void
	 */
	public function register_payment_gateway() {
		require_once dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/includes/class-transactpay-payment-gateway.php';

		add_filter( 'woocommerce_payment_gateways', array( 'TransactPay', 'add_gateway_to_woocommerce_gateway_list' ), 99 );
	}

	/**
	 * Add the Gateway to WooCommerce
	 *
	 * @param  array $methods Existing gateways in WooCommerce.
	 *
	 * @return array Gateway list with our gateway added
	 */
	public static function add_gateway_to_woocommerce_gateway_list( array $methods ): array {

		$methods[] = 'Transactpay_Payment_Gateway';

		return $methods;
	}

	/**
	 * Add the Settings link to the plugin
	 *
	 * @param  array $links Existing links on the plugin page.
	 *
	 * @return array Existing links with our settings link added
	 */
	public static function plugin_action_links( array $links ): array {

		$transactpay_settings_url = esc_url( get_admin_url( null, 'admin.php?page=wc-settings&tab=checkout&section=transactpay' ) );
		array_unshift( $links, "<a title='TransactPay Settings Page' href='$transactpay_settings_url'>Configuration</a>" );

		return $links;
	}
}
