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
	- [How to contribute](#how-to-contribute)
		- [Developing with docker](#developing-with-docker)
	- [License](#license)

## Features <a name="features"></a>

- Receive money from a mobile account to a business account

## Prerequisites <a name="prerequisites"></a>

To use this plugin it is necessary to:

- Have Woocommerce installed.
- Create an account into [M-pesa developer portal](https://developer.mpesa.vm.co.mz/).
- Make sure that the port 18352 is opened in your server.

## Installation<a name="installation"></a>

### Auto installation <a name="installation-auto"></a>

- Go to _Plugins > Add new_ in your dashboard.
- Search for **PaymentsDS - Mpesa Payment Gateway for WooCommerce**.
- Click _Install now_ on **PaymentsDS - Mpesa Payment Gateway for WooCommerce** to install the plug-in and then click _ativate_.

### Manual installation <a name="installation-manual"></a>

- First, you need to download the latest version of the plugin from the [releases page]https://github.com/paymentsds/mpesa-wp-plugin/releases).
- Upload the plugin folder to your WordPress installation's wp-content/plugins/ directory.
- Activate the plugin from the Plugins menu within the WordPress admin.

## Configuration <a name="configuration"></a>

- After a successfully installation and activation of the plugin, go to Woocommerce > Setting > Payments tab, its supposed to appear the M-pesa payment method listed. Activate it and then click on "Manage".
- Fill out the configuration fields with the information found in the [M-pesa developer portal](https://developer.mpesa.vm.co.mz/).

## How to contribute

All the contributions are welcome, but you should follow this steps to contribute:

- Open an issue describing your feature suggestion or bug fix
- fork this repo
- Create a new branch with your feature

### Developing with docker

- Run the containers: `docker-compose up`
- Access the wp page you just created on http://localhost:8080
- Access the admin page and activate the plugins (mpesa-wp-plugin and woocommerce)
- Now make your changes to the plugin and test if it works.

- Commit your changes
- Open a PR with these changes

After your PR has been merged, you can delete your branch.

## License

This project is under the GNU General Public License v3.0 License. check the file [LICENSE](https://github.com/paymentsds/mpesa-wp-plugin/blob/main/LICENSE) for details.
