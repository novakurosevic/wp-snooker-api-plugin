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

	public static function render() {
		// Check and handle the manual cron job form submission with nonce verification
		if (
			isset($_POST['snooker_run_cron']) &&
			isset($_POST['snooker_run_cron_nonce']) &&
			wp_verify_nonce(
				sanitize_text_field(wp_unslash($_POST['snooker_run_cron_nonce'])),
				'snooker_run_cron_action'
			)
		) {
			$snooker_org_api_client = new SnookerOrgApiClient();
			$snooker_org_api_client->cron_events_all();

			echo '<div class="updated"><p>' . esc_html__('Cron job is started manually.', 'snooker-org-api') . '</p></div>';
		}

		// Check and handle the clear cache form submission with nonce verification
		if (
			isset($_POST['snooker_org_clear_cache']) &&
			isset($_POST['snooker_org_clear_cache_nonce']) &&
			wp_verify_nonce(
				sanitize_text_field(wp_unslash($_POST['snooker_org_clear_cache_nonce'])),
				'snooker_org_clear_cache_action'
			)
		) {
			SnookerOrgCache::clear_all_cache();
			echo '<div class="updated"><p>' . esc_html__('Cache Cleared.', 'snooker-org-api') . '</p></div>';
		}

		// Check and handle the API key form submission with nonce verification
		if (
			isset($_POST['snooker_org_api_key']) &&
			isset($_POST['snooker_org_api_key_nonce']) &&
			wp_verify_nonce(
				sanitize_text_field(wp_unslash($_POST['snooker_org_api_key_nonce'])),
				'snooker_org_api_key_action'
			)
		) {
			// Sanitize and clean the API key before saving
			$sanitized = sanitize_text_field(wp_unslash($_POST['snooker_org_api_key']));
			$sanitized = self::clearApiHeaderValue($sanitized);
			update_option('snooker_org_api_key', $sanitized);

			echo '<div class="updated"><p>' . esc_html__('Header Value (X-Requested-By) Saved.', 'snooker-org-api') . '</p></div>';
		}

		// Retrieve and escape the saved API key for display in the input field
		$api_key = esc_attr(get_option('snooker_org_api_key'));

		?>
        <div class="wrap">
            <h1><?php echo esc_html__('Snooker API Plugin', 'snooker-org-api'); ?></h1>

            <form method="post">
				<?php wp_nonce_field('snooker_org_api_key_action', 'snooker_org_api_key_nonce'); ?>
                <h2><?php echo esc_html__('Header Value (X-Requested-By)', 'snooker-org-api'); ?></h2>
                <p>
					<?php echo esc_html__('You can provide this value from snooker.org, contact webmaster@snooker.org about this header value.', 'snooker-org-api'); ?>
                </p>
                <p>
                    <input type="text" name="snooker_org_api_key" placeholder="<?php echo esc_attr__('Header Value (X-Requested-By)', 'snooker-org-api'); ?>" value="<?php echo esc_attr( $api_key ); ?>" size="50" />
                </p>
                <p>
                    <input type="submit" class="button button-primary" value="<?php echo esc_attr__('Save Header Value', 'snooker-org-api'); ?>">
                </p>
            </form>

            <hr>

            <form method="post">
				<?php wp_nonce_field('snooker_run_cron_action', 'snooker_run_cron_nonce'); ?>
                <h2><?php echo esc_html__('Start cron job manually', 'snooker-org-api'); ?></h2>
                <p>
                    <input type="submit" name="snooker_run_cron" class="button button-primary" value="<?php echo esc_attr__('Start Cron', 'snooker-org-api'); ?>">
                </p>
            </form>

            <hr>

            <form method="post">
				<?php wp_nonce_field('snooker_org_clear_cache_action', 'snooker_org_clear_cache_nonce'); ?>
                <h2><?php echo esc_html__('Caching', 'snooker-org-api'); ?></h2>
                <p>
                    <input type="submit" name="snooker_org_clear_cache" class="button button-secondary" value="<?php echo esc_attr__('Clear Cache', 'snooker-org-api'); ?>">
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
