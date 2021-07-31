<?php

/**
 * Plugin Name: 		PaymentsDS - Mpesa Payment Gateway for WooCommerce
 * Plugin URI: 			https://github.com/paymentsds/mpesa-wp-plugin
 * Description: 		Receive payments directly to your store through the Vodacom Mozambique M-Pesa.
 * Author: 					PaymentsDS
 * Author URI: 			https://developers.paymentsds.org/
 * Version: 				0.1.1
 * Text Domain: 		mpesa-wp-plugin
 * Domain Path: 		/languages
 *
 * Copyright: 			© 2021 PaymentsDS. (https://developers.paymentsds.org/)
 *
 * License: 				GNU General Public License
 * License URI: 		http://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @author    			PaymentsDS
 * @copyright 			Copyright © 2021 PaymentsDS.
 * @license   			http://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License
 *
 */

if (!defined('WPINC')) {
	wp_die();
}
require 'vendor/autoload.php';

use Paymentsds\MPesa\Client;
use Paymentsds\MPesa\Environment;

if (!defined('MPESA_WP_PLUGIN_VERSION')) {
	define('MPESA_WP_PLUGIN_VERSION', '0.1.1');
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
		// $wpdb->query("DROP TABLE IF EXISTS $table_name");

		update_option('mpesa_wp_version');
	}
	// Creating transactions table
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  order_id varchar(9) NOT NULL UNIQUE,
  phone varchar(12) NOT NULL ,
  PRIMARY KEY  (id)
) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
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
				'products',
				'refunds'
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

			// Actions
			add_action(
				'woocommerce_update_options_payment_gateways_' . $this->id,
				array($this, 'process_admin_options')
			);

			add_action(
				'woocommerce_receipt_' . $this->id,
				array($this, 'payment_form_html')
			);
			add_action(
				'wp_enqueue_scripts',
				array($this, 'payment_scripts')
			);
			add_action(
				'woocommerce_api_process_action',
				array($this, 'process_action')
			);

			/**
			 * Set a minimum order amount for checkout
			 */
			add_action('woocommerce_checkout_process', 'wc_minimum_order_amount');
			add_action('woocommerce_before_cart', 'wc_minimum_order_amount');
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
						value="' . esc_attr($number) . '"
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

		function payment_scripts() {
			if (!is_checkout_pay_page()) {
				return;
			}
			if ('no' == $this->enabled) {
				return;
			}
			// Load only on specified pages

			wp_enqueue_script(
				'payment',
				plugin_dir_url(__FILE__) . '/scripts/main.js',
				array(),
				false,
				true
			);

			wp_localize_script('payment', 'payment_text', [
				'status' => [
					'intro'  => [
						'title' => __('Payment Information', 'wc-mpesa-payment-gateway'),
						'description'  => __(
							'<p>
							Thank you for your order, please click the button bellow to proceed.
							</p>',
							'wc-mpesa-payment-gateway'
						),
					],
					'requested' => [
						'title' => __('Payment request sent!', 'wc-mpesa-payment-gateway'),
						'description' => __(
							'<p>You will receive a pop-up on the phone requesting payment confirmation, please enter your PIN code to confirm the payment.</p>',
							'wc-mpesa-payment-gateway'
						)
					],
					'received' => [
						'title' => __('Payment received!', 'wc-mpesa-payment-gateway'),
						'description' => __('Your payment has been received and your order will be processed soon.', 'wc-mpesa-payment-gateway')
					],
					'timeout' => [
						'title' => __('Payment timeout exceeded!', 'wc-mpesa-payment-gateway'),
						'description' => __('Use your browser\'s back button and try again.', 'wc-mpesa-payment-gateway')
					],
					'failed' => [
						'title' => __('Payment failed!', 'wc-mpesa-payment-gateway'),
						'description' => __('Try again or use your browser\'s back button to change the number.', 'wc-mpesa-payment-gateway')
					],

				],
				'buttons' => [
					'pay' => __('Pay', 'wc-mpesa-payment-gateway'),
					'back' => __('Back', 'wc-mpesa-payment-gateway'),
				]
			]);
			wp_enqueue_style(
				'style',
				plugin_dir_url(__FILE__) . '/styles/style.css',
				false,
				false,
				'all'
			);
		}

		function payment_form_html($order_id) {
			// modify post object here
			$order = new WC_Order($order_id);
			$return_url = $this->get_return_url($order);
			$data = json_encode([
				'order_id' => $order_id,
				'return_url' => $return_url
			]);
			$html_output = "<div class='payment-container' id='app'>
            <div>
              <h4 class='payment-title' v-cloak>{{status.title}}</h4>
              <div v-if='error' class='payment-error' role='error'>{{error}}</div>
              <div class='payment-description' role='alert' v-html='status.description'></div>
            </div>
						<div class='btn-container' >
            <button class='payment-btn' v-bind='{ btnDisabled }' v-on:click='pay($data)'>" . __('Pay', 'wc-mpesa-payment-gateway') . "</button>
						<button
							class='back-btn'
							type='button'
							onClick='history.back()';
						>". __('Back', 'wc-mpesa-payment-gateway') ."</button>
						</div>
						</div>";
			echo $html_output;
		}

		/*
			 * We're processing the payments here
			 */
		public function process_payment($order_id) {
			session_start();
			$order = new WC_Order($order_id);
			$checkout_url = $order->get_checkout_payment_url(true);

			return array(
				'result' => 'success',
				'redirect' => $checkout_url
			);
		}

		function process_action() {
			session_start();

			if (isset($_SESSION['wc_mpesa_number'])) {
				$number = $this->wc_mpesa_validate_number($_SESSION['wc_mpesa_number']);
			} else {
				$number = false;
			}
			$response = [];

			//Initialize API
			$client = new Client([
				'apiKey' => $this->api_key,             // API Key
				'publicKey' => $this->public_key,          // Public Key
				'serviceProviderCode' => $this->service_provider, // input_ServiceProviderCode
				'debugging' => false,
				'environment' => 'yes' != $this->test ?? Environment::PRODUCTION
			]);

			$order = new WC_Order(filter_input(
				INPUT_POST,
				'order_id',
				FILTER_VALIDATE_INT
			));

			$order_id = $order->get_id();

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
					$response['status'] = 'failed';
					if (WP_DEBUG) {
						$response['error_message'] = $e->getMessage();
						$response['raw'] =  $result->response;
						$response['request'] = [
							'order_id' => $order_id,
							'phone' => $number,
							'amount' => $amount,
							'reference_id' => $reference,
							'service_provider' => $this->service_provider,
						];
					}
					return wp_send_json_error($response);
				}

				if ('yes' == $this->test) {
					$response['raw'] =  $result->response;
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

					// Storing payment data
					global $wpdb;
					$table_name = $wpdb->prefix . "mpesa_wp_transactions";
					$wpdb->insert(
						$table_name,
						array(
							'order_id' => $order_id,
							'phone' => $number,
						)
					);

					$response['status'] = 'success';
				} else {
					$response['status'] = 'failed';
					$response['error_message'] = $result->data['description'];

					$order->update_status(
						'failed',
						__(
							'Payment failed',
							'wc-mpesa-payment-gateway'
						)
					);
				}
			}

			wp_send_json($response);
		}

		function generate_reference_id($order_id) {
			//generate uniq reference_id
			return substr($order_id . bin2hex(random_bytes(5)), 0, 10);
		}

		function wc_minimum_order_amount() {
			// Set this variable to specify a minimum order value
			$minimum = 1;

			if (WC()->cart->total < $minimum) {

				if (is_cart()) {

					wc_print_notice(
						sprintf(
							'Your current order total is %s — you must have an order with a minimum of %s to place your order ',
							wc_price(WC()->cart->total),
							wc_price($minimum)
						),
						'error'
					);
				} else {

					wc_add_notice(
						sprintf(
							'Your current order total is %s — you must have an order with a minimum of %s to place your order',
							wc_price(WC()->cart->total),
							wc_price($minimum)
						),
						'error'
					);
				}
			}
		}

		// Processing refunds
		public function process_refund($order_id, $amount = null, $reason = "") {
			$order = wc_get_order($order_id);

			if (!is_a($order, 'WC_Order')) {
				return false;
			}

			if ('refunded' == $order->get_status()) {
				return false;
			}

			global $wpdb;
			$table_name = $wpdb->prefix . "mpesa_wp_transactions";

			$transaction = $wpdb->get_row("SELECT order_id, phone FROM $table_name WHERE order_id = $order_id LIMIT 0,1");

			if (strlen($transaction->phone) != 12) {
				return false;
			}
			$reference = $this->generate_reference_id($order_id);

			//Initialize API
			$client = new Client([
				'apiKey' => $this->api_key,
				'publicKey' => $this->public_key,
				'serviceProviderCode' => $this->service_provider,
				'debugging' => false,
				'environment' => 'yes' != $this->test ?? Environment::PRODUCTION
			]);

			try {
				$paymentData = [
					'to' => $transaction->phone,
					'reference' => $reference,
					'transaction' => $order_id,
					'amount' => $amount
				];
				$result = $client->send($paymentData);
			} catch (\Exception $e) {
				return false;
			}

			if ($result->success) {
				return true;
			} else {
				return false;
			}
		}
	} // end of class Mpesa_WP_Plugin
}
