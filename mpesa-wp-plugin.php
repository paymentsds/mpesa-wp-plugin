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

use Paymentsds\MPesa\Client;


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
	require 'vendor/autoload.php';

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

			//add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
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
					'default' => __('Pay via mpesa', 'mpesa-wp-plugin')
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
		 * Custom CSS and JS
		 */
		public function payment_scripts() {
			if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
				return;
			}

			if ('no' == $this->enabled) {
				return;
			}

			if (empty($this->api_key) || empty($this->public_key)) {
				return;
			}

			wp_enqueue_script(
				'payment',
				plugin_dir_url(__FILE__) . '/public/js/main.js',
				array(),
				false,
				true
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
					$this->description  = trim($this->description);
				}

				echo wpautop(wp_kses_post($this->description));
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
						type="tel"
						autocomplete="off"
						placeholder="' . esc_attr__('ex: 84 123 4567', 'mpesa-wp-plugin') . '"
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
			$number = $this->wc_mpesa_validate_number($_SESSION['wc_mpesa_number']);

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

			if ($order_id && $number != false) {
				$amount = $order->get_total();
				$reference_id = $this->generate_reference_id($order_id);
				$number = "258${number}";

				$result = $this -> make_payment($number, $reference_id, $order_id, $amount);

				if ($result) {
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
						__('Please try again. RESULT: ' . $result->data['code'], 'mpesa-wp-plugin'),
						'error'
					);
					return;
				}
			}
		}

		public function make_payment($number, $reference_id, $order_id, $amount) {
			$client = new Client([
				'apiKey' => $this->api_key,
				'publicKey' => $this->public_key,
				'serviceProviderCode' => $this->service_provider,
				'verifySSL' => false,
				'Origin' => "developer.mpesa.vm.co.mz"
			]);

			$paymentData = [
				'from' => $number,
				'reference' => $reference_id,
				'transaction' => $order_id,
				'amount' => $amount
			];

				$result = $client->receive($paymentData);

				if($result->success) {
					return true;
				} else {
					return false;
				}

		}

		function generate_reference_id($order_id) {
			//generate uniq reference_id
			return substr($order_id . bin2hex(random_bytes(5)), 0, 10);
		}

		/*
			 * The webhook
			 */
		public function webhook() {
		}
	}
}
