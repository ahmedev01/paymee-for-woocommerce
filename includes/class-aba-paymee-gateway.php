<?php 

/**
 * Paymee Payment Gateway Class
 *
 * Provides Paymee Payment Gateway for WooCommerce websites;
 *
 * @class 		ABA_Paymee_Gateway
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @author 		Ahmed Benali
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'ABA_Paymee_Gateway' ) ) {
class ABA_Paymee_Gateway extends WC_Payment_Gateway {
    
    /** @var bool Whether or not logging is enabled */
    public static $log_enabled = false;

    /** @var WC_Logger Logger instance */
    public static $log = false;
    
    /**
	 * Constructor for Paymee gateway settings.
     *
     * @since 1.0.0
     * @access public
	 */
		public function __construct() {
	  
			$this->id                 = 'paymee';
			$this->icon               = PMEE_WC_PLUGIN_URL.'/assets/images/paymee-logo.png';
			$this->has_fields         = false;
            $this->order_button_text  = __( 'Proceed to Paymee', 'aba-woo-paymee' );
			$this->method_title       = __( 'Paymee', 'aba-woo-paymee' );
			$this->method_description = __( 'Paymee Gateway redirects customers to Paymee to complete payment. You need to have a Paymee merchant account.', 'aba-woo-paymee' );
		    $this->title              = __( 'Paymee', 'aba-woo-paymee' );
            //$this->supports           = apply_filters( 'aba_pmee_wc_supports', array( 'products' ) );
           
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 999 );
            
            // Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			//$this->instructions = $this->get_option( 'instructions' );
            $this->api_token    = $this->get_option( 'api_token' );
            $this->vendor_id    = $this->get_option( 'vendor_id' );
            $this->sandbox      = $this->get_option( 'sandbox', 'no' );
            $this->debug        = $this->get_option( 'debug', 'no' );
            $this->cancel_page  = $this->get_option( 'cancel_page' );
		  
            self::$log_enabled  = $this->debug;

            if ( $this->sandbox == 'yes' ) {
                $this->description .= __( ' SANDBOX ENABLED. You can use sandbox testing accounts only.','aba-woo-paymee');
            }

            if ( ! $this->is_currency_valid()) {
                $this->enabled = 'no';
                self::log('Currency test: currency is not supported', 'error');
            } elseif (!$this->is_curl_enabled()){
                $this->enabled = 'no';
                self::log('cURL extension disabled', 'error');
            }else {
                include_once( dirname( __FILE__ ) . '/class-aba-paymee-transaction-handler.php' );
                new ABA_Paymee_Transaction_Handler ( $this->sandbox, $this->api_token, $this->vendor_id, $this->cancel_page );
            }
            
			// Actions
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            } else {
            add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
            }
			// for upcoming versions
            //add_action( 'woocommerce_thankyou_'.$this->id, array( $this, 'thankyou_page' ), 1, 3 );
			//add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 1, 3 );
            		
		}
    
    /**
     * Logging method.
     *
     * @since 1.0.0
     * @access public
     * @param string $message
     * @param string $level
     */
    public static function log( $message, $level = 'info' ) {
        if ( self::$log_enabled ) {
            if ( empty( self::$log ) ) {
                self::$log = wc_get_logger();
            }
            self::$log->log( $level, $message, array( 'source' => 'paymee' ) );
        }
    }
    
    /**
     * Check if this gateway is enabled and available in the user's country.
     *
     * @since 1.0.0
     * @access public
     * @return bool
     */
    public function is_currency_valid() {
        return in_array( get_woocommerce_currency(), array('TND'));
    }
    
    /**
     * Check if php cURL extension is enabled.
     *
     * @since 1.0.0
     * @access public
     * @return bool
     */
    public function is_curl_enabled() {
        return function_exists('curl_version');
    }
    
    /**
     * Initialise Paymee WooCommerce settings form fields.
     *
     * @since 1.0.0
     * @access public
     */
    public function init_form_fields() {
        $this->form_fields = include( 'admin-wc-paymee-settings.php' );
    }

    /**
     * Admin Options.
     *
     * @since 1.0.0
     * @access public
     */
    public function admin_options() {
        if ( $this->is_currency_valid() && $this->is_curl_enabled()){
            if ($this->needs_setup()) {
                ?>
            <div class="inline error"><p><strong><?php _e( 'Paymee Gateway setup incomplete', 'aba-woo-paymee' ); ?></strong>: <?php _e( 'Please make sure that the fields API key, Merchant ID and Cancel page are filled correctly.', 'aba-woo-paymee' ); 
            ?></p></div>
            <?php
                $this->enabled = 'no';
            }
            
        } else {
            if ( !$this->is_currency_valid()) {
            ?>
            <div class="inline error"><p><strong><?php _e( 'Paymee Gateway disabled', 'aba-woo-paymee' ); ?></strong>: <?php _e( 'Paymee does not support your store currency.', 'aba-woo-paymee' ); ?></p></div>
            <?php
            } 
            if (!$this->is_curl_enabled()) {
            ?>
            <div class="inline error"><p><strong><?php _e( 'Paymee Gateway disabled', 'aba-woo-paymee' ); ?></strong>: <?php _e( 'Paymee requires cURL php extension to run properly. Please make sure to enable it in your server', 'aba-woo-paymee' ); ?></p></div>
            <?php
            }
        }
        parent::admin_options();
        }
    
    /**
	 * Return whether or not Paymee still requires setup.
	 *
	 * @since 1.0.0
     * @access public
	 * @return bool
	 */
	public function needs_setup() {
        $setup = false;
        if ($this->api_token == '' || $this->vendor_id == '' || $this->cancel_page == '00') 
            {
                $setup = true;
            }
		return $setup;
	}
    
    /**
	 * Changes footer text in Paymee settings page.
	 *
     * @since 1.0.0
     * @access public
	 * @param string $text Footer text.
	 *
	 * @return string
	 */
	public function admin_footer_text( $text ) {
		if ( isset( $_GET['section'] ) && 'paymee' === $_GET['section'] ) {
			$text = _e('If you like Paymee for WooCommerce, please consider <strong>assigning Paymee as your payment processor partner</strong>.','aba-woo-paymee' );
		}

		return $text;
	}
    
    /**
     * Process the payment and return the result
     *
     * @since 1.0.0
     * @access public
     * @param int $order
     * @return array
     */
    public function process_payment($order_id) {
            global $woocommerce;
            
            $order = new WC_Order($order_id);
            if( isset( $_COOKIE['paymee_order'] )) {
                  unset( $_COOKIE['paymee_order'] );
                  setcookie( 'paymee_order', '', time() - ( 15 * 60 ) );
                  setcookie('paymee_order', $order_id, 0, '/');
            } else {
                setcookie('paymee_order', $order_id, 0, '/');
            }
            self::log('order '.$_SESSION['paymee_order'].'order id :'.$order_id.'session '.$_SESSION, 'info');
            return array(
                'result' 	=> 'success',
                'redirect'	=> $order->get_checkout_payment_url(true)
            );
    }
    
    /**
     * Output for the order received page.
     *
     * @since 1.0.0
     * @access public
     */
    public function thankyou_page() {
            
    }
    
    /**
     * Add content to custommer WooCommerce emails.
     *
     * @since 1.0.0
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

        }
  }
}