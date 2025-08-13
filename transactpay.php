<?php
/**
 * Plugin Name: TransactPay by TransactWorld Digital
 * Plugin URI: https://transactpay.readme.io/
 * Description: This plugin is the official TransactWorld Digital payment plugin for WooCommerce.
 * Version: 1.0.0
 * Author: TransactWorld Digital Engineers
 * Author URI: https://transactpay.readme.io/docs/getting-started
 * Text Domain: transactpay
 * Domain Path: /languages
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 *
 * WC requires at least: 2.2
 * WC tested up to: 2.3
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 *
 * @package TransactPay
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TRANSACTION_PAY_MAIN_PLUGIN_FILE' ) ) {
	define( 'TRANSACTION_PAY_MAIN_PLUGIN_FILE', __FILE__ );
}

/**
 * Add the Settings link to the plugin
 *
 * @param  array $links Existing links on the plugin page.
 *
 * @return array Existing links with our settings link added
 */
function transactpay_plugin_action_links( array $links ): array {

	$transactpay_settings_url = esc_url( get_admin_url( null, 'admin.php?page=wc-admin&path=%2Ftransactpay' ) );
	array_unshift( $links, "<a title='TransactPay Settings Page' href='$transactpay_settings_url'>Configure</a>" );

	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'transactpay_plugin_action_links' );

/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 */
function transactpay_init() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( ! class_exists( 'Transactpay' ) ) {
		include_once dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/includes/class-transactpay.php';
		$GLOBALS['transactpay'] = TransactPay::instance();
	}
}

add_action( 'plugins_loaded', 'transactpay_init', 99 );

/**
 * Register the admin JS.
 */
function transactpay_add_extension_register_script() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) && version_compare( WC_VERSION, '6.3', '<' ) && ! \Automattic\WooCommerce\Admin\Loader::is_admin_or_embed_page() ) {
		return;
	}

	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) && version_compare( WC_VERSION, '6.3', '>=' ) && ! \Automattic\WooCommerce\Admin\PageController::is_admin_or_embed_page() ) {
		return;
	}

	$script_path       = '/build/settings.js';
	$script_asset_path = dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/build/settings.asset.php';
	$script_asset      = file_exists( $script_asset_path )
		? require_once $script_asset_path
		: array(
			'dependencies' => array(),
			'version'      => TRANSACTPAY_VERSION,
		);

	wp_register_script(
		'transactpay-admin-js',
		plugins_url( 'build/settings.js', TRANSACTION_PAY_MAIN_PLUGIN_FILE ),
		array_merge( array( 'wp-element', 'wp-data', 'moment', 'wp-api' ), $script_asset['dependencies'] ),
		$script_asset['version'],
		true
	);

	$transactpay_fallback_settings = array(
		'enabled'             => 'no',
		'go_live'             => 'no',
		'title'               => 'TransactPay',
		'live_public_key'     => 'PGW-PUBLICKEY-XXXXXXXXXXXXXXXXXXXX',
		'live_secret_key'     => 'PGW-SECRETKEY-XXXXXXXXXXXXXXXXXXXX',
		'live_encryption_key' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXX',
		'test_public_key'     => 'PGW-PUBLICKEY-TEST-XXXXXXXXXXXXXXXXXXXXXXXXXXX',
		'test_secret_key'     => 'PGW-SECRETKEY-TEST-XXXXXXXXXXXXXXXXXXXXXXXXXXX',
		'test_encryption_key' => 'XXXXXXXXXXXXXXXXXXXXXXX==',
		'autocomplete_order'  => 'no',
	);

	if ( empty( get_option( 'woocommerce_transactpay_settings', array() ) ) ) {
		add_option( 'woocommerce_transactpay_settings', $transactpay_fallback_settings );
	}

	$transactpay_default_settings = get_option( 'woocommerce_transactpay_settings', $transactpay_fallback_settings );

	wp_localize_script(
		'transactpay-admin-js',
		'transactpayData',
		array(
			'asset_plugin_url'     => plugins_url( '', TRANSACTION_PAY_MAIN_PLUGIN_FILE ),
			'asset_plugin_dir'     => plugins_url( '', TRANSACTPAY_PLUGIN_DIR ),
			'transactpay_logo'     => plugins_url( 'assets/img/logo.png', TRANSACTION_PAY_MAIN_PLUGIN_FILE ),
			'transactpay_defaults' => $transactpay_default_settings,
			'transactpay_webhook'  => WC()->api_request_url( 'Transactpay_Payment_Webhook' ),
		)
	);

	wp_enqueue_script( 'transactpay-admin-js' );

	wp_register_style(
		'transactpay_admin_css',
		plugins_url( 'assets/admin/style/index.css', TRANSACTION_PAY_MAIN_PLUGIN_FILE ),
		array(),
		TRANSACTPAY_VERSION
	);

	wp_enqueue_style( 'transactpay_admin_css' );
}

add_action( 'admin_enqueue_scripts', 'transactpay_add_extension_register_script' );

/**
 * Register the Transactpay payment gateway for WooCommerce Blocks.
 *
 * @return void
 */
function transactpay_woocommerce_blocks_support() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		require_once dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/includes/block/class-transactpay-block-support.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {

				$payment_method_registry->register( new Transactpay_Block_Support() );
			}
		);
	}
}

// add woocommerce block support.
add_action( 'woocommerce_blocks_loaded', 'transactpay_woocommerce_blocks_support' );

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
