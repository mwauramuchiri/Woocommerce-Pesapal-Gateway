<?php

defined( 'ABSPATH' ) || exit;

add_filter('woocommerce_locate_template', 'custom_payment_gateway_order_pay_template', 10, 3);

function custom_payment_gateway_order_pay_template($template, $template_name, $template_path) {
  if ($template_name === 'checkout/form-pay.php') {
    // Check if your custom template file exists in your plugin directory
    $custom_template = plugin_dir_path(__FILE__) . '../templates/checkout/order-pay.php';
    if (file_exists($custom_template)) {
      return $custom_template;
    }
  }
  return $template;
}

add_action( 'woocommerce_add_pesapal_iframe', 'add_pesapal_iframe', 10, 1);
add_action( 'woocommerce_add_pesapal_iframe_error', 'add_pesapal_iframe_error', 10, 1);

function add_pesapal_iframe( $iframe_url = '' ) {
  ?>

  <div class="pesapal__iframe">
    <iframe src="<?= $iframe_url ?>" frameborder="0">
    </iframe>
  </div>

  <?php
}

function add_pesapal_iframe_error( $message = '' ) {
  ?>

  <div class="pesapal__iframe iframe--error">
    <p><?= esc_html( $message ) ?></p>
  </div>

  <?php
}