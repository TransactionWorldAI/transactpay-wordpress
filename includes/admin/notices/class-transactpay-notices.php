<?php
/**
 * Class TransactPay_Notices
 *
 * @package    TransactPay
 * @subpackage TransactPay/Notices
 */

defined( 'ABSPATH' ) || exit;

/**
 * TransactPay Main Notice Class
 */
class TransactPay_Notices {
	/**
	 *  Woocommerce_not_installed
	 *
	 * @return void
	 */
	public function woocommerce_not_installed() {
		include_once dirname( TRANSACTION_PAY_MAIN_PLUGIN_FILE ) . '/includes/admin/views/html-admin-missing-woocommerce.php';
	}

	/**
	 *  Woocommerce_wc_not_supported
	 *
	 * @return void
	 */
	public function woocommerce_wc_not_supported() {
		/* translators: $1. Minimum WooCommerce version. $2. Current WooCommerce version. */
		echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'TransactPay requires WooCommerce %1$s or greater to be installed and activated. kindly upgrade to a higher version of WooCommerce. WooCommerce version %2$s is not supported.', 'transactpay' ), esc_attr( TRANSACTPAY_MIN_WC_VER ), esc_attr( WC_VERSION ) ) . '</strong></p></div>';
	}
}
