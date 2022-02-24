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
    // Add rewrite rules at the beginning of the product category rules to not
    // mistakenly match the product taxonomy ones which are defined upfront.
    add_filter('product_cat_rewrite_rules', __CLASS__ . '::product_rewrite_rules', 100);
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
    // If the shop page matches the product category base, then WooCommerce adds
    // additional rewrite rules, to enforce the product archive listing on the
    // shop page instead of the shop page content:
    //   shop/?$    index.php?post_type=product    other
    // Override this to only show the shop page content, if configured.
    if (defined('WOOCOMMERCE_PERMALINK_STRUCTURE_SHOP_PAGE_CONTENT_ONLY') && WOOCOMMERCE_PERMALINK_STRUCTURE_SHOP_PAGE_CONTENT_ONLY
        && isset($query_vars['post_type']) && $query_vars === ['post_type' => 'product']) {
      // A shop page might be set but may not exist.
      // is_shop() returns false in this early bootstrap phase.
      if (($shop_page_id = wc_get_page_id('shop')) && ($shop_page = get_post($shop_page_id))) {
        $shop_uri = get_permalink($shop_page_id);
        $shop_slug = trim(str_replace(site_url(), '', $shop_uri), '/');
        $is_shop_page = $shop_slug === $shop_page->post_name;
        if ($is_shop_page) {
          // page_id would be much better for performance (avoiding another lookup
          // by post_name), but WP_Query does not populate queried_object with it.
          return ['pagename' => $shop_page->post_name];
        }
      }
    }
    if (isset($query_vars['product_cat']) && $query_vars['product_cat'] !== '' && !term_exists($query_vars['product_cat'], 'product_cat')) {
      // If the requested path is a child page of the shop page then query that
      // page instead of a category or product.
      $pagename = static::getCategoryBase();
      if (isset($query_vars['product_cat_and_post_name'])) {
        $pagename .= '/' . ltrim($query_vars['product_cat_and_post_name'], '/');
      }
      if (get_page_by_path($pagename)) {
        // page_id would be much better for performance (avoiding another lookup
        // by post_name), but WP_Query does not populate queried_object with it.
        return ['pagename' => $pagename];
      }
      // The regular rewrite rule for products is:
      //   shop/(.+?)/([^/]+)(?:/([0-9]+))?/?$	index.php?product_cat=$matches[1]&product=$matches[2]&page=$matches[3]	product
      $query_vars['post_type'] = 'product';
      $query_vars['product'] = $query_vars['product_cat'];
      $query_vars['name'] = $query_vars['product'];
      if (isset($query_vars['product_cat_and_post_name'])) {
        $query_vars['product_cat'] = trim(strtr($query_vars['product_cat_and_post_name'], [$query_vars['product_cat'] => '']), '/');
      }

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
