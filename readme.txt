=== Sitewide Sales ===
Contributors: strangerstudios, dlparker1005
Tags: sales, sale, woocommerce, paid memberships pro, pmpro, black friday, cyber monday, discount
Requires at least:
Tested up to: 5.5.1
Stable tag: 1.1

Run Black Friday, Cyber Monday, or other flash sales on your WordPress-powered eCommerce or membership site.

== Description ==

The Sitewide Sales plugin allows you to create flash or sitewide sales. Use the Sitewide Sale CPT to create multiple sales, each with an associated discount code, banners and landing page. The plugin will automatically apply a discount code for users who comlete checkout after visiting the sale landing page. 

The plugin also adds the option to display sitewide page banners to advertise your sale and gives you statistics about the outcome of your sale.

This plugin requires WooCommerce or Paid Memberships Pro to function. New integrations will be built as requested.

== Installation ==

1. Upload the `sitewide-sales` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Create a new `Sitewide Sale` under `Sitewide Sales` > `Add New`.

== Changelog ==
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
