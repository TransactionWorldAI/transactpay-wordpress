/**
 * WooCommerce dependencies
 */
import { getSetting, WC_ } from '@woocommerce/settings';

export const getBlocksConfiguration = () => {
	const transactpayServerData = getSetting( 'transactpay_data', null );

	if ( ! transactpayServerData ) {
		throw new Error( 'Transactpay initialization data is not available' );
	}

	return transactpayServerData;
};

/**
 * Creates a payment request using cart data from WooCommerce.
 *
 * @param {Object} Transactpay - The Transactpay JS object.
 * @param {Object} cart - The cart data response from the store's AJAX API.
 *
 * @return {Object} A Transactpay payment request.
 */
export const createPaymentRequestUsingCart = ( transactpay, cart ) => {
	const options = {
		total: cart.order_data.total,
		currency: cart.order_data.currency,
		country: cart.order_data.country_code,
		requestPayerName: true,
		requestPayerEmail: true,
		requestPayerPhone: getBlocksConfiguration()?.checkout
			?.needs_payer_phone,
		requestShipping: !!cart.shipping_required,
		displayItems: cart.order_data.displayItems,
	};

	if ( options.country === 'PR' ) {
		options.country = 'US';
	}

	return transactpay.paymentRequest( options );
};

/**
 * Updates the given PaymentRequest using the data in the cart object.
 *
 * @param {Object} paymentRequest  The payment request object.
 * @param {Object} cart  The cart data response from the store's AJAX API.
 */
export const updatePaymentRequestUsingCart = ( paymentRequest, cart ) => {
	const options = {
		total: cart.order_data.total,
		currency: cart.order_data.currency,
		displayItems: cart.order_data.displayItems,
	};

	paymentRequest.update( options );
};

/**
 * Returns the Transactpay public key
 *
 * @throws Error
 * @return {string} The public api key for the Transactpay payment method.
 */
export const getPublicKey = () => {
	const public_key = getBlocksConfiguration()?.public_key;
	if ( ! public_key ) {
		throw new Error(
			'There is no public key available for Transactpay. Make sure it is available on the wc.transactpay_data.public_key property.'
		);
	}
	return public_key;
};