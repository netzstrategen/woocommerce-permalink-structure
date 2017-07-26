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
    add_filter('query_vars', __CLASS__ . '::query_vars');
    add_filter('product_rewrite_rules', __CLASS__ . '::product_rewrite_rules', 100);
    add_filter('request', __CLASS__ . '::request', 1);
  }

  /**
   * @implements query_vars
   */
  public static function query_vars(array $vars) {
    $vars[] = 'product_cat_and_post_name';
    return $vars;
  }

  /**
   * @implements product_rewrite_rules
   */
  public static function product_rewrite_rules(array $rules) {
    $rules = [
      // Same as default rewrite rule for product categories with default config
      // '%product_cat%/$postname%' for product permalinks, but additionally
      // records the full category/product-name path, so it can be used as a
      // fallback in request().
      'shop((?:/[^/]+?)*?/([^/]+?))(/page/([0-9]+))?/?$' => 'index.php?product_cat=$matches[2]&paged=$matches[4]&product_cat_and_post_name=$matches[1]',
    ] + $rules;
    return $rules;
  }

  /**
   * Rewrites the request to query a product page if the requested product category does not exist.
   *
   * @implements request
   */
  public static function request(array $query_vars) {
    if (isset($query_vars['product_cat']) && $query_vars['product_cat'] !== '' && !term_exists($query_vars['product_cat'], 'product_cat')) {
      // The regular rewrite rule for products is:
      //   shop/(.+?)/([^/]+)(?:/([0-9]+))?/?$	index.php?product_cat=$matches[1]&product=$matches[2]&page=$matches[3]	product
      $query_vars['post_type'] = 'product';
      $query_vars['product'] = $query_vars['product_cat'];
      $query_vars['name'] = $query_vars['product'];
      $query_vars['product_cat'] = trim(strtr($query_vars['product_cat_and_post_name'], [$query_vars['product_cat'] => '']), '/');

      // Also rewrite the paging parameter from categories to posts/pages.
      if (isset($query_vars['paged'])) {
        $query_vars['page'] = $query_vars['paged'];
        unset($query_vars['paged']);
      }
    }
    return $query_vars;
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