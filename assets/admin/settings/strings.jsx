/* eslint-disable max-len */
/**
 * External dependencies
 */
import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';

export default {
	button: {
		get_started: __(
			'Get Started!',
			'transactpay'
		),
		save_settings: __(
			'Save Configuration',
			'transactpay'
		),
		enable_test_mode: __( 'Enable Test mode', 'transactpay' ),
		disable_test_mode: __( 'Disable Test mode', 'transactpay' ),
	},
	heading: ( firstName ) =>
		sprintf(
			/* translators: %s: first name of the merchant, if it exists, %s: TransactPay. */
			__( 'Hi%s,\n Welcome to %s!', 'transactpay' ),
			firstName ? ` ${ firstName }` : '',
			'transactpay'
		),
	settings: {
		general: __(
			'API/Webhook Settings',
			'transactpay'
		),
		checkout: __(
			'Checkout Settings',
			'transactpay'
		),
	},
	card: __(
		'Offer card payments',
		'transactpay'
	),
	sandboxMode: {
		title: __(
			"Test Mode: I'm setting up a store for someone else.",
			'transactpay'
		),
		description: sprintf(
			/* translators: %s: Transactpay */
			__(
				'This option will set up %s in test mode. When you’re ready to launch your store, switching to live payments is easy.',
				'transactpay'
			),
			'Transactpay'
		),
	},
	testModeNotice: interpolateComponents( {
		mixedString: __(
			'Test mode is enabled, only test credentials can be used to make payments. If you want to process live transactions, please {{learnMoreLink}}disable it{{/learnMoreLink}}.',
			'transactpay'
		),
		components: {
			learnMoreLink: (
				// Link content is in the format string above. Consider disabling jsx-a11y/anchor-has-content.
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<a
					href="#"
					target="_blank"
					rel="noreferrer"
				/>
			),
		},
	} ),
	infoNotice: {
		button: __( 'enable collection.', 'transactpay' ),
	},
	infoModal: {
		title: sprintf(
			/* translators: %s: Transactpay */
			__( 'Verifying your information with %s', 'transactpay' ),
			'Transactpay'
		),
	},
	stepsHeading: __(
		'You’re only steps away from getting paid',
		'transactpay'
	),
	step1: {
		heading: __(
			'Create and connect your account',
			'transactpay'
		),
		description: __(
			'To ensure safe and secure transactions, a WordPress.com account is required.',
			'transactpay'
		),
	},
	step3: {
		heading: __( 'Setup complete!', 'transactpay' ),
		description: sprintf(
			/* translators: %s: Transactpay */
			__(
				'You’re ready to start using the features and benefits of %s.',
				'transactpay'
			),
			'Transactpay'
		),
	},
	onboardingDisabled: __(
		"We've temporarily paused new account creation. We'll notify you when we resume!",
		'transactpay'
	),
	incentive: {
		termsAndConditions: ( url ) =>
			createInterpolateElement(
				__(
					'*See <a>Terms and Conditions</a> for details.',
					'transactpay'
				),
				{
					a: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a
							href={ url }
							target="_blank"
							rel="noopener noreferrer"
						/>
					),
				}
			),
	},
	nonSupportedCountry: createInterpolateElement(
		sprintf(
			/* translators: %1$s: Transactpay */
			__(
				'<b>%1$s is not currently available in your location</b>.',
				'transactpay'
			),
			'Transactpay'
		),
		{
			b: <b />,
			a: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<a
					href="#"
					target="_blank"
					rel="noopener noreferrer"
				/>
			),
		}
	),
};