<?php

defined( 'ABSPATH' ) || exit;

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

add_filter('woocommerce_locate_template', 'custom_payment_gateway_order_pay_template', 10, 3);
