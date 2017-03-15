<?php

/*
  Plugin Name: WooCommerce Permalink Structure
  Plugin URI: http://wordpress.org/plugins/woocommerce-permalink-structure/
  Version: 1.0.0
  Text Domain: woocommerce-permalink-structure
  Description: Adjusts WordPress rewrite rule structure for SEO reasons to allow permalinks of the shop base to be identical to the product category base and the product base; i.e., '/shop/category/subcategory/product-name'.
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
