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

class SnookerOrgCache
{
	protected static array $cache_keys = [
		'snooker_org_events_in_seasons',
		'snooker_org_all_players',
		'snooker_org_maximum_number_of_frames',
		'snooker_org_results_in_last_few_days',
		'snooker_org_results_ongoing_matches',
		'snooker_org_results_upcoming_matches',
		'snooker_org_plugin_output'
	];

	public static function remember(string $key, int $seconds, $callback)
	{
		$data = get_transient($key);
		if ($data === false) {
			$data = call_user_func($callback);
			set_transient($key, $data, $seconds);
		}
		return $data;
	}

	public static function get(string $key)
	{
		return get_transient($key);
	}

	public static function set(string $key, $value, int $seconds): void
	{
		set_transient($key, $value, $seconds);
	}

	public static function update(string $key, string $value, int $seconds): void
	{
		set_transient($key, $value, $seconds);
	}

	public static function has(string $key): bool
	{
		return get_transient($key) !== false;
	}

	public static function forget(string $key): void
	{
		delete_transient($key);
	}

	public static function clear_all_cache(): void
	{
		foreach (self::$cache_keys as $cache_key) {
			self::forget($cache_key);
		}
	}
}
