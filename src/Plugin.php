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
    // Add rewrite rules at the beginning of the product rules to not mistakenly
    // match the product taxonomy ones which are defined upfront.
    add_filter('product_rewrite_rules', __CLASS__ . '::product_rewrite_rules', 100);
    add_filter('request', __CLASS__ . '::request', 1);

    // Force WooCommerce to assume that the product permalink base path is
    // identical to the path of the shop page and thus trigger the generation of
    // rewrite rules necessary for special endpoints of child pages below the
    // my-account page; e.g., orders, logout, etc.
    // @see wc_fix_rewrite_rules()
    add_filter('default_option_woocommerce_permalinks', __CLASS__ . '::option_woocommerce_permalinks');
    add_filter('option_woocommerce_permalinks', __CLASS__ . '::option_woocommerce_permalinks');

    // Removes trailing slash from password reset and other links to avoid redirects.
    add_filter('woocommerce_get_endpoint_url', __CLASS__ . '::woocommerce_get_endpoint_url', 10, 4);
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
      static::getCategoryBase() . '((?:/[^/]+?)*?/([^/]+?))(/page/([0-9]+))?/?$' => 'index.php?product_cat=$matches[2]&paged=$matches[4]&product_cat_and_post_name=$matches[1]',
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
      // If the requested path is a child of the shop page query the page instead of a category or product.
      $pagename = static::getCategoryBase() . '/' . ltrim($query_vars['product_cat_and_post_name'], '/');
      if ($post = get_page_by_path($pagename)) {
        return ['page_id' => $post->ID];
      }
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
   * @implements default_option_NAME
   * @implements option_NAME
   */
  public static function option_woocommerce_permalinks($settings) {
    $settings['use_verbose_page_rules'] = TRUE;
    return $settings;
  }

  /**
   * Removes trailing slash from links to WooCommerce password reset and other pages.
   *
   * WooCommerce appends a trailing slash to the password reset and other special
   * pages, which causes an unnecessary redirect for users.
   *
   * @implements woocommerce_get_endpoint_url
   */
  public static function woocommerce_get_endpoint_url($url, $endpoint, $value, $permalink) {
    if (!$value) {
      $url = untrailingslashit($url);
    }
    return $url;
  }

  /*
   * Returns the WooCommerce category permalink base.
   *
   * @return string
   */
  public static function getCategoryBase(): string {
    $permalinks = (array) get_option('woocommerce_permalinks');
    return $permalinks['category_base'] ?? 'shop';
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
