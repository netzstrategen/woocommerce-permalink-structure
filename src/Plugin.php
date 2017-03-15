<?php

/**
 * @file
 * Contains \Netzstrategen\WooCommerce\PermalinkStructure\Plugin.
 */

namespace Netzstrategen\WooCommerce\PermalinkStructure;

/**
 * Main front-end functionality.
 */
class Plugin {

  /**
   * Gettext localization domain.
   *
   * @var string
   */
  const L10N = 'woocommerce-permalink-structure';

  /**
   * @var string
   */
  private static $baseUrl;

  /**
   * @implements init
   */
  public static function init() {
    add_filter('rewrite_rules_array', __CLASS__ . '::rewrite_rules_array', 100);
  }

  /**
   * @implements rewrite_rules_array
   */
  public static function rewrite_rules_array(array $rules) {
    $rules = [
      // Category pages (paging).
      'shop/([^/]*?)/page/([0-9]{1,})/?$' => 'index.php?product_cat=$matches[1]&paged=$matches[2]',
      // Subcategory pages.
      // @todo Add paging.
      // @todo Add support for any amount of nested categories instead of two levels.
      'shop/([^/]*?)/([^/]*?)/?$' => 'index.php?product_cat=$matches[2]',
      // Category pages.
      'shop/([^/]*?)/?$' => 'index.php?product_cat=$matches[1]',
    ] + $rules;
    return $rules;
  }

  /**
   * The base URL path to this plugin's folder.
   *
   * Uses plugins_url() instead of plugin_dir_url() to avoid a trailing slash.
   */
  public static function getBaseUrl() {
    if (!isset(self::$baseUrl)) {
      self::$baseUrl = plugins_url('', self::getBasePath() . '/plugin.php');
    }
    return self::$baseUrl;
  }

  /**
   * The absolute filesystem base path of this plugin.
   *
   * @return string
   */
  public static function getBasePath() {
    return dirname(__DIR__);
  }

}
