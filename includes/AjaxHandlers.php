<?php
/*
 * This file is part of the Snooker Org API plugin, distributed under the terms of the
 * GNU General Public License v2 or later. For full license text, see the LICENSE.md file.
 *
 * @package    SnookerApiPlugin
 * @author     Novak Urošević
 * @license    GPL-2.0-or-later
 * @link       https://github.com/novakurosevic/wp-snooker-api-plugin
 */
if (!defined('ABSPATH')) {
	exit;
}

// Ajax handler for previous matches
add_action('wp_ajax_load_previous_matches', 'load_previous_matches_callback');
add_action('wp_ajax_nopriv_load_previous_matches', 'load_previous_matches_callback');
function load_previous_matches_callback() {
	// Verify the nonce for security
	check_ajax_referer('snooker_org_nonce', 'security');

	$snooker_org_api_client = new SnookerOrgApiClient();
	$last_few_days_html = $snooker_org_api_client->generateHtmlOutputForLastFewDays();

	echo wp_kses_post($last_few_days_html);

	wp_die();
}

// Ajax handler for current matches
add_action('wp_ajax_load_current_matches', 'load_current_matches_callback');
add_action('wp_ajax_nopriv_load_current_matches', 'load_current_matches_callback');
function load_current_matches_callback() {
	// Verify the nonce for security
	check_ajax_referer('snooker_org_nonce', 'security');

	$snooker_org_api_client = new SnookerOrgApiClient();
	$ongoing_matches_html = $snooker_org_api_client->generateHtmlOutputOngoingMatches();

	echo wp_kses_post($ongoing_matches_html);

	wp_die();
}

// Ajax handler for upcoming matches
add_action('wp_ajax_load_upcoming_matches', 'load_upcoming_matches_callback');
add_action('wp_ajax_nopriv_load_upcoming_matches', 'load_upcoming_matches_callback');
function load_upcoming_matches_callback() {
	// Verify the nonce for security
	check_ajax_referer('snooker_org_nonce', 'security');

	$snooker_org_api_client = new SnookerOrgApiClient();
	$upcoming_matches_html = $snooker_org_api_client->generateHtmlOutputForUpcomingMatches();

	echo wp_kses_post($upcoming_matches_html);

	wp_die();
}
