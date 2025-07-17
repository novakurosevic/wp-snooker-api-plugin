<?php
/*
 * This file is part of the Snooker API Plugin, distributed under the terms of the
 * GNU General Public License v2 or later. For full license text, see the LICENSE.md file.
 *
 * @package    SnookerApiPlugin
 * @author     Novak Urošević
 * @license    GPL-2.0-or-later
 * @link       https://github.com/novakurosevic/wp-snooker-api-plugin
 */

require_once __DIR__ . '/SnookerOrgApiClient.php';

class AdminPage
{
	public static function init()
	{
		add_menu_page(
			'Snooker.org API',
			'Snooker.org API',
			'manage_options',
			'snooker-org-api',
			[self::class, 'render'],
			'dashicons-chart-line'
		);
	}

	public static function render()
	{
		if (isset($_POST['snooker_run_cron'])) {
			$snooker_org_api_client = new SnookerOrgApiClient();
            $snooker_org_api_client->cron_events_all();

			echo '<div class="updated"><p>Cron job is started manually.</p></div>';
		}

		// Handle form submit
		if (isset($_POST['snooker_org_clear_cache'])) {
			SnookerOrgCache::clear_all_cache();
			echo '<div class="updated"><p>Cache Cleared.</p></div>';
		}

		if (isset($_POST['snooker_org_api_key'])) {
			$sanitized = sanitize_text_field($_POST['snooker_org_api_key']);
            $sanitized = self::clearApiHeaderValue($sanitized);
			update_option('snooker_org_api_key', $sanitized);
			echo '<div class="updated"><p>Header Value (X-Requested-By) Saved.</p></div>';
		}

		$api_key = esc_attr(get_option('snooker_org_api_key'));

		?>
		<div class="wrap">
			<h1>Snooker API Plugin</h1>

			<form method="post">
				<h2>Header Value (X-Requested-By)</h2>
                <p>
                    You can provide this value from snooker.org, contact webmaster@snooker.org about this header value.
                </p>
				<p>
					<input type="text" name="snooker_org_api_key" placeholder="Header Value (X-Requested-By)" value="<?= $api_key ?>" size="50" />
				</p>
				<p>
					<input type="submit" class="button button-primary" value="Save Header Value">
				</p>
			</form>

			<hr>

			<form method="post">
				<h2>Start cron job manually</h2>
				<p>
					<input type="submit" name="snooker_run_cron" class="button button-primary" value="Start Cron">
				</p>
			</form>

			<hr>

			<form method="post">
				<h2>Caching</h2>
				<p>
					<input type="submit" name="snooker_org_clear_cache" class="button button-secondary" value="Clear Cache">
				</p>
			</form>

		</div>
		<?php
	}

	/**
	 * Allows only capital letters, small letters and numbers
	 */
	public static function clearApiHeaderValue(string $input): string
	{
		return preg_replace('/[^a-zA-Z0-9]/', '', $input);
	}


}
