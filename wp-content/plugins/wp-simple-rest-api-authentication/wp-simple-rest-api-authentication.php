<?php
/**
 * Plugin Name: Simple REST API Authentication with WooCommerce Credentials
 * Plugin URI: https://1teamsoftware.com/product/wordpress-simple-rest-api-authentication/
 * Description: Simple REST API Authentication plugin for WordPress offers seamless integration with external apps, debugging capabilities, and flexibility to enable/disable SSL requirement using WooCommerce REST API credentials for WordPress REST API.
 * Version: 1.0.7
 * Tested up to: 6.8
 * Requires PHP: 7.3
 * Author: OneTeamSoftware
 * Author URI: http://oneteamsoftware.com/
 * Developer: OneTeamSoftware
 * Developer URI: http://oneteamsoftware.com/
 * Text Domain: wp-simple-rest-api-authentication
 * Domain Path: /languages
 *
 * Copyright: © 2025 FlexRC, Canada.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

declare(strict_types=1);

namespace OneTeamSoftware\WP\SimpleRestApiAuthentication;

if (file_exists(__DIR__ . '/src/autoloader.php') && file_exists(__DIR__ . '/includes/AutoLoader/AutoLoader.php')) {
	include_once __DIR__ . '/includes/AutoLoader/AutoLoader.php';
	include_once __DIR__ . '/src/autoloader.php';
}

if (file_exists(__DIR__ . '/includes/autoloader.php')) {
	include_once __DIR__ . '/includes/autoloader.php';
}

if (class_exists(__NAMESPACE__ . '\\Plugin')) {
	(new Plugin(
		__FILE__,
		__('Simple REST API Authentication for WordPress', 'wp-simple-rest-api-authentication'),
		sprintf(
			'<div class="oneteamsoftware notice notice-info inline"><p><strong>%s</strong> - %s</p><li><a href="%s" target="_blank">%s</a><br/><li><a href="%s" target="_blank">%s</a><p></p></div>',
			__('Simple REST API Authentication for WordPress', 'wp-simple-rest-api-authentication'),
			__('Enables Basic Authentication to WordPress REST API using WooCommerce API credentials.', 'wp-simple-rest-api-authentication'),
			'https://1teamsoftware.com/contact-us/',
			__('Do you have any questions or requests?', 'wp-simple-rest-api-authentication'),
			'https://wordpress.org/plugins/wp-simple-rest-api-authentication/',
			__('Do you like our plugin and can recommend it to others?', 'wp-simple-rest-api-authentication')
		),
		'1.0.7'
	)
	)->register();
} else if (is_admin()) {
	add_action(
		'admin_notices',
		function () {
			echo sprintf(
				'<div class="oneteamsoftware notice notice-error error"><p><strong>%s</strong> %s %s <a href="%s" target="_blank">%s</a> %s</p></div>',
				__('Simple REST API Authentication for WordPress', 'wp-simple-rest-api-authentication'),
				__('plugin can not be loaded.', 'wp-simple-rest-api-authentication'),
				__('Please contact', 'wp-simple-rest-api-authentication'),
				'https://1teamsoftware.com/contact-us/',
				__('1TeamSoftware support', 'wp-simple-rest-api-authentication'),
				__('for assistance.', 'wp-simple-rest-api-authentication')
			);
		}
	);
}
