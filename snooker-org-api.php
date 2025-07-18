<?php
/*
Plugin Name: Snooker Org API
Description: Fetch data from snooker.org API and cache it.
Version: 1.0
Author: Novak Urošević
Author URI: https://www.linkedin.com/in/novak-urosevic/
Text Domain: snooker-org-api
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

defined('ABSPATH') || exit;

// Autoload
require_once plugin_dir_path(__FILE__) . 'includes/SnookerOrgCache.php';
require_once plugin_dir_path(__FILE__) . 'includes/SnookerOrgApiClient.php';
require_once plugin_dir_path(__FILE__) . 'includes/AdminPage.php';
require_once plugin_dir_path(__FILE__) . 'includes/AjaxHandlers.php';

// Add cron event on 10 minutes and on 7 days
add_filter('cron_schedules', function ($schedules) {
	$schedules['every_10_minutes'] = [
		'interval' => 10 * 60,
		'display'  => __('Every 10 Minutes', 'snooker-org-api'),
	];

	$schedules['weekly'] = [
		'interval' => 7 * 24 * 60 * 60,
		'display'  => __('Once Weekly', 'snooker-org-api'),
	];

	return $schedules;
});

// Admin page start
add_action('admin_menu', ['AdminPage', 'init']);

// Cron event
add_action('snooker_org_cron_event_10_minutes', [ 'SnookerOrgApiClient', 'cron_event_10_minutes']);



// Add styling and JS
function snooker_enqueue_assets() {
	$plugin_data = get_file_data(__FILE__, ['Version' => 'Version'], false);
	$version = $plugin_data['Version'];

	wp_enqueue_style('snooker-style', plugin_dir_url( __FILE__ ) . 'assets/css/snooker-org-style.css', [], $version );
	wp_enqueue_script('snooker-script', plugin_dir_url( __FILE__ ) . 'assets/js/snooker-org.js', [], $version, true);

	// Adding AJAX URL-a i nonce-a
	wp_localize_script('snooker-script', 'snooker_ajax_object', [
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce'    => wp_create_nonce('snooker_org_nonce')
	]);
}

add_action('wp_enqueue_scripts', 'snooker_enqueue_assets');

add_action('snooker_cron_10min_event', function () {
	// Do this code once every 10 minutes
	try {
		$snooker_org_api_client = new SnookerOrgApiClient();
		$snooker_org_api_client->cron_event_10_minutes();
	} catch (Exception $e) {
		snooker_log_error('[Snooker 10 Minutes Cron Error] ' . $e->getMessage());
	}
});

add_action('snooker_weekly_cron_event', function () {
	// Do this code once per week
	try {
		$snooker_org_api_client = new SnookerOrgApiClient();
		$snooker_org_api_client->cron_event_weekly();
	} catch (Exception $e) {
		snooker_log_error('[Snooker Weekly Cron Error] ' . $e->getMessage());
	}

});

// Plugin activation
register_activation_hook(__FILE__, function () {
	if (!wp_next_scheduled('snooker_cron_10min_event')) {
		wp_schedule_event(time(), 'every_10_minutes', 'snooker_cron_10min_event');
	}

	if (!wp_next_scheduled('snooker_weekly_cron_event')) {
		wp_schedule_event(time(), 'weekly', 'snooker_weekly_cron_event');
	}
});

// Plugin deactivation
register_deactivation_hook(__FILE__, function () {
	wp_clear_scheduled_hook('snooker_cron_10min_event');
	wp_clear_scheduled_hook('snooker_weekly_cron_event');
});


// Shortcode for presenting
add_shortcode('snooker_org_plugin', 'snooker_org_plugin_shortcode');

function snooker_org_plugin_shortcode($atts = [], $content = null) {

	$cache_key = 'snooker_org_plugin_output';
	$cached_output = get_transient($cache_key);

	if ($cached_output !== false) {
		return $cached_output;
	}

	$api_key = get_option('snooker_org_api_key');

	if (empty($api_key)) {
		return '<div class="snooker-org-error">Snooker.org Header Value (X-Requested-By) is not defined.
											   Please configure the plugin settings.</div>';
	}

	$snooker_org_api_client = new SnookerOrgApiClient();
	$output_data = $snooker_org_api_client->generateOutputHtml();

	if (empty($output_data)) {
		$output_data = '<h3>There is no data from Snooker.org.</h3>';
	}

	// Cache for 10 minutes
	set_transient($cache_key, $output_data, 10 * MINUTE_IN_SECONDS);

	return $output_data;
}

function snooker_log_error($message) {
	if ( defined('WP_DEBUG') && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log($message);
	}
}

