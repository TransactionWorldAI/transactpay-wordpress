import { registerPlugin } from "@wordpress/plugins";
import { addFilter } from "@wordpress/hooks";
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { useEffect, useState } from '@wordpress/element';
// import { sanitizeHTML } from '@woocommerce/utils';
// import { RawHTML } from '@wordpress/element';
// Example of RawHTML and sanitize HTML: https://github.com/Saggre/woocommerce/blob/e38ffc8427ec4cc401d90482939bae4cddb69d7c/plugins/woocommerce-blocks/assets/js/extensions/payment-methods/bacs/index.js#L24

import { 
	Button,
	Panel,
	PanelBody,
	Card,
	Snackbar,
	CheckboxControl,
	ToggleControl,
	__experimentalText as Text,
	__experimentalHeading as Heading,
	__experimentalInputControl as InputControl,
	ResponsiveWrapper
} from '@wordpress/components';
import { WooNavigationItem } from "@woocommerce/navigation";
// import * as Woo from '@woocommerce/components';
import { Fragment } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
// import { addQueryArgs } from '@wordpress/url';

/** Internal Dependencies */
import strings from './strings';
import Page from 'wctransactpay/admin/components/page';
import Input, { CustomSelectControl } from 'wctransactpay/admin/components/input';
// import { CheckoutIcon, EyeIcon } from 'wctransactpay/admin/icons';

const NAMESPACE = "transactpay/v1";
const ENDPOINT = "/settings";

import './index.scss';

apiFetch({ path: NAMESPACE + ENDPOINT }).then((configuration) => console.log(configuration));

// https://woocommerce.github.io/woocommerce-blocks/?path=/docs/icons-icon-library--docs

const TransactpaySaveButton = ( { children, onClick } ) => {
	const [isBusy, setIsBusy] = useState(false);
	useEffect(() => {}, [isBusy]);
	return (
		<Button
			className="transactpay-settings-cta"
			variant="secondary"
			isBusy={ isBusy }
			disabled={ false }
			onClick={ () => { 
				setIsBusy(true); 
				onClick(setIsBusy);
			} }
		>
			{ children }
		</Button>
	)
}

const EnableTestModeButton = ({ onClick, isDestructive, isBusy, children}) => {
	const [ isRed, setIsRed ] = useState(isDestructive);
	useEffect(() => {}, [isDestructive]);
	return (
		<Button
			className="transactpay-settings-cta"
			variant="secondary"
			isBusy={ isBusy }
			disabled={ false }
			isDestructive={ isRed }
			onClick={ () => {
				onClick()
				setIsRed(!isRed)
			} }
			>
			{children}
		</Button>
	)
}

const TransactpaySettings = () => {
	/** Initial Values */
	const default_settings = transactpayData?.transactpay_defaults;
	const [openGeneralPanel, setOpenGeneralPanel] = useState(false);
;	const firstName = wcSettings.admin?.currentUserData?.first_name || 'there';
	const TRANSACTPAY_LOGO_URL = transactpayData?.transactpay_logo;
	const [transactpaySettings, setTransactPaySettings] = useState(default_settings);
	const [enableGetStartedBtn, setEnabledGetstartedBtn] = useState(false);
	const payment_style_on_checkout_options = [
        // { label: 'Redirect', value: 'redirect' },
        { label: 'Popup', value: 'inline' },
    ];

	let headingStyle = {  };

	if(firstName != '') {
		headingStyle['whiteSpaceCollapse'] = 'preserve-breaks';
	}
	/** Initial Values End */

	/** Handlers */
	const handleChange = (key, value) => {
		setTransactPaySettings(prevSettings => ({
			...prevSettings,
			[key]: value 
		}));
	};
	
	const handleSecretKeyChange = (evt) => {
		handleChange('live_secret_key', evt);
	};

	const handleEncryptionKeyChange = (evt) => {
		handleChange('live_encryption_key', evt);
	};

	const handlePaymentTitle = (evt) => {
		handleChange('title', evt);
	}
	
	const handlePublicKeyChange = (evt) => {
		handleChange('live_public_key', evt);
	};

	const handleTestSecretKeyChange = (evt) => {
		handleChange('test_secret_key', evt);
	};

	const handleTestPublicKeyChange = (evt) => {
		handleChange('test_public_key', evt);
	};

	const handleTestEncryptionKeyChange = (evt) => {
		handleChange('test_encryption_key', evt);
	}

	return (
		<Fragment>
			<Page isNarrow >
				<Card className="transactpay-page-banner">
		
					<div className="transactpay-page__heading">
						<img className="transactpay__settings_logo" alt="transactpay-logo" src={ TRANSACTPAY_LOGO_URL } id="transactpay__settings_logo" />

						<h2 className="transactpay-font-heading" style={{ marginLeft: "15px", ...headingStyle }}>{ strings.heading( firstName ) }</h2>
					</div>	

					<div className="transactpay-page__buttons">
						<Button
							variant="primary"
							isBusy={ false }
							disabled={ enableGetStartedBtn }
							onClick={ () => {
								setOpenGeneralPanel(true);
								setEnabledGetstartedBtn(false);
							} }
						>
							{ strings.button.get_started }
						</Button>
					</div>
		
				</Card>

				<Panel className="transactpay-page__general_settings-panel">
					<PanelBody
						title={ strings.settings.general }
						initialOpen={ openGeneralPanel }
					>
						<div className="transactpay-settings__general">
							<ToggleControl
								checked={ transactpaySettings.enabled == 'yes' }
								label="Enable Transactpay"
								onChange={() => setTransactPaySettings( prevSettings => ({
									...prevSettings,
									enabled: prevSettings.enabled == 'yes' ? 'no' : 'yes' // Toggle the value
								}) )}
							/>
							
							<div className="transactpay-settings__inputs">
								<Input labelName="Secret Key" initialValue={ transactpaySettings.live_secret_key } onChange={ handleSecretKeyChange } isConfidential />
								<Input labelName="Public Key" initialValue={ transactpaySettings.live_public_key } onChange={ handlePublicKeyChange }  />
								<Input labelName="Encryption Key" initialValue={ transactpaySettings.live_encryption_key } onChange={ handleEncryptionKeyChange }  />
							</div>

							<Text className="transactpay-webhook-link" numberOfLines={1} >
								{ transactpayData.transactpay_webhook }
							</Text>

							<Text className="transactpay-webhook-instructions" numberOfLines={1} >
							Please add this webhook URL and paste on the webhook section on your dashboard.
							</Text>
						</div>

						<div className="transactpay-settings-btn-center">
							<TransactpaySaveButton onClick={ (setIsBusy) => {
									apiFetch({
										path: NAMESPACE + ENDPOINT,
										method: 'POST',
										headers: {
											'Content-Type': 'application/json',
											'X-WP-Nonce': wpApiSettings.nonce,
										},
										data: transactpaySettings // Send the updated settings to the server
									}).then(response => {
										console.log('Settings saved successfully:', response);
										// Optionally, you can update the UI or show a success message here
										setIsBusy(false);

									}).catch(error => {
										console.error('Error saving settings:', error);
										// Handle errors if any
									});
								} }>
								{ strings.button.save_settings }	
							</TransactpaySaveButton>
						</div>
					</PanelBody>
				</Panel>

				<Panel className="transactpay-page__checkout_settings-panel">
					<PanelBody
						title={ strings.settings.checkout }
						// icon={ CheckoutIcon }
						initialOpen={ false }
					>
						{/* <Woo.CheckboxControl
						instanceId="transactpay-autocomplete-order"
						checked={ true }
						label="Autocomplete Order After Payment"
						onChange={ ( isChecked ) => {
							console.log(isChecked);
						} }
						/> */}

						
						<div className="transactpay-settings__inputs">
							<CheckboxControl
								checked={ transactpaySettings.autocomplete_order == 'yes' }
								help="should we complete the order on a confirmed payment?"
								label="Autocomplete Order After Payment"
								onChange={ () => setTransactPaySettings( prevSettings => ({
									...prevSettings,
									autocomplete_order: prevSettings.autocomplete_order == 'yes' ? 'no' : 'yes' // Toggle the value
								}) ) }
							/>
							<Input labelName="Payment method Title" initialValue={ transactpaySettings.title } onChange={ handlePaymentTitle } />
							<CustomSelectControl labelName="Payment Style on Checkout" initialValue={ transactpaySettings.payment_style } options={ payment_style_on_checkout_options } />
						</div>

						<div className="transactpay-settings-btn-center">
							<TransactpaySaveButton onClick={ (setIsBusy) => {
									apiFetch({
										path: NAMESPACE + ENDPOINT,
										method: 'POST',
										headers: {
											'Content-Type': 'application/json',
											'X-WP-Nonce': wpApiSettings.nonce,
										},
										data: transactpaySettings // Send the updated settings to the server
									}).then(response => {
										console.log('Settings saved successfully:', response);
										// Optionally, you can update the UI or show a success message here
										setIsBusy(false);

									}).catch(error => {
										console.error('Error saving settings:', error);
										// Handle errors if any
									});
								} }>
								{ strings.button.save_settings }	
							</TransactpaySaveButton>
						</div>
					</PanelBody>
				</Panel>

				<Panel className="transactpay-page__sandbox-mode-panel">
					<PanelBody
						title={ strings.sandboxMode.title }
						initialOpen={ false }
					>
							<p>{ strings.sandboxMode.description }</p>
						<div className="transactpay-settings__inputs">
							<Input labelName="Test Secret Key" initialValue={ transactpaySettings.test_secret_key } onChange={ handleTestSecretKeyChange }  isConfidential />
							<Input labelName="Test Public Key" initialValue={ transactpaySettings.test_public_key } onChange={ handleTestPublicKeyChange }  />
							<Input labelName="Test Encryption Key" initialValue={ transactpaySettings.test_encryption_key } onChange={ handleTestEncryptionKeyChange }  />
						</div>
						<EnableTestModeButton
							className="transactpay-settings-cta"
							variant="secondary"
							isBusy={ false }
							disabled={ false }
							isDestructive={ transactpaySettings.go_live == 'no' }
							onClick={ () => {
								setTransactPaySettings( prevSettings => ({
									...prevSettings,
									go_live: (prevSettings.go_live == 'yes') ? 'no' : 'yes' // Toggle the value
								}) )

								apiFetch({
									path: NAMESPACE + ENDPOINT,
									method: 'POST',
									headers: {
										'Content-Type': 'application/json',
										'X-WP-Nonce': wpApiSettings.nonce,
									},
									data: { ...transactpaySettings, go_live: (transactpaySettings.go_live == 'yes') ? 'no' : 'yes'  } // Send the updated settings to the server
								}).then(response => {
									console.log('Test mode enabled successfully:', response);
									// Optionally, you can update the UI or show a success message here
								}).catch(error => {
									console.error('Error saving settings:', error);
									// Handle errors if any
								});
							} }
						>
							{ (transactpaySettings.go_live === 'yes') ?  strings.button.enable_test_mode: strings.button.disable_test_mode  }
						</EnableTestModeButton>
					</PanelBody>
				</Panel>
			</Page>
		</Fragment>
	);
} 

addFilter("woocommerce_admin_pages_list", "transactpay", (pages) => {
  pages.push({
    container: TransactpaySettings,
    path: "/transactpay",
    wpOpenMenu: "toplevel_page_woocommerce",
    breadcrumbs: ["Transactpay"],
  });

  return pages;
});

const TransactPayNav = () => {
	return (
	  <WooNavigationItem parentMenu="transactpay-root" item="transactpay-1">
		<a className="components-button" href="https://merchant.transactpay.ai/">
		  TransactPay
		</a>
	  </WooNavigationItem>
	);
};

registerPlugin("my-plugin", { render: TransactPayNav });