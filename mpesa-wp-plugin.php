<?php

/**
 * Plugin Name: Mpesa WordPress Plugin
 * Plugin URI:
 * Description: Receive payments directly to your store through the Vodacom Mozambique M-Pesa.
 * Author: PaymentsDS
 * Author URI:
 * Version: 0.1.0
 * Text Domain:
 *
 * Copyright: © 2021 PaymentsDS. ()
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author    PaymentsDS
 * @copyright Copyright © 2021 PaymentsDS.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 */

if (!defined('WPINC')) {
	wp_die();
}


if (!defined('MPESA_WP_PLUGIN_VERSION')) {
	define('MPESA_WP_PLUGIN_VERSION', '0.1.0');
}

register_activation_hook(__FILE__, 'mpesa_wp_install');
add_action('plugins_loaded', 'mpesa_wp_update_check');
add_action('plugins_loaded', 'mpesa_wp_init', 0);
add_filter('woocommerce_payment_gateways', 'mpesa_wp_add_gateway_class');



function mpesa_wp_install() {
	global $wpdb;
	$table_name = $wpdb->prefix . "mpesa_wp_transactions";

	if (!get_option('mpesa_wp_version', MPESA_WP_PLUGIN_VERSION)) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$wpdb->query("DROP TABLE IF EXISTS $table_name");

		update_option('mpesa_wp_version');
	}
}

function mpesa_wp_update_check() {
	if (MPESA_WP_PLUGIN_VERSION != get_option('mpesa_wp_version')) {
		mpesa_wp_install();
	}
}

function mpesa_wp_add_gateway_class($gateways) {
	$gateways[] = 'Mpesa_WP_Plugin';
	return $gateways;
}

function mpesa_wp_init() {
	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}

	load_plugin_textdomain(
		'mpesa-wp-plugin',
		false,
		dirname(plugin_basename(__FILE__) . '/languages')
	);

	class Mpesa_WP_Plugin extends WC_Payment_Gateway {

		/**
		 * Plugin Class constructor
		 */
		public function __construct() {
		}

		/**
		 * Plugin options, we deal with it in Step 3 too
		 */
		public function init_form_fields() {
		}

		/*
		 * Custom CSS and JS
		 */
		public function payment_scripts() {
		}

		/*
				* Fields validation
			 */
		public function validate_fields() {
		}

		/*
			 * We're processing the payments here
			 */
		public function process_payment($order_id) {
		}

		/*
			 * The webhook
			 */
		public function webhook() {
		}
	}
}
