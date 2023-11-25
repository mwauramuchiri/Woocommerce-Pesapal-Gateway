<?php
/**
 * @package WoocommercePesapalGateway
 * @version 1.0.0
 */
/*
Plugin Name: Woocommerce Pesapal Gateway
Description: Receive payments via Pesapal Payment Gateway v3
Author: Mwaura Muchiri
Version: 1.0.0
Author URI: https://mwauramuchiri.com
Requires PHP: 7.4.9
Text Domain: woocommerce-pesapal-gateway
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// woocommerce is not installed
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'wp_enqueue_scripts', 'pesapal_css');
add_action( 'plugins_loaded', 'woo_pesapal_payment_init', 11 );
add_filter( 'woocommerce_payment_gateways', 'add_to_woo_pesapal_payment_gateway');

require_once plugin_dir_path( __FILE__ ) . '/includes/pesapal-db.php';

register_activation_hook( __FILE__, 'create_payment_table' );

function woo_pesapal_payment_init() {
	if ( class_exists( 'WC_Payment_Gateway' )) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-pesapal-payment-gateway.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/pesapal-order-pay.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/pesapal-order-request.php';
	}
}

function add_to_woo_pesapal_payment_gateway( $gateways ) {
	$gateways[] = 'WC_Gateway_Pesapal';

	return $gateways;
}

function pesapal_css() {
	wp_enqueue_style( 'pesapal-styles', plugins_url( '/assets/style.css', __FILE__ ) );
}
