=== Transactpay ===
Contributors: TransactWorld Digital Engineers
Tags: payments, mastercard, visa
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.3
Stable tag: 1.0.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
TransactPay enables you to collect payments on your WooCommerce store.

== Description ==
TransactPay allows merchants to seamlessly accept card payments through their WooCommerce store, supporting major card providers like Mastercard and Visa.

= Plugin Features =
* Collections: Accept payments via cards.


= Requirements =

1. WordPress 6.0 or newer.
2. TranasctPay Merchant Account [API Keys](https://merchant.transactpay.ai/)
3. WooCommerce 7.6 or newer.
4. Supported PHP version: 7.4.0 or newer is recommended.

== Installation ==
= Manual Installation =
1.  Download the plugin zip file.
2.  Login to your WordPress Admin. Click on "Plugins > Add New" from the left menu.
3.  Click on the "Upload" option, then click "Choose File" to select the zip file you downloaded. Click "OK" and "Install Now" to complete the installation.
4.  Activate the plugin.
5.  Click on "WooCommerce > Settings" from the left menu and click the "Checkout" tab.
6.  Click on the __Transactpay__ link from the available Checkout Options
7. Configure your __Transactpay__ settings accordingly.

For FTP manual installation, [check here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Configure the plugin =
To configure the plugin, go to __WooCommerce > Settings__ from the left menu, click __Checkout__ tab. Click on __Transactpay__.
Alternatively you can see the transactpay button on the sidebar. click it.

* __Enable/Disable__ - check the box to enable Transactpay.
* Configure your general setting by providing your merchant secret key and public key.
* Testmode is enabled by default. To make live collections disable Test mode.
* Click __Save Changes__ to save your changes in each section.

= Webhooks =

= 1.0.0 =
*   First release
*   Added: Support for WooCommerce Blocks.
*   Updated: WooCommerce Checkout Process
*   Added: Webhook Handler Acknowledgement.
*   Added: Support for HPOS.
*   Added: compatibility with WooCommerce 7.1 to 6.9.1

== Screenshots ==



== Other Notes ==