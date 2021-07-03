<?php

/**
 * Plugin Name: 		Mpesa WordPress Plugin
 * Plugin URI: 			https://github.com/paymentsds/mpesa-wp-plugin
 * Description: 		Receive payments directly to your store through the Vodacom Mozambique M-Pesa.
 * Author: 					PaymentsDS
 * Author URI: 			https://developers.paymentsds.org/
 * Version: 				0.1.0
 * Text Domain: 		mpesa-wp-plugin
 * Domain Path: 		/languages
 *
 * Copyright: 			© 2021 PaymentsDS. (https://developers.paymentsds.org/)
 *
 * License: 				Apache License 2.0
 * License URI: 		https://www.apache.org/licenses/LICENSE-2.0
 *
 * @author    			PaymentsDS
 * @copyright 			Copyright © 2021 PaymentsDS.
 * @license   			https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 *
 */

if (!defined('WPINC')) {
	wp_die();
}
require 'vendor/autoload.php';

use Paymentsds\MPesa\Client;
use Paymentsds\MPesa\Environment;

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
				plugins_url('assets/mpesa-logo.png', __FILE__)
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
		}

		/**
		 * Plugin options
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'       => __('Enable/Disable', 'mpesa-wp-plugin'),
					'label'       => __('Enable Mpesa Wordpress plugin', 'mpesa-wp-plugin'),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title' => array(
					'title' => __('Title', 'mpesa-wp-plugin'),
					'type' => 'text',
					'description' => __('This controls the title which the user sees during checkout', 'mpesa-wp-plugin'),
					'default' => __('Mpesa', 'mpesa-wp-plugin'),
					'desc_tip'      => true,
				),
				'description' => array(
					'title' => __('Customer Message', 'mpesa-wp-plugin'),
					'type' => 'textarea',
					'default' => __('Insert your number below to proceed with checkout', 'mpesa-wp-plugin')
				),
				'api_key' => array(
					'title' => __('API Key', 'mpesa-wp-plugin'),
					'type' => 'password',
					'default' => __('', 'mpesa-wp-plugin')
				),
				'public_key' => array(
					'title' => __('Public Key', 'mpesa-wp-plugin'),
					'type' => 'textarea',
					'default' => __('', 'mpesa-wp-plugin')
				),
				'service_provider' => array(
					'title' => __('Service Provider Code', 'mpesa-wp-plugin'),
					'type' => 'text',
					'description' => __('Use 171717 for testing', 'mpesa-wp-plugin'),
					'default' => '171717'
				),
				'test' => array(
					'title' => __('Test Mode', 'mpesa-wp-plugin'),
					'type' => 'checkbox',
					'label' => __('Enable Test Environment', 'mpesa-wp-plugin'),
					'default' => 'yes',
				),
			);
		}

		/*
				*  Payment fields
			 */

		public function payment_fields() {
			session_start();
			if ($this->description) {
				if ('yes' == $this->test) {
					$this->description .= __(
						'<br />
						<strong>TEST MODE ENABLED</strong>',
						'mpesa-wp-plugin'
					);
				}

				$text = __(
					'<strong>Pay with Mpesa</strong><br/>',
					'mpesa-wp-plugin'
				) . $this->description;

				$text = trim($text);

				echo wpautop(wp_kses_post($text));
			}

			if (isset($_SESSION['wc_mpesa_number'])) {
				$number = $this->wc_mpesa_validate_number($_SESSION['wc_mpesa_number']);
			} else {
				$number = '';
			}

			echo '
			<fieldset
				id="wc-' . esc_attr($this->id) . '-cc-form"
				class="wc-credit-card-form wc-payment-form"
				style="background:transparent;"
			>';

			echo '
				<div class="form-row form-row-wide">
					<label>'
				. esc_html__('Mpesa Number', 'mpesa-wp-plugin') .
				'<span class="required"> * </span>
					</label>
					<input
						id="wc_mpesa_number"
						name="wc_mpesa_number"
						class="wc_mpesa_number"
						type="tel"
						autocomplete="off"
						placeholder="' . esc_attr__('ex: 841234567', 'mpesa-wp-plugin') . '"
					>
				</div>
				<div class="clear"></div>';

			echo '<div class="clear"></div></fieldset>';
		}

		public function validate_fields() {
			session_start();
			//validate currency
			if ('MZN' != get_woocommerce_currency()) {
				wc_add_notice(
					__('Currency not supported!', 'mpesa-wp-plugin'),
					'error'
				);
				return false;
			}
			//validate  phone
			$number = $this->wc_mpesa_validate_number($_POST['wc_mpesa_number']);

			if (!$number) {
				wc_add_notice(
					__('Phone number is required!', 'mpesa-wp-plugin'),
					'error'
				);
				return false;
			}

			//save phone to use on payment screen and new transactions
			$_SESSION['wc_mpesa_number'] = $number;

			return true;
		}

		public function wc_mpesa_validate_number($number) {
			$number = filter_var($number, FILTER_VALIDATE_INT);
			//validade mpesa numbers to only accept 84 and 85 prefix ex: 84 8283607
			if (
				!isset($number) ||
				strlen($number) != 9 ||
				!preg_match('/^8[4|5][0-9]{7}$/', $number)
			) {

				wc_add_notice(__('Phone number is incorrect!', 'mpesa-wp-plugin'), 'error');
				return false;
			}
			return $number;
		}

		/*
			 * We're processing the payments here
			 */
		public function process_payment($order_id) {
			session_start();
			$order = new WC_Order($order_id);

			if (isset($_SESSION['wc_mpesa_number'])) {
				$number = $this->wc_mpesa_validate_number($_SESSION['wc_mpesa_number']);
			} else {
				$number = false;
			}

			//Initialize API
			$client = new Client([
				'apiKey' => $this->api_key,             // API Key
				'publicKey' => $this->public_key,          // Public Key
				'serviceProviderCode' => $this->service_provider, // input_ServiceProviderCode
				'debugging' => false,
				'environment' => 'yes' != $this->test ?? Environment::PRODUCTION
			]);

			if ($order_id && $number != false) {
				$amount = $order->get_total();
				$reference = $this->generate_reference_id($order_id);
				$number = "258${number}";



				try {
					$paymentData = [
						'from' => $number,
						'reference' => $reference,
						'transaction' => $order_id,
						'amount' => $amount
					];
					$result = $client->receive($paymentData);
				} catch (\Exception $e) {
					if (WP_DEBUG) {
						$error = $e->getMessage();
						wc_add_notice(
							"$error",
							'error'
						);
					}
				}

				if ($result->success) {
					// Mark as paid
					$order->payment_complete();
					// Reduce stock levels
					$order->reduce_order_stock();

					// some notes to customer (replace true with false to make it private)
					$order->add_order_note(
						__('Your order is paid! Thank you!', 'mpesa-wp-plugin'),
						true
					);
					WC()->cart->empty_cart();

					return array(
						'result' => 'success',
						'redirect' => $this->get_return_url($order)
					);
				} else {
					wc_add_notice(
						__(
							'Unfortunately has been an error processing your payment. Please, try again.',
							'mpesa-wp-plugin'
						),
						'error'
					);
					if (WP_DEBUG) {
						$code = $result->data['code'];
						$description = $result->data['description'];
						wc_add_notice(
							$code . ': ' . $description,
							'error'
						);
					}
					return;
				}
			}
		}

		function generate_reference_id($order_id) {
			//generate uniq reference_id
			return substr($order_id . bin2hex(random_bytes(5)), 0, 10);
		}
	}
}
