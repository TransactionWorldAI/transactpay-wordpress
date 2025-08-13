<?php
/**
 * The file that defines the class that register transactpay as a payment gateway on the cart and checkout block.
 *
 * A class that defines a block type.
 *
 * @link       https://transactpay.readme.io/
 * @since      1.0.0
 *
 * @package    TransactPay
 * @subpackage TransactPay/Block
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Class Transactpay_Block_Support.
 *
 * @since 2.3.2
 * @extends AbstractPaymentMethodType
 * @package Transactpay
 */
final class Transactpay_Block_Support extends AbstractPaymentMethodType {
	/**
	 * Name of the payment method.
	 *
	 * @var string
	 */
	protected $name = 'transactpay';

	/**
	 * Settings from the WP options table
	 *
	 * @var WC_Payment_Gateway
	 */
	protected $gateway;

	/**
	 * Initialize the Block.
	 *
	 * @inheritDoc
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_transactpay_settings', array() );

		if ( version_compare( WC_VERSION, '6.9.1', '<' ) ) {
			// For backwards compatibility.
			if ( ! class_exists( 'Transactpay_Payment_Gateway' ) ) {
				require_once dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/includes/class-transactpay-payment-gateway.php';
			}

			$this->gateway = new Transactpay_Payment_Gateway();
		} else {
			$gateways      = WC()->payment_gateways->payment_gateways();
			$this->gateway = $gateways[ $this->name ];
		}
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active(): bool {
		if ( version_compare( WC_VERSION, '6.9.0', '>' ) ) {
			$gateways = WC()->payment_gateways->payment_gateways();

			if ( ! isset( $gateways[ $this->name ] ) ) {
				return false;
			}
		}

		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features(): array {
		return $this->gateway->supports;
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles(): array {
		wp_register_script(
			'transactpay',
			'https://payment-web-sdk.transactpay.ai/v1/checkout',
			array(),
			TRANSACTPAY_VERSION,
			true
		);

		$asset_path   = dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/build/index.asset.php';
		$version      = TRANSACTPAY_VERSION;
		$dependencies = array();
		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$version      = is_array( $asset ) && isset( $asset['version'] )
				? $asset['version']
				: $version;
			$dependencies = is_array( $asset ) && isset( $asset['dependencies'] )
				? $asset['dependencies']
				: $dependencies;
		}

		wp_register_script(
			'wc-transactpay-blocks',
			TRANSACTPAY_URL . '/build/index.js',
			array_merge( array( 'transactpay' ), $dependencies ),
			$version,
			true
		);

		wp_enqueue_style(
			'wc-transactpay-blocks',
			TRANSACTPAY_URL. '/assets/css/transactpay.css',
			[],
			TRANSACTPAY_VERSION,
			false
		);

		wp_set_script_translations(
			'wc-transactpay-blocks',
			'transactpay'
		);

		return array(
			'wc-transactpay-blocks',
		);
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data(): array {
		return array(
			'icons'       => $this->get_icons(),
			'supports'    => array_filter( $this->get_supported_features(), array( $this->gateway, 'supports' ) ),
			'isAdmin'     => is_admin(),
			'public_key'  => ( 'yes' === $this->settings['go_live'] ) ? $this->settings['live_public_key'] : $this->settings['test_public_key'],
			'asset_url'   => plugins_url( 'assets', TRANSACTION_PAY_MAIN_PLUGIN_FILE ),
			'title'       => $this->settings['title'],
			'description' => $this->settings['description'] ?? '',
		);
	}

	/**
	 * Returns an array of icons for the payment method.
	 *
	 * @return array
	 */
	private function get_icons(): array {
		$icons_src = array(
			'visa'       => array(
				'src' => dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/assets/img/visa.svg',
				'alt' => __( 'Visa', 'transactpay' ),
			),
			'mastercard' => array(
				'src' => dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/assets/img/mastercard.svg',
				'alt' => __( 'Mastercard', 'transactpay' ),
			),
			'verve'      => array(
				'src' => dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/assets/img/verve.svg',
				'alt' => __( 'Verve', 'transactpay' ),
			),
		);

		if ( 'USD' === get_woocommerce_currency() ) {
			$icons_src['discover'] = array(
				'src' => dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/assets/img/discover.svg',
				'alt' => _x( 'Discover', 'Name of credit card', 'transactpay' ),
			);
			$icons_src['jcb']      = array(
				'src' => dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/assets/img/jcb.svg',
				'alt' => __( 'JCB', 'transactpay' ),
			);
			$icons_src['diners']   = array(
				'src' => dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/assets/img/diners.svg',
				'alt' => __( 'Diners', 'transactpay' ),
			);
		}
		return $icons_src;
	}
}
