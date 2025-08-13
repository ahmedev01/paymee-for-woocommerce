<?php
/**
 * Paymee Gateway WooCommerce admin page
 *
 * @since 1.0.0
 * @author Ahmed Benali
 */

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

    $pages = get_pages();
    $options['00'] = esc_attr( __( "Select page" ) );

      foreach ( $pages as $page ) {
        $options [get_page_link( $page->ID )] = $page->post_title;
      }

    return array(
        'enabled'               => array(
            'title'   => __( 'Enable/Disable', 'aba-woo-paymee' ),
            'type'    => 'checkbox',
            'label'   => __( 'Enable Paymee Gateway', 'aba-woo-paymee' ),
            'default' => 'no',
        ),
        'title'                 => array(
            'title'       => __( 'Title', 'aba-woo-paymee' ),
            'type'        => 'text',
            'description' => __( 'This controls the title which the user sees during checkout.', 'aba-woo-paymee' ),
            'default'     => __( 'Pay with Paymee', 'aba-woo-paymee' ),
            'desc_tip'    => true,
        ),
        'description'           => array(
            'title'       => __( 'Description', 'aba-woo-paymee' ),
            'type'        => 'text',
            'desc_tip'    => true,
            'description' => __( 'This controls the description which the user sees during checkout.', 'aba-woo-paymee' ),
            'default'     => __( "Pay via Paymee; you will be redirected to Paymee to login and complete payment.", 'aba-woo-paymee' ),
        ),
        /*'instructions' => array(
                        'title'       => __( 'Instructions', 'aba-woo-paymee' ),
                        'type'        => 'textarea',
                        'description' => __( 'Instructions that will be added to the thank you page and emails.', 'aba-woo-paymee' ),
                        'default'     => '',
                        'desc_tip'    => true,
        ),*/
        'api_token'             => array(
            'title'       => __( 'API Token', 'aba-woo-paymee' ),
            'type'        => 'text',
            'description' => __( 'Please enter your Paymee token.', 'aba-woo-paymee' ),
            'desc_tip'    => true,
        ),
        'vendor_id'                 => array(
            'title'       => __( 'Vendor ID', 'aba-woo-paymee' ),
            'type'        => 'text',
            'description' => __( 'Please enter your Paymee Vendor ID; this is needed in order to receive payment.', 'aba-woo-paymee' ),
            'desc_tip'    => true,
        ),
        'cancel_page'               => array(
            'title'       => __( 'Cancel Page', 'aba-woo-paymee' ),
            'type'        => 'select',
            'options'     => $options,
            'description' => __( 'Please enter the URL to the cancel payment page to show after a customer cancel payment;', 'aba-woo-paymee' ),
            'desc_tip'    => true,
        ),
        'advanced'              => array(
            'title'       => __( 'Advanced options', 'aba-woo-paymee' ),
            'type'        => 'title',
            'description' => '',
        ),
        'sandbox'              => array(
            'title'       => __( 'Paymee sandbox', 'aba-woo-paymee' ),
            'type'        => 'checkbox',
            'label'       => __( 'Enable Paymee sandbox for testing.', 'aba-woo-paymee' ),
            'default'     => 'no',
            'description' => __( 'Paymee sandbox can be used to test payments.', 'aba-woo-paymee' ),
        ),
        'debug'                 => array(
            'title'       => __( 'Debug log', 'aba-woo-paymee' ),
            'type'        => 'checkbox',
            'label'       => __( 'Enable logging', 'aba-woo-paymee' ),
            'default'     => 'no',
            'description' => sprintf( __( 'Log Paymee events, such as API requests, inside %s Note: We recommend using this for debugging purposes only and deleting the logs when finished.', 'aba-woo-paymee' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'paymee' ) . '</code>' )),
    );