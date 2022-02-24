=== WooCommerce Permalink Structure ===
Contributors: netzstrategen
Tags: permalink, woocommerce
Requires at least: 4.5
Tested up to: 5.9.1
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Change WooCommerce products permalink to have the same prefix as product categories and the shop base page.

== Description ==

Allows WooCommerce products to have the same permalink path prefix as product categories and the shop base page; i.e., '/shop/category/subcategory/product-name'.
Adjusts internal WordPress rewrite rule structure; not necessarily compatible with all plugins and shop configurations.

== Installation ==

1. Upload the entire woocommerce-permalink-structure folder to the /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

= Requirements =

* PHP 7.0 or later.

= Configuration =

To only show the Shop page content on the WooCommerce Shop page without the
regular product listing, set a constant in `wp-config.php`:
```
const WOOCOMMERCE_PERMALINK_STRUCTURE_SHOP_PAGE_CONTENT_ONLY = TRUE;
```
