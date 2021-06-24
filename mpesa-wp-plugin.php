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
			$this->id = 'mpesa-wp-plugin';
			$this->icon = apply_filters(
				'mpesa_wp_icon',
				plugins_url('public/images/mpesa-logo.png', __FILE__)
			);
			$this->has_fields = false;
			$this->method_title = __('Mpesa WordPress Plugin', 'mpesa-wp-plugin');
			$this->method_description = __('Accept Mpesa payments for your store', 'mpesa-wp-plugin');

			$this->supports = array(
				'products'
			);

			// Load settings
			$this->init_form_fields();

			$this->init_settings();

			$this->title = $this->get_option('title');
			$this->description = $this->get_option('description');
			$this->api_key = $this->get_option('api_key');
			$this->public_key = $this->get_option('public_key');
			$this->service_provider = $this->get_option('service_provider');
			$this->test = $this->get_option('test');
			$this->enabled = $this->get_option('enabled');

			// Save the settings
			add_action(
				'woocommerce_update_options_payment_gateways_' . $this->id,
				array($this, 'process_admin_options')
			);

			add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
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
