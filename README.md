# M-Pesa Plugin for WordPress

M-Pesa Plugin for WordPress aims to help businesses integrating payments using M-pesa to their Wordpress website/application that has Woocommerce plugin installed.

## Contents
- [M-Pesa Plugin for WordPress](#m-pesa-plugin-for-wordpress)
	- [Contents](#contents)
	- [Features <a name="features"></a>](#features-)
	- [Prerequisites <a name="prerequisites"></a>](#prerequisites-)
	- [Installation<a name="installation"></a>](#installation)
		- [Auto installation <a name="installation-auto"></a>](#auto-installation-)
		- [Manual installation <a name="installation-manual"></a>](#manual-installation-)
	- [Configuration <a name="configuration"></a>](#configuration-)
## Features <a name="features"></a>
* Receive money from a mobile account to a business account
## Prerequisites <a name="prerequisites"></a>
To use this plugin it is necessary to:
* Have Woocommerce installed.
* Create an account into [M-pesa developer portal](https://developer.mpesa.vm.co.mz/).
* Make sure that the port 18352 is opened in your server.
## Installation<a name="installation"></a>

### Auto installation <a name="installation-auto"></a>
* Go to *Plugins > Add new* in your dashboard.
* Search for **PaymentsDS - Mpesa Payment Gateway for WooCommerce**.
* Click *Install now* on **PaymentsDS - Mpesa Payment Gateway for WooCommerce** to install the plug-in and then click *ativate*.
### Manual installation <a name="installation-manual"></a>
* First, you need to download the latest version of the plugin from the [releases page]https://github.com/paymentsds/mpesa-wp-plugin/releases).
* Upload the plugin folder to your WordPress installation's wp-content/plugins/ directory.
* Activate the plugin from the Plugins menu within the WordPress admin.
## Configuration <a name="configuration"></a>
* After a successfully installation and activation of the plugin, go to Woocommerce > Setting > Payments tab, its supposed to appear the M-pesa payment method listed. Activate it and then click on "Manage".
* Fill out the configuration fields with the information found in the [M-pesa developer portal](https://developer.mpesa.vm.co.mz/).
