<?php
/**
 * Paymee Payment Gateway API Handler Class
 *
 * Handle API informations for Paymee Payment Gateway;
 *
 * @class 		ABA_Paymee_Transaction_Handler
 * @version		1.0.0
 * @author 		Ahmed Benali
 */

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

if ( ! class_exists( 'ABA_Paymee_Transaction_Handler' ) ) {
class ABA_Paymee_Transaction_Handler {
    
    protected $sandbox;
    protected $api_key;
    protected $merchant_id;
    protected $success_page;
    protected $cancel_page;
    protected $orderId;
    
    /**
     * Constructor for handler class.
     *
     * @since 1.0.0
     * @access public
     * @param bool $sandbox (yes or no)
     * @param string $api_key
     * @param string $merchant_id
     * @param string $success_page
     * @param string $cancel_page
	 */
	public function __construct($sandbox = '', $api_key = '', $merchant_id='', $success_page='', $cancel_page='') {

        $this->sandbox = $sandbox;
        $this->api_key = $api_key;
        $this->merchant_id = $merchant_id;
        $this->cancel_page = $cancel_page;
        $this->success_page = add_query_arg('wc-api', 'aba_paymee_transaction_handler', home_url('/')).'&';
            
        add_action ('init', array($this, 'check_payment_status'));
        add_action ('woocommerce_api_aba_paymee_transaction_handler', array( $this, 'check_payment_status'));
        add_action ('woocommerce_receipt_paymee', array( $this, 'transaction_launcher' ));
            
        }
    
    /**
     * Initialise payment request
     *
     * @since 1.0.0
     * @access public
     * @param int $order_id
     * @return array (with payment token)
     */
    public function init_payment_request($order_id){
        
        global $woocommerce;
        
        $order = new WC_Order($order_id);
        
        if ($this->sandbox == 'yes') {
                $request_url = 'http://sandbox.paymee.tn/api/OPRequest/';
            }
        elseif ($this->sandbox == 'no') {
                $request_url = 'https://app.paymee.tn/api/OPRequest/';
            }
        else {
            wc_add_notice(sprintf(__('We are currently experiencing problems trying to connect to Paymee. Sorry for the inconvenience.', 'aba-woo-paymee')), $notice_type = 'error');
            ABA_Paymee_Gateway::log('Payment failure: request URL not found or invalid.', 'error');
            wp_redirect(get_permalink(get_option('woocommerce_checkout_page_id')));
        }
        
        $json_headers = array('Content-type: application/json', 'Authorization: Token '.$this->api_key);
        
        $json_data = json_encode(array (
            'vendor' => $this->merchant_id,
            'amount' => $order->get_total(),
            'note'   => $order->get_customer_note()
        ));
        
        $init_request = curl_init($request_url);
        
        //cURL options
        curl_setopt($init_request, CURLOPT_POST, 1);
        curl_setopt($init_request, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($init_request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($init_request, CURLOPT_HTTPHEADER, $json_headers);
        curl_setopt($init_request, CURLOPT_HEADER, false);
        curl_setopt($init_request, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($init_request, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($init_request, CURLOPT_SSL_VERIFYPEER, false);

        $result = json_decode(curl_exec($init_request), true);
        curl_close($init_request);
        
        return $result;
    }
    
    /*
     * Paymee redirection
     *
     * @since 1.0.0
     * @access public
     * @param int $order_id
     * @return string (payment form html)
     */
    public function redirect_and_pay($order_id){
        global $woocommerce;
        $order = new WC_Order($order_id);
        $init_result = $this->init_payment_request($order_id);
        
        if ($init_result){
        if ($this->sandbox == 'yes') {
                $redirect_url = 'http://sandbox.paymee.tn/gateway/';
            }
        elseif ($this->sandbox == 'no') {
                $redirect_url = 'https://app.paymee.tn/gateway/';
            }
        else {
            wc_add_notice(sprintf(__('We are currently experiencing problems trying to connect to Paymee. Sorry for the inconvenience.', 'aba-woo-paymee')), $notice_type = 'error');
            ABA_Paymee_Gateway::log('Payment failure: redirect URL not found or invalid for order '.$order_id, 'error');
            wp_redirect(get_permalink(get_option('woocommerce_checkout_page_id')));
            exit;
        }
        }else{
            wc_add_notice(sprintf(__('We are currently not able to process the payment. No valid response from Paymee. Sorry for the inconvenience.', 'aba-woo-paymee')), $notice_type = 'error');
            ABA_Paymee_Gateway::log('Payment failure: token not found or invalid for order '.$order_id, 'error');
            wp_redirect(get_permalink(get_option('woocommerce_checkout_page_id')));
            exit;
        }
       
       $formfields = array (
           'payment_token' => $init_result['token'],
           'url_ok' => $this->success_page,
           'url_ko' => $this->cancel_page
       );
            wc_enqueue_js('
            jQuery("#submit_paymee_form").click();
            ');
            $formresult = '<form action="'.esc_url($redirect_url).'" method="post" id="paymee_form">'; 
            foreach ($formfields as $key => $value) {
                $formresult .= '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'" />';
            }
            $formresult .= '<input type="submit" class="button" id="submit_paymee_form" value="'.__('Pay Now', 'aba-woo-paymee').'"/>
            </form>';
            return $formresult;
        
    }
    
    /**
     * Payment transaction launcher
     *
     * @since 1.0.0
     * @access public
     * @param int $order_id
     */
    public function transaction_launcher($order_id) {
        echo $this->redirect_and_pay($order_id);
    }
    
    /*
     * Check for payment status
     *
     * @since 1.0.0
     * @access public
     */
    public function check_payment_status(){
        global $woocommerce;
        if ($this->sandbox == 'yes') {
                $check_url = 'http://sandbox.paymee.tn/api/OPCheck/';
            }
        elseif ($this->sandbox == 'no') {
                $check_url = 'https://app.paymee.tn/api/OPCheck/';
            }
        
        $json_headers = array('Content-Type:application/json', 'Authorization: Token '.$this->api_key);
        
        $json_data = json_encode(array (
            'token' => $_GET['?payment_token'],
        ));
        
        $check_status = curl_init($check_url);
        
        //set cURL options
        curl_setopt($check_status, CURLOPT_POST, 1);
        curl_setopt($check_status, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($check_status, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($check_status, CURLOPT_HTTPHEADER, $json_headers);
        curl_setopt($check_status, CURLOPT_HEADER, false);
        curl_setopt($check_status, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($check_status, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($check_status, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = json_decode(curl_exec($check_status), true);
        curl_close($check_status);
        
        if ($result['result']==1) {
                $order = new WC_Order($_COOKIE['paymee_order']);
                $order->add_order_note(sprintf(__('Successful Payment. Transaction number # %s.', 'aba-woo-paymee'), $_GET['transaction']));
                $order->payment_complete();
                wc_reduce_stock_levels($current_order);
                WC()->cart->empty_cart();
                wc_add_notice(sprintf(__('Successful Payment. Transaction number # %s.', 'aba-woo-paymee'), $_GET['transaction']), $notice_type = 'info');
                ABA_Paymee_Gateway::log('Payment success: payment token:'.$_GET['?payment_token'].'Transaction number # '.$_GET['transaction'].'order '.$_COOKIE['paymee_order'], 'info');
                wp_redirect(ABA_Paymee_Gateway::get_return_url($order));
                exit;
            }
            wc_add_notice(sprintf(__('Payment failed somewhere.', 'aba-woo-paymee')), $notice_type = 'error');
            ABA_Paymee_Gateway::log('Payment failure: payment token not found or invalid. Used token:'.$_GET['?payment_token'].'values '.$result['result'], 'error');
            wp_redirect(get_permalink(get_option('woocommerce_checkout_page_id')));
            exit;
    }
}
}