<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://transactpay.readme.io/
 * @since      1.0.0
 *
 * @package    TransactPay
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/util/class-transactpay-logger.php';

/**
 * TransactPay x WooCommerce Integration Class.
 */
class Transactpay_Payment_Gateway extends WC_Payment_Gateway {
	/**
	 * Public Key
	 *
	 * @var string the public key
	 */
	protected string $public_key;
	/**
	 * Secret Key
	 *
	 * @var string the secret key.
	 */
	protected string $secret_key;

	/**
	 * Encryption Key
	 *
	 * @var string the secret key.
	 */
	protected string $encryption_key;

	/**
	 * Test Public Key
	 *
	 * @var string the test public key.
	 */
	private string $test_public_key;
	/**
	 * Test Secret Key.
	 *
	 * @var string the test secret key.
	 */
	private string $test_secret_key;

	/**
	 * Encryption Key
	 *
	 * @var string the secret key.
	 */
	protected string $test_encryption_key;

	/**
	 * Live Public Key
	 *
	 * @var string the live public key
	 */
	private string $live_public_key;
	/**
	 * Go Live Status.
	 *
	 * @var string the go live status.
	 */
	private string $go_live;
	/**
	 * Live Secret Key.
	 *
	 * @var string the live secret key.
	 */
	private string $live_secret_key;

	/**
	 * Live Encryption Key.
	 *
	 * @var string the live secret key.
	 */
	private string $live_encryption_key;

	/**
	 * Auto Complete Order.
	 *
	 * @var false|mixed|null
	 */
	private $auto_complete_order;
	/**
	 * Logger
	 *
	 * @var WC_Logger the logger.
	 */
	private Transactpay_Logger $logger;

	/**
	 * Base Url
	 *
	 * @var string the base url
	 */
	private string $base_url;

	/**
	 * Payment Style
	 *
	 * @var string the payment style
	 */
	private string $payment_style;

	/**
	 * Country
	 *
	 * @var string the country
	 */
	private string $country;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->base_url           = 'https://payment-api-service.transactpay.ai/payment/order/';
		$this->id                 = 'transactpay';
		$this->icon               = plugins_url( 'assets/img/logo.png', TRANSACTION_PAY_MAIN_PLUGIN_FILE );
		$this->has_fields         = false;
		$this->method_title       = 'Transactpay';
		$this->method_description = 'Transactpay ' . __( 'payment made easy ', 'transactpay' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title               = $this->get_option( 'title' );
		$this->description         = $this->get_option( 'description' );
		$this->enabled             = $this->get_option( 'enabled' );
		$this->test_public_key     = $this->get_option( 'test_public_key' );
		$this->test_secret_key     = $this->get_option( 'test_secret_key' );
		$this->test_encryption_key = $this->get_option( 'test_encryption_key' );
		$this->live_public_key     = $this->get_option( 'live_public_key' );
		$this->live_secret_key     = $this->get_option( 'live_secret_key' );
		$this->live_encryption_key = $this->get_option( 'live_encryption_key' );
		$this->auto_complete_order = $this->get_option( 'autocomplete_order' );
		$this->go_live             = $this->get_option( 'go_live' );
		$this->payment_style       = $this->get_option( 'payment_style' );
		$this->country             = '';
		$this->supports            = array(
			'products',
		);

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_api_wc_transactpay_payment_gateway', array( $this, 'transactpay_verify_payment' ) );

		// Webhook listener/API hook.
		add_action( 'woocommerce_api_transactpay_payment_webhook', array( $this, 'transactpay_notification_handler' ) );

		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		$this->public_key     = $this->test_public_key;
		$this->secret_key     = $this->test_secret_key;
		$this->encryption_key = $this->test_encryption_key;

		if ( 'yes' === $this->go_live ) {
			$this->public_key     = $this->live_public_key;
			$this->secret_key     = $this->live_secret_key;
			$this->encryption_key = $this->live_encryption_key;
		}
		$this->logger = Transactpay_Logger::instance();

		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
	}

	/**
	 * WooCommerce admin settings override.
	 */
	public function admin_options() {
		?>
		<img class="transactpay__heading" src="https://merchant.transactpay.ai/static/media/paymentgateway.14c63c11.png" alt="transactpay logo" width="250px" />
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label><?php esc_attr_e( 'Webhook Instruction', 'transactpay' ); ?></label>
				</th>
				<td class="forminp forminp-text">
					<p class="description">
						<?php esc_attr_e( 'Please add this webhook URL and paste on the webhook section on your dashboard', 'transactpay' ); ?><strong style="color: #8E173F"><pre><code><?php echo esc_url( WC()->api_request_url( 'Transactpay_Payment_Webhook' ) ); ?></code></pre></strong><a href="https://merchant.transactpay.ai/dashboard/settings/api-webhooks" target="_blank">Merchant Account</a>
					</p>
				</td>
			</tr>
			<?php
				$this->generate_settings_html();
			?>
		</table>
		<?php
	}

	/**
	 * Initial gateway settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$this->form_fields = array(

			'enabled'             => array(
				'title'       => __( 'Enable/Disable', 'transactpay' ),
				'label'       => __( 'Enable Transactpay', 'transactpay' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable Transactpay as a payment option on the checkout page', 'transactpay' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'title'               => array(
				'title'       => __( 'Payment method title', 'transactpay' ),
				'type'        => 'text',
				'description' => __( 'Optional', 'transactpay' ),
				'default'     => 'Transactpay',
			),
			'description'         => array(
				'title'       => __( 'Payment method description', 'transactpay' ),
				'type'        => 'text',
				'description' => __( 'Optional', 'transactpay' ),
				'default'     => 'Powered by Transactpay: Accepts Mastercard, Visa, Verve.',
			),
			'test_public_key'     => array(
				'title'       => __( 'Test Public Key', 'transactpay' ),
				'type'        => 'text',
				'description' => __( 'Required! Enter your Transactpay test public key here', 'transactpay' ),
				'default'     => '',
			),
			'test_secret_key'     => array(
				'title'       => __( 'Test Secret Key', 'transactpay' ),
				'type'        => 'password',
				'description' => __( 'Required! Enter your Transactpay test secret key here', 'transactpay' ),
				'default'     => '',
			),
			'test_encryption_key' => array(
				'title'       => __( 'Test Encryption Key', 'transactpay' ),
				'type'        => 'password',
				'description' => __( 'Required! Enter your Transactpay test encryption key here', 'transactpay' ),
				'default'     => '',
			),
			'live_public_key'     => array(
				'title'       => __( 'Live Public Key', 'transactpay' ),
				'type'        => 'text',
				'description' => __( 'Required! Enter your Transactpay live public key here', 'transactpay' ),
				'default'     => '',
			),
			'live_secret_key'     => array(
				'title'       => __( 'Live Secret Key', 'transactpay' ),
				'type'        => 'password',
				'description' => __( 'Required! Enter your Transactpay live secret key here', 'transactpay' ),
				'default'     => '',
			),
			'live_encryption_key' => array(
				'title'       => __( 'Live Encryption Key', 'transactpay' ),
				'type'        => 'password',
				'description' => __( 'Required! Enter your Transactpay Live encryption key here', 'transactpay' ),
				'default'     => '',
			),
			'autocomplete_order'  => array(
				'title'       => __( 'Autocomplete Order After Payment', 'transactpay' ),
				'label'       => __( 'Autocomplete Order', 'transactpay' ),
				'type'        => 'checkbox',
				'class'       => 'transactpay-autocomplete-order',
				'description' => __( 'If enabled, the order will be marked as complete after successful payment', 'transactpay' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'payment_style'       => array(
				'title'       => __( 'Payment Style on checkout', 'transactpay' ),
				'type'        => 'select',
				'description' => __( 'Optional - Choice of payment style to use. Either inline or redirect. (Default: inline)', 'transactpay' ),
				'options'     => array(
					'inline' => esc_html_x( 'Pop-Up (Keep the customer on your site)', 'payment_style', 'transactpay' ),
				),
				'default'     => 'inline',
			),
			'go_live'             => array(
				'title'       => __( 'Mode', 'transactpay' ),
				'label'       => __( 'Live mode', 'transactpay' ),
				'type'        => 'checkbox',
				'description' => __( 'Check this box if you\'re using your live keys and are ready to start collecting actual payments.', 'transactpay' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Order id
	 *
	 * @param int $order_id  Order id.
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id ) {

		// For inline Checkout.
		$order = wc_get_order( $order_id );

		$custom_nonce = wp_create_nonce();
		$this->logger->info( 'Rendering Payment Modal' );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ) . "&_wpnonce=$custom_nonce",
		);
	}

	/**
	 * Get Secret Key
	 *
	 * @return string
	 */
	public function get_secret_key(): string {
		return $this->secret_key;
	}

	/**
	 * Handles admin notices
	 *
	 * @return void
	 */
	public function admin_notices(): void {

		if ( 'yes' === $this->enabled ) {

			if ( empty( $this->public_key ) || empty( $this->secret_key ) ) {

				$message = sprintf(
				/* translators: %s: url */
					__( 'For TransactPay on appear on checkout. Please <a href="%s">set your TransactPay API keys</a> to be able to accept payments.', 'transactpay' ),
					esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=transactpay' ) )
				);
			}
		}
	}

	/**
	 * Checkout receipt page
	 *
	 * @param int $order_id Order id.
	 *
	 * @return void
	 */
	public function receipt_page( int $order_id ) {
		$order = wc_get_order( $order_id );
	}

	/**
	 * Loads (enqueue) static files (js & css) for the checkout page
	 *
	 * @return void
	 */
	public function payment_scripts() {

		// Load only on checkout page.
		if ( ! is_checkout_pay_page() && ! isset( $_GET['key'] ) ) {
			return;
		}

		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		$expiry_message = sprintf(
			/* translators: %s: shop cart url */
			__( 'Sorry, your session has expired. <a href="%s" class="wc-backward">Return to shop</a>', 'transactpay' ),
			esc_url( wc_get_page_permalink( 'shop' ) )
		);

			$nonce_value = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );

			$order_key = urldecode( sanitize_text_field( wp_unslash( $_GET['key'] ) ) );
			$order_id  = absint( get_query_var( 'order-pay' ) );

			$order = wc_get_order( $order_id );

		if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value ) ) {

			WC()->session->set( 'refresh_totals', true );
			wc_add_notice( __( 'We were unable to process your order, please try again.', 'transactpay' ) );
			wp_safe_redirect( $order->get_cancel_order_url() );
			return;
		}

		if ( $this->id !== $order->get_payment_method() ) {
			return;
		}

		wp_enqueue_script( 'jquery' );

		$transactpay_inline_link = 'https://payment-web-sdk.transactpay.ai/v1/checkout';

		wp_enqueue_script( 'transactpay', $transactpay_inline_link, array( 'jquery' ), TRANSACTPAY_VERSION, false );

		$checkout_frontend_script = 'assets/js/checkout.js';
		if ( 'yes' === $this->go_live ) {
			$checkout_frontend_script = 'assets/js/checkout.min.js';
		}

		wp_enqueue_script( 'transactpay_js', plugins_url( $checkout_frontend_script, TRANSACTION_PAY_MAIN_PLUGIN_FILE ), array( 'jquery', 'transactpay' ), TRANSACTPAY_VERSION, false );

		$payment_args = array();

		if ( is_checkout_pay_page() && get_query_var( 'order-pay' ) ) {
			$email         = $order->get_billing_email();
			$amount        = $order->get_total();
			$txnref        = 'WOO_' . $order_id . '_' . time();
			$the_order_id  = $order->get_id();
			$the_order_key = $order->get_order_key();
			$currency      = $order->get_currency();
			$custom_nonce  = wp_create_nonce();
			$redirect_url  = WC()->api_request_url( 'WC_Transactpay_Payment_Gateway' );

			// Parse the base URL to check for existing query parameters.
			$url_parts = wp_parse_url( $redirect_url );

			// If the base URL already has query parameters, merge them with new ones.
			if ( isset( $url_parts['query'] ) ) {
				// Convert the query string to an array.
				parse_str( $url_parts['query'], $query_array );

				// Add the new parameters to the existing query array.
				$query_array['order_id'] = $order_id;
				$query_array['_wpnonce'] = $custom_nonce;

				// Rebuild the query string with the new parameters.
				$new_query_string = http_build_query( $query_array );

				// Rebuild the final URL with the new query string.
				$redirect_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '?' . $new_query_string;
			} else {
				// If no existing query parameters, simply append the new ones.
				$redirect_url = add_query_arg(
					array(
						'order_id' => $order_id,
						'_wpnonce' => $custom_nonce,
					),
					$redirect_url
				);
			}

			if ( $the_order_id === $order_id && $the_order_key === $order_key ) {

				$payment_args['email']        = $email;
				$payment_args['amount']       = $amount;
				$payment_args['reference']    = $txnref;
				$payment_args['currency']     = $currency;
				$payment_args['public_key']   = $this->public_key;
				$payment_args['redirect_url'] = $redirect_url;
				$payment_args['country']      = $order->get_billing_country();
				$payment_args['phone_number'] = $order->get_billing_phone();
				$payment_args['first_name']   = $order->get_billing_first_name();
				$payment_args['last_name']    = $order->get_billing_last_name();
				$payment_args['consumer_id']  = $order->get_customer_id();
				$payment_args['ip_address']   = $order->get_customer_ip_address();
				$payment_args['title']        = esc_html__( 'Checkout Payment', 'transactpay' );
				$payment_args['description']  = 'Payment for Order: ' . $order_id;
				$payment_args['logo']         = wp_get_attachment_url( get_theme_mod( 'custom_logo' ) );
				$payment_args['checkout_url'] = wc_get_checkout_url();
				$payment_args['cancel_url']   = $order->get_cancel_order_url();
				$payment_args['encrypt_key']  = $this->encryption_key;
			}
			update_post_meta( $order_id, '_transactpay_txn_ref', $txnref );
		}

		wp_localize_script( 'transactpay_js', 'transactpay_args', $payment_args );
	}

	/**
	 * Check Amount Equals.
	 *
	 * Checks to see whether the given amounts are equal using a proper floating
	 * point comparison with an Epsilon which ensures that insignificant decimal
	 * places are ignored in the comparison.
	 *
	 * eg. 100.00 is equal to 100.0001
	 *
	 * @param Float $amount1 1st amount for comparison.
	 * @param Float $amount2  2nd amount for comparison.
	 * @since 1.0.0
	 * @return bool
	 */
	public function amounts_equal( $amount1, $amount2 ): bool {
		return ! ( abs( floatval( $amount1 ) - floatval( $amount2 ) ) > TRANSACTPAY_EPSILON );
	}

	/**
	 * Verify payment made on the checkout page.
	 *
	 * @return void
	 */
	public function transactpay_verify_payment() {
		$public_key = $this->public_key;
		$secret_key = $this->secret_key;
		$logger     = $this->logger;

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) ) ) {
			if ( isset( $_GET['order_id'] ) ) {
				// Handle expired Session.
				$order_id = urldecode( sanitize_text_field( wp_unslash( $_GET['order_id'] ) ) ) ?? sanitize_text_field( wp_unslash( $_GET['order_id'] ) );
				$order_id = intval( $order_id );
				$order    = wc_get_order( $order_id );

				if ( $order instanceof WC_Order ) {
					WC()->session->set( 'refresh_totals', true );
					wc_add_notice( __( 'We were unable to process your order, please try again.', 'transactpay' ) );
					$admin_note  = esc_html__( 'Attention: Customer session expired. ', 'transactpay' ) . '<br>';
					$admin_note .= esc_html__( 'Customer should try again. order has status is now pending payment.', 'transactpay' );
					$order->add_order_note( $admin_note );
					wp_safe_redirect( $order->get_cancel_order_url() );
				}
				die();
			}
		}

		if ( isset( $_POST['reference'] ) || isset( $_GET['reference'] ) ) {
			$txn_ref  = urldecode( sanitize_text_field( wp_unslash( $_GET['reference'] ) ) ) ?? sanitize_text_field( wp_unslash( $_POST['reference'] ) );
			$o        = explode( '_', sanitize_text_field( $txn_ref ) );
			$order_id = intval( $o[1] );
			$order    = wc_get_order( $order_id );

			// Communicate with Transactpay to confirm payment.
			$max_attempts = 3;
			$attempt      = 0;
			$success      = false;

			while ( $attempt < $max_attempts && ! $success ) {
				$args = array(
					'method'    => 'POST',
					'headers'   => array(
						'Content-Type' => 'application/json',
						'api-key'      => $secret_key,
					),
					'body'      =>
						wp_json_encode( array( 'reference' => $txn_ref ), JSON_UNESCAPED_SLASHES ),
					'sslverify' => false,
				);

				$order->add_order_note( esc_html__( 'verifying the Payment of Transactpay...', 'transactpay' ) );

				$response = wp_safe_remote_request( $this->base_url . 'verify', $args );

				if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
					// Request successful.
					$current_response                       = \json_decode( $response['body'] );
					$is_cancelled_or_pending_on_transactpay = in_array( $current_response->data->status, array( 'cancelled', 'pending' ), true );
					if ( isset( $_GET['status'] ) && 'cancelled' === $_GET['status'] && $is_cancelled_or_pending_on_transactpay ) {
						if ( $order instanceof WC_Order ) {
							$order->add_order_note( esc_html__( 'The customer clicked on the cancel button on Checkout.', 'transactpay' ) );
							$order->update_status( 'cancelled' );
							$admin_note  = esc_html__( 'Attention: Customer clicked on the cancel button on the payment gateway. We have updated the order to cancelled status. ', 'transactpay' ) . '<br>';
							$admin_note .= esc_html__( 'Please, confirm from the order notes that there is no note of a successful transaction. If there is, this means that the user was debited and you either have to give value for the transaction or refund the customer.', 'transactpay' );
							$order->add_order_note( $admin_note );
						}
						header( 'Location: ' . wc_get_cart_url() );
						die();
					} else {
						$success = true;
					}
				} else {
					// Retry.
					++$attempt;
					usleep( 1000000 ); // Wait for 1 seconds before retrying (adjust as needed).
				}
			}

			if ( ! $success ) {
				// Get the transaction from your DB using the transaction reference (txref)
				// Queue it for requery. Preferably using a queue system. The requery should be about 15 minutes after.
				// Ask the customer to contact your support and you should escalate this issue to the TransactPay support team. Send this as an email and as a notification on the page. just incase the page timesout or disconnects.
				$order->add_order_note( esc_html__( 'The payment didn\'t return a valid response. It could have timed out or abandoned by the customer on TransactPay', 'transactpay' ) );
				$order->update_status( 'on-hold' );
				$customer_note  = 'Thank you for your order.<br>';
				$customer_note .= 'We had an issue confirming your payment, but we have put your order <strong>on-hold</strong>. ';
				$customer_note .= esc_html__( 'Please, contact us for information regarding this order.', 'transactpay' );
				$admin_note     = esc_html__( 'Attention: New order has been placed on hold because we could not get a definite response from the payment gateway. Kindly contact the TransactPay support team at support@Transactpay.com  to confirm the payment.', 'transactpay' ) . ' <br>';
				$admin_note    .= esc_html__( 'Payment Reference: ', 'transactpay' ) . $txn_ref;

				$order->add_order_note( $customer_note, 1 );
				$order->add_order_note( $admin_note );

				wc_add_notice( $customer_note, 'notice' );
				$this->logger->error( 'Failed to verify transaction ' . $txn_ref . ' after multiple attempts.' );
			} else {
				// Transaction verified successfully.
				// Proceed with setting the payment on hold.
				$response = json_decode( $response['body'] );
				$this->logger->info( wp_json_encode( $response ) );
				if ( 'Successful' === $response->data->status ) {
					$amount = (float) $response->data->totalAmountCharged - (float) $response->data->fee;
					if ( $response->data->currencyName !== $order->get_currency() || ! $this->amounts_equal( $amount, $order->get_total() ) ) {
						$order->update_status( 'on-hold' );
						$customer_note  = 'Thank you for your order.<br>';
						$customer_note .= 'Your payment successfully went through, but we have to put your order <strong>on-hold</strong> ';
						$customer_note .= 'because the we couldn\t verify your order. Please, contact us for information regarding this order.';
						$admin_note     = esc_html__( 'Attention: New order has been placed on hold because of incorrect payment amount or currency. Please, look into it.', 'transactpay' ) . '<br>';
						$admin_note    .= esc_html__( 'Amount paid: ', 'transactpay' ) . $response->data->currencyName . ' ' . $amount . ' <br>' . esc_html__( 'Order amount: ', 'transactpay' ) . $order->get_currency() . ' ' . $order->get_total() . ' <br>' . esc_html__( ' Reference: ', 'transactpay' ) . $response->data->paymentReference;
						$order->add_order_note( $customer_note, 1 );
						$order->add_order_note( $admin_note );
					} else {
						$order->payment_complete( $order->get_id() );
						if ( 'yes' === $this->auto_complete_order ) {
							$order->update_status( 'completed' );
						}
						$order->add_order_note( 'Payment was successful on TransactPay' );
						$order->add_order_note( 'TransactPay  reference: ' . $txn_ref );

						$customer_note  = 'Thank you for your order.<br>';
						$customer_note .= 'Your payment was successful, we are now <strong>processing</strong> your order.';
						$order->add_order_note( $customer_note, 1 );
					}
				}
			}
			wc_add_notice( $customer_note, 'notice' );
			WC()->cart->empty_cart();

			$redirect_url = $this->get_return_url( $order );
			header( 'Location: ' . $redirect_url );
			die();
		}

		wp_safe_redirect( home_url() );
		die();
	}

	/**
	 * Process Webhook notifications.
	 */
	public function transactpay_notification_handler() {
		$public_key = $this->public_key;
		$secret_key = $this->secret_key;
		$logger     = $this->logger;
		$sdk        = $this->sdk;

		$event = file_get_contents( 'php://input' );

		http_response_code( 200 );
		$event = json_decode( $event );

		if ( empty( $event->notify ) && empty( $event->data ) ) {
			$this->logger->info( 'Webhook: ' . wp_json_encode( $event->data ) );
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => 'Webhook sent is deformed. missing data object.',
				),
				WP_Http::NO_CONTENT
			);
		}

		if ( 'test_assess' === $event->notify ) {
			wp_send_json(
				array(
					'status'  => 'success',
					'message' => 'Webhook Test Successful. handler is accessible',
				),
				WP_Http::OK
			);
		}

		if ( 'transaction' === $event->notify ) {
			sleep( 6 );

			$event_type = $event['notifyType'];
			$event_data = $event->data;

			// check if transaction reference starts with WOO on hpos enabled.
			if ( substr( $event_data->reference, 0, 4 ) !== 'WOO' ) {
				wp_send_json(
					array(
						'status'  => 'failed',
						'message' => 'The transaction reference ' . $event_data->tx_ref . ' is not a Transactpay WooCommerce Generated transaction',
					),
					WP_Http::OK
				);
			}

			$txn_ref  = sanitize_text_field( $event_data->tx_ref );
			$o        = explode( '_', $txn_ref );
			$order_id = intval( $o[1] );
			$order    = wc_get_order( $order_id );
			// get order status.
			$current_order_status = $order->get_status();

			/**
			 * Fires after the webhook has been processed.
			 *
			 * @param string $event The webhook event.
			 * @since 1.0.0
			 */
			do_action( 'transactpay_webhook_after_action', wp_json_encode( $event, true ) );
			// TODO: Handle Checkout draft status for WooCommerce Blocks users.
			$statuses_in_question = array( 'pending', 'on-hold' );
			if ( 'failed' === $current_order_status ) {
				// NOTE: customer must have tried to make payment again in the same session.
				// TODO: add timeline to order notes to brief merchant as to why the order status changed.
				$statuses_in_question[] = 'failed';
			}
			if ( ! in_array( $current_order_status, $statuses_in_question, true ) ) {
				wp_send_json(
					array(
						'status'  => 'error',
						'message' => 'Order already processed',
					),
					WP_Http::CREATED
				);
			}

			// Verify transaction and give value.
			// Communicate with Transactpay to confirm payment.
			$max_attempts = 3;
			$attempt      = 0;
			$success      = false;

			while ( $attempt < $max_attempts && ! $success ) {
				$args = array(
					'method'  => 'GET',
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $secret_key,
					),
				);

				$order->add_order_note( esc_html__( 'verifying the Payment of Transactpay...', 'transactpay' ) );

				$response = wp_safe_remote_request( $this->base_url . 'transaction/verify/:' . $txn_ref, $args );

				if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
					// Request successful.
					$success = true;
				} else {
					// Retry.
					++$attempt;
					usleep( 2000000 ); // Wait for 2 seconds before retrying (adjust as needed).
				}
			}

			if ( ! $success ) {
				// Get the transaction from your DB using the transaction reference (txref)
				// Queue it for requery. Preferably using a queue system. The requery should be about 15 minutes after.
				// Ask the customer to contact your support and you should escalate this issue to the TransactPay support team. Send this as an email and as a notification on the page. just incase the page timesout or disconnects.
				$order->add_order_note( esc_html__( 'The payment didn\'t return a valid response. It could have timed out or abandoned by the customer on Transactpay', 'transactpay' ) );
				$order->update_status( 'on-hold' );
				$admin_note  = esc_html__( 'Attention: New order has been placed on hold because we could not get a definite response from the payment gateway. Kindly contact the Transactpay support team at support@Transactpay.com to confirm the payment.', 'transactpay' ) . ' <br>';
				$admin_note .= esc_html__( 'Payment Reference: ', 'transactpay' ) . $txn_ref;
				$order->add_order_note( $admin_note );
				$this->logger->error( 'Failed to verify transaction ' . $txn_ref . ' after multiple attempts.' );
			} else {
				// Transaction verified successfully.
				// Proceed with setting the payment on hold.
				$response = json_decode( $response['body'] );
				$this->logger->info( wp_json_encode( $response ) );
				if ( (bool) $response->data->status ) {
					$amount = (float) $response->data->amount;
					if ( $response->data->currency !== $order->get_currency() || ! $this->amounts_equal( $amount, $order->get_total() ) ) {
						$order->update_status( 'on-hold' );
						$admin_note  = esc_html__( 'Attention: New order has been placed on hold because of incorrect payment amount or currency. Please, look into it.', 'transactpay' ) . '<br>';
						$admin_note .= esc_html__( 'Amount paid: ', 'transactpay' ) . $response->data->currency . ' ' . $amount . ' <br>' . esc_html__( 'Order amount: ', 'transactpay' ) . $order->get_currency() . ' ' . $order->get_total() . ' <br>' . esc_html__( ' Reference: ', 'transactpay' ) . $response->data->reference;
						$order->add_order_note( $admin_note );
					} else {
						$order->payment_complete( $order->get_id() );
						if ( 'yes' === $this->auto_complete_order ) {
							$order->update_status( 'completed' );
						}
						$order->add_order_note( 'Payment was successful on Transactpay' );
						$order->add_order_note( 'Transactpay  reference: ' . $txn_ref );

						$customer_note  = 'Thank you for your order.<br>';
						$customer_note .= 'Your payment was successful, we are now <strong>processing</strong> your order.';
						$order->add_order_note( $customer_note, 1 );
					}
				}
			}

			wp_send_json(
				array(
					'status'  => 'success',
					'message' => 'Order Processed Successfully',
				),
				WP_Http::CREATED
			);
		}

		wp_safe_redirect( home_url() );
		exit();
	}
}