<?php

defined( 'ABSPATH' ) || exit;

$table_name = $wpdb->prefix . 'woocommerce_pesapal_payments'; // Replace 'your_custom_table' with your desired table name

function create_payment_table() {
  global $wpdb;
  global $table_name;

  $charset_collate = $wpdb->get_charset_collate();

  $query = "CREATE TABLE IF NOT EXISTS $table_name (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` varchar(11) NOT NULL,
    `order_tracking_id` varchar(255) NOT NULL,
    `merchant_reference` varchar(255) NOT NULL,
    `token` longtext NOT NULL,
    `amount_paid` float NOT NULL,
    `currency` varchar(55) NOT NULL,
    `payment_method` text,
    `payment_account` text,
    `payment_status_description` varchar(55) DEFAULT NULL,
    `status_code` int(7) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_updated` timestamp NULL DEFAULT NULL,
    `date_deleted` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_id` (`order_id`),
    UNIQUE KEY `order_tracking_id` (`order_tracking_id`),
    UNIQUE KEY `merchant_reference` (`merchant_reference`)
  ) ENGINE=InnoDB AUTO_INCREMENT=10 $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  try {
    dbDelta( $query );
  } catch(Exception $err) {
    var_dump($err);
  }
}

function add_pesapal_payment($payment) {
  global $table_name;

  $query = "INSERT INTO `$table_name` (
    `id`,
    `order_id`,
    `order_tracking_id`,
    `merchant_reference`,
    `token`,
    `amount_paid`,
    `currency`,
    `payment_method`,
    `payment_account`,
    `payment_status_description`,
    `status_code`,
    `date_created`,
    `date_updated`,
    `date_deleted`)
    VALUES (
      NULL,
      '$payment->order_id',
      '$payment->order_tracking_id',
      '$payment->merchant_reference',
      '$payment->token',
      '$payment->amount_paid',
      '$payment->currency',
      NULL,
      NULL,
      NULL,
      NULL,
      CURRENT_TIMESTAMP,
      NULL,
      NULL
    );";
  //

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  try {
    dbDelta( $query );
    return true;
  } catch(Exception $err) {
    var_dump($err);
    return false;
  }
}

function update_pesapal_payment($id, $payment) {
  global $table_name;

  $query = "UPDATE `$table_name` SET
    `amount_paid` = '$payment->amount_paid',
    `payment_method` = '$payment->payment_method',
    `payment_account` = '$payment->payment_account',
    `payment_status_description` = '$payment->payment_status_description',
    `status_code` = '$payment->status_code',
    `date_updated` = NULL,
    `date_deleted` = NULL
    WHERE `$table_name`.`id` = $id";
  //

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  try {
    dbDelta( $query );
    return true;
  } catch(Exception $err) {
    var_dump($err);
    return false;
  }
}

function get_payment_by_order_id($order_id) {
  global $wpdb;
  global $table_name;

  $query = "SELECT * FROM `$table_name` WHERE `order_id` = '$order_id'";

  try {
    return $wpdb->get_row( $query );
  } catch(Exception $err) {
    var_dump($err);
    return false;
  }
}

function get_payment_by_order_tracking_id($order_tracking_id) {
  global $wpdb;
  global $table_name;

  $query = "SELECT * FROM `$table_name` WHERE `order_tracking_id` = '$order_tracking_id'";

  try {
    return $wpdb->get_row( $query );
  } catch(Exception $err) {
    var_dump($err);
    return false;
  }
}
