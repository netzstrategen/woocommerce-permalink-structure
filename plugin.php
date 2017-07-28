<?php

/*
  Plugin Name: WooCommerce Permalink Structure
  Plugin URI: http://wordpress.org/plugins/woocommerce-permalink-structure/
  Version: 1.0.3
  Text Domain: woocommerce-permalink-structure
  Description: Allows WooCommerce products to have the same permalink path prefix as product categories and the shop base page; i.e., '/shop/category/subcategory/product-name'. Adjusts internal WordPress rewrite rule structure; not necessarily compatible with all plugins and shop configurations.
  Author: Daniel F. Kudwien (sun)
  Author URI: http://www.netzstrategen.com/sind/daniel-kudwien
  License: GPL-2.0+
  License URI: http://www.gnu.org/licenses/gpl-2.0
*/

namespace Netzstrategen\WooCommerce\PermalinkStructure;

if (!defined('ABSPATH')) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
  exit;
}

/**
 * Loads PSR-4-style plugin classes.
 */
function classloader($class) {
  static $ns_offset;
  if (strpos($class, __NAMESPACE__ . '\\') === 0) {
    if ($ns_offset === NULL) {
      $ns_offset = strlen(__NAMESPACE__) + 1;
    }
    include __DIR__ . '/src/' . strtr(substr($class, $ns_offset), '\\', '/') . '.php';
  }
}
spl_autoload_register(__NAMESPACE__ . '\classloader');

add_action('init', __NAMESPACE__ . '\Plugin::init');
