<?php
/**
 * Admin Setup Class of the Plugin.
 *
 * @package    Transactpay
 */

declare(strict_types=1);

namespace TransactPay\Admin;

/**
 * TransactPay Setup Class
 */
class Setup {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	/**
	 * Load all necessary dependencies.
	 *
	 * @since 1.0.0
	 */
	public function register_scripts() {
		if ( ! method_exists( 'Automattic\WooCommerce\Admin\PageController', 'is_admin_or_embed_page' ) ||
		! \Automattic\WooCommerce\Admin\PageController::is_admin_or_embed_page()
		) {
			return;
		}

		$script_path       = '/build/index.js';
		$script_asset_path = dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path
		: array(
			'dependencies' => array(),
			'version'      => filemtime( $script_path ),
		);
		$script_url        = plugins_url( $script_path, TRANSACTION_PAY_MAIN_PLUGIN_FILE );

		wp_register_script(
			'my-extension-name',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_register_style(
			'my-extension-name',
			plugins_url( '/build/index.css', TRANSACTION_PAY_MAIN_PLUGIN_FILE ),
			// Add any dependencies styles may have, such as wp-components.
			array(),
			filemtime( dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/build/index.css' )
		);

		wp_enqueue_script( 'my-extension-name' );
		wp_enqueue_style( 'my-extension-name' );
	}

	/**
	 * Register page in wc-admin.
	 *
	 * @since 1.0.0
	 */
	public function register_page() {

		if ( ! function_exists( 'wc_admin_register_page' ) ) {
			return;
		}

		wc_admin_register_page(
			array(
				'id'     => 'my_extension_name-example-page',
				'title'  => __( 'My Extension Name', 'transactpay' ),
				'parent' => 'woocommerce',
				'path'   => '/my-extension-name',
			)
		);
	}
}
