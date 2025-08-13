/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constant';
import {
	getBlocksConfiguration,
} from 'wctransactpay/blocks/utils';

/**
 * Content component
 */
const Content = () => {
	return <div></div>;
};

const TRANSACTPAY_ASSETS = getBlocksConfiguration()?.asset_url ?? null;


const paymentMethod = {
	name: PAYMENT_METHOD_NAME,
	label: (
		<div style={{ display: 'flex', flexDirection: 'row', rowGap: '0em', alignItems: 'center'}}>
			<img
			className='transactionpay-logo-on-checkout'
			src={ `${TRANSACTPAY_ASSETS}/img/budpay_checkout.png` }
			alt={ decodeEntities(
				getBlocksConfiguration()?.title || __( 'Transactpay', 'transactpay' )
			) }
			/>
		</div>
	),
	placeOrderButtonLabel: __(
		'Proceed to Transactpay',
		'transactpay'
	),
	ariaLabel: decodeEntities(
		getBlocksConfiguration()?.title ||
		__( 'Payment via Transactpay', 'transactpay' )
	),
	canMakePayment: () => true,
	content: <Content />,
	edit: <Content />,
	paymentMethodId: PAYMENT_METHOD_NAME,
	supports: {
		features:  getBlocksConfiguration()?.supports ?? [],
	},
}

export default paymentMethod;