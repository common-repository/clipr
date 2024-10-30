=== Clipr ===
Contributors: Nicolas Mercier
Tags: ecommerce, sales, conversion
Requires at least: 3.0.1
Tested up to: 5.1
Requires PHP : 5.4
Stable tag: 1.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Storify your Woocommerce eshop and boost its conversion !

== Installation ==
1. Install the plugin through the WordPress plugins screen directly, or upload the plugin files to the `/wp-content/plugins/clipr` directory
2. Activate the plugin through the 'Plugins' screen in WordPress

== Description ==

This plugin requires Woocommerce 2.2 and above installed on your e-shop, and a Clipr account.
Don't have a Clipr account yet ? Ask for one at <https://clipr.co> !

Clipr lets you "storify" your e-shop and boost your conversion rate !

Technically, this module consists in creating 4 new controllers our Clipr platform will use :

1. "checkConfig" controller :
Allow Clipr to detect if this plugin is installed on your shop.

2. {your_product_url}?cnv_cap_id=1 :
This allows Clipr to retrieve product id from any product url

3. "productData" controller :
Allow Clipr to get all necessary public information of a given product identified by its id.
Note that a token validation system is preventing other domain names to use this URL.

4. "cartBuilder" controller :
This is where customers who clicked on "checkout" button will be redirected to.
This controller asks Clipr for cart content, then programmatically add products into cart, and finally redirect customer directly on your checkout page.

These routes are independent : not a single code injection is made on your existing pages, so this plugin cannot change the current behavior of your shop.

Find out more at <https://clipr.co> !

== Changelog ==

= 1.0.1 =
* Readme update
* Illustrations update

= 1.0.2 =
* Now supporting WooCommerce 2.2 and above

= 1.0.3 =
* Bug fix : support attribute not showing as variation

= 1.1.0 =
* Now supporting clip embedded in product page

= 1.1.1 =
* Redirect user on cart if login is required for checkout

= 1.1.2 =
* Supporting "redirect user on cart if login is required for checkout" for Woocommerce 2.2 and above

= 1.1.3 =
* Bug fix on cart redirection

= 1.1.4 =
* Update Clipr API root

= 1.1.5 =
* Fix Clipr API root

= 1.1.6 =
* API root landing & back-office support

= 1.2.0 =
* Wordpress 5.0 compatibility

= 1.2.1 =
* Debug support

= 1.2.2 =
* Delete report from production environment

= 1.2.3 =
* Improve debug support