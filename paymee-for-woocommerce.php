<?php
/**
* Plugin Name:  Paymee for WooCommerce
* Plugin URI:   https://ahmedev.com
* Description:  Paymee WooCommerce extension adds the ability to use paymee payment system as a payment gateway in your  WooCommerce powered website.
* Version:      v 1.0.0
* Author: Ahmed Benali
* Author URI: https://ahmedev.com/
* Developer: Ahmed Ben Ali
* Developer URI: https://ahmedev.com/
* Text Domain: aba-woo-paymee
* Domain Path: /languages
*
* WC requires at least: 2.6
* WC tested up to: 3.4
*
* Copyright: © 2018 Ahmed Benali.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

    session_start();
    /**
     * Required minimums and constants
     **/

    define( 'PMEE_WC_VERSION', '1.6.1' );
    define( 'PMEE_WC_MIN_PHP_VER', '5.3.0' );
    define( 'PMEE_WC_MIN_WC_VER', '2.6.0' );
    define( 'PMEE_WC_MAIN_FILE', __FILE__ );
    define( 'PMEE_WC_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
    define( 'PMEE_WC_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

    /**
     * Add Paymee Payment to WC Available Gateways
     * 
     * @since 1.0.0
     * @param array $gateways (all available WC gateways)
     * @return array $gateways (all WC gateways + new added Paymee Gateway)
     */
    function aba_paymee_wc_add_to_gateways( $gateways ) {
        $gateways[] = 'ABA_Paymee_Gateway';
        return $gateways;
    }

    /**
     * Adds woo-paymee extension page links
     * 
     * @since 1.0.0
     * @param array $links (all plugin links)
     * @return array $links (all plugin links + Paymee links)
     */
    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'aba_paymee_wc_plugin_links' );
    function aba_paymee_wc_plugin_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paymee' ) . '">' . __( 'Settings', 'aba-woo-paymee' ) . '</a>',
            '<a href="https://ahmedev.com/">' . __( 'Support', 'aba-woo-paymee' ) . '</a>',
        );
        return array_merge( $plugin_links, $links );
    }

    /**
         * Adds Paymee extension templating engine
         * 
         * @since 1.0.0
         * @param string $template
         * @param string $template_name
         * @param string $template_path
         */
    add_filter( 'woocommerce_locate_template', 'paymee_wc_locate_template' , 10, 3 );
    function paymee_wc_locate_template( $template, $template_name, $template_path ) {
      global $woocommerce;

      $_template = $template;

      if ( ! $template_path ) $template_path = $woocommerce->template_url;

      $plugin_path  = PMEE_WC_PLUGIN_PATH . '/woocommerce/';

      $template = locate_template(

        array(
          $template_path . $template_name,
          $template_name
        )
      );

      if ( ! $template && file_exists( $plugin_path . $template_name ) )
        $template = $plugin_path . $template_name;

      if ( ! $template )
        $template = $_template;

      return $template;
    }

    /**
     * Load plugin textdomain.
     *
     * @since 1.0.0
     * @return bool
     */
	add_action( 'init', 'aba_paymee_load_textdomain');
    function aba_paymee_load_textdomain() {
      load_plugin_textdomain( 'aba-woo-paymee', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
    }

    /**
     * Include Paymee Gateway Class and Register Paymee System with WooCommerce
     * 
     * @since 1.0.0
     */

    add_action( 'plugins_loaded', 'aba_paymee_wc_init', 0 );
    function aba_paymee_wc_init() {

        // check if woocommerce is installed and active
        // do nothing if not active
        if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

        // Include Paymee Class ABA_Paymee_Gateway
        include_once( 'includes/class-aba-paymee-gateway.php' );

        // Register Paymee Gateway
        add_filter( 'woocommerce_payment_gateways', 'aba_paymee_wc_add_to_gateways' );
    }