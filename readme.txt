=== Sitewide Sales ===
Contributors: strangerstudios, dlparker1005
Tags: sales, sale, woocommerce, paid memberships pro, pmpro, black friday, cyber monday, discount
Requires at least: 5.2
Tested up to: 5.8.1
Stable tag: 1.2

Run Black Friday, Cyber Monday, or other flash sales on your WordPress-powered eCommerce or membership site.

== Description ==

The Sitewide Sales plugin allows you to create flash or sitewide sales. Use the Sitewide Sale CPT to create multiple sales, each with an associated discount code, banners and landing page. The plugin will automatically apply a discount code for users who comlete checkout after visiting the sale landing page. 

The plugin also adds the option to display sitewide page banners to advertise your sale and gives you statistics about the outcome of your sale.

This plugin offers modules for [WooCommerce](https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/documentation/sale-type/woocommerce/), [Paid Memberships Pro](https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/documentation/sale-type/paid-memberships-pro/), and [Easy Digital Downloads](https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/documentation/sale-type/easy-digital-downloads/). You can also use the [Custom sale module](https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/documentation/sale-type/custom/) to track any banner > landing page > conversion workflow. New integrations will be built as requested.

== Installation ==

1. Upload the `sitewide-sales` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Create a new `Sitewide Sale` under `Sitewide Sales` > `Add New`.

== Changelog ==

= 1.2 - 2021-09-22 =
* FEATURE: Added EDD module
* FEATURE: Added "Custom" module
* ENHANCEMENT: Start and end times can now be set for Sitewide Sales
* ENHANCEMENT: Added daily revenue chart to reports
* ENHANCEMENT: Clicking the discount code link in PMPro reports now shows the orders that have used that code
* ENHANCEMENT: Added filter `swsales_pmpro_landing_page_default_discount_code`
* BUG FIX/ENHANCEMENT: Now hiding discount code option for PMPro checkout on SWS landing page
* BUG FIX: Now checking that PMPro discount code is valid before applying on landing page
* BUG FIX: WooCommerce coupons are no longer being applied on every page
* BUG FIX: Removed strike price from WC subscriptions as it wasn't showing consistently
* BUG FIX: Resolved issue where `swsales_show_banner filter` would not always fire
* BUG FIX: Fixed issues where checks for landing/checkout pages failed if no landing or checkout page was set.

= 1.1 - 2020-09-21 =
* NOTE: Sending launch emails today.
* FEATURE: Added a one click migration from PMPro Sitewide Sales.
* BUG FIX: Fixed issue where the wrong discount code/coupon might show up on the "Fancy Coupon" landing page.
* BUG FIX: Fixed the banner tracking code and a few other reporting inaccuracies.
* BUG FIX/ENHANCEMENT: Fixed issue with the WooCommerce landing pages not always showing the discounts if the setting to apply the discount code automatically wasn't set.
* BUG FIX/ENHANCEMENT: Fixed warning message when a sale doesn't have a type set.
* BUG FIX/ENHANCEMENT: Better error handling when checking for updates with an active license.
* ENHANCEMENT: Improved the HTML and CSS for some of the templates.
* ENHANCEMENT: Fixed styling of notices in the admin.
* ENHANCEMENT: Updated styling of the admin pages to be more responsive.
* ENHANCEMENT: Updated the recommended privacy policy text.
* REFACTOR: Updated prefixes on options, functions, and hooks to make them consistently swsales_.
* REFACTOR: Moved the classes folder out of the includes folder. This is a bit more consistent with how PMPro code is structured.

= 1.0 =
* NOTE: Initial soft launch.
* ENHANCEMENT: Adding support for updates through the Stranger Studios license server.

= .1 =
* Ported from PMPro Sitewide Sales
