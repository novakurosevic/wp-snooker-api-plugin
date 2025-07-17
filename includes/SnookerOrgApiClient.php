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
if (!defined('ABSPATH')) {
	exit;
}

class SnookerOrgApiClient
{
	protected const EVERY_10_MINUTES = 600;
	protected const EVERY_7_DAYS = 86400;

	public function cron_event_10_minutes(): void {
		$this->numberOfFramesData();
		$this->getResultsForLastFewDays();
		$this->getResultsForOngoingMatches();
		$this->getResultsForUpcomingMatches();
	}

	public function cron_event_weekly(): void {
		SnookerOrgCache::clear_all_cache();
		$this->getEventsInSeason();
		$this->getAllPlayers();
	}

	public function cron_events_all(): void {
		// Weekly must be first since it's data is used for 10 minutes cron.
		$this->cron_event_weekly();
		$this->cron_event_10_minutes();
	}

	protected function getDataFromSnookerOrgAPI($url_params = []): array {
		$api_key = get_option('snooker_org_api_key');
		if (!$api_key) {
			return [
				'errors' => 'API key is not defined.',
				'response' => null,
			];
		}

		// Check if URL parameters are provided
		if (empty($url_params)) {
			return [
				'errors' => 'API request failed: missing URL parameters.',
				'response' => null,
			];
		}

		// Build the full URL with query parameters
		$base_url = 'https://api.snooker.org/';
		$query_string = http_build_query($url_params);
		$url = $base_url . '?' . $query_string;

		$response = wp_remote_get($url, [
			'headers' => [
				'X-Requested-By' => $api_key,
				'Accept' => 'application/json',
			],
			'timeout' => 10,
		]);

		$errors = $this->check_response_code_for_errors($response);

		if ($errors !== '') {
			return [
				'errors' => $errors,
				'response' => null,
			];
		}

		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		return [
			'errors' => '',
			'response' => $data,
		];
	}

	protected function getEventsInSeason() {
		// Try to get cached data
		$cached = SnookerOrgCache::get('snooker_org_events_in_seasons');
		if ($cached !== false) {
			return $cached;
		}

		$season = $this->getCurrentYear();

		// Fetch fresh data from API
		$result = $this->getDataFromSnookerOrgAPI(
			[
				't' => '5',
				's' => $season
			]
		);

		// If no error, cache it
		if ($result['errors'] === '') {
			SnookerOrgCache::set('snooker_org_events_in_seasons', $result['response'], self::EVERY_7_DAYS);
		}

		return $result['response'];
	}

	protected function getAllPlayers() {
		// Try to get cached data
		$cached = SnookerOrgCache::get('snooker_org_all_players');
		if ($cached !== false) {
			return $cached;
		}

		// Fetch fresh data from API
		$result = $this->getDataFromSnookerOrgAPI(
			[
				't' => '10'
			]
		);

		// If no error, cache it
		if ($result['errors'] === '') {
			SnookerOrgCache::set('snooker_org_all_players', $result['response'], self::EVERY_7_DAYS);
		}

		return !empty($result['response']) ? $result['response'] : [];
	}

	protected function getResultsForLastFewDays() {
		// Try to get cached data
		$cached = SnookerOrgCache::get('snooker_org_results_in_last_few_days');
		if ($cached !== false) {
			return $cached;
		}

		// Fetch fresh data from API
		$result = $this->getDataFromSnookerOrgAPI(
			[
				't'  => '15',
				'ds' => '2',
				'tr' => 'main'
			]
		);

		$prepared_results_data = $this->processMatchesData($result);

		// If no error, cache it
		if ($result['errors'] === '') {
			SnookerOrgCache::set('snooker_org_results_in_last_few_days',
				['data' => $prepared_results_data, 'errors' => ''], self::EVERY_10_MINUTES);
		}

		return ['data' => $prepared_results_data, 'errors' => $result['errors']];

	}

	public function generateHtmlOutputForLastFewDays($with_logo = true):string {
		$data = $this->getResultsForLastFewDays();
		if($data['errors'] === '') {
			$data_html = $this->generateTabHtml($data['data']);

			if(empty($data_html)){
				$data_html = '<p class="no-matches">There were no matches in last few days. Please check back later for updates.</p>';
			}
		}else{
			$data_html = '<div class="snooker-org-error">' . $data['errors'] . '</div>';
		}

		return '<p>Finished Matches</p><div class="snooker-tournaments-wrapper">'
		       . $data_html . '</div>'. $this->showSnookerOrgLogoHtml();
	}

	protected function getResultsForOngoingMatches() {
		// Try to get cached data
		$cached = SnookerOrgCache::get('snooker_org_results_ongoing_matches');
		if ($cached !== false) {
			return $cached;
		}

		// Fetch fresh data from API
		$result = $this->getDataFromSnookerOrgAPI(
			[
				't'  => '17',
				'tr' => 'main'
			]
		);

		$prepared_results_data = $this->processMatchesData($result);

		// If no error, cache it
		if ($result['errors'] === '') {
			SnookerOrgCache::set('snooker_org_results_ongoing_matches',
				['data' => $prepared_results_data, 'errors' => ''], self::EVERY_10_MINUTES);
		}

		return ['data' => $prepared_results_data, 'errors' => $result['errors']];

	}

	public function generateHtmlOutputOngoingMatches():string {
		$data = $this->getResultsForOngoingMatches();
		if($data['errors'] === '') {
			$data_html = $this->generateTabHtml($data['data']);

			if(empty($data_html)){
				$data_html = '<p class="no-matches">There are no ongoing matches. Please check back later for updates.</p>';
			}
		}else{
			$data_html = '<div class="snooker-org-error">' . $data['errors'] . '</div>';
		}


		return '<p>Ongoing Matches</p><div class="snooker-tournaments-wrapper">' . $data_html
		       . '</div>'. $this->showSnookerOrgLogoHtml();

	}

	protected function getResultsForUpcomingMatches() {
		// Try to get cached data
		$cached = SnookerOrgCache::get('snooker_org_results_upcoming_matches');
		if ($cached !== false) {
			return $cached;
		}

		// Fetch fresh data from API
		$result = $this->getDataFromSnookerOrgAPI(
			[
				't'  => '14',
				'tr' => 'main'
			]
		);

		$prepared_results_data = $this->processMatchesData($result);

		// If no error, cache it
		if ($result['errors'] === '') {
			SnookerOrgCache::set('snooker_org_results_upcoming_matches',
				['data' => $prepared_results_data, 'errors' => ''], self::EVERY_10_MINUTES);
		}

		return ['data' => $prepared_results_data, 'errors' => $result['errors']];

	}

	public function generateHtmlOutputForUpcomingMatches():string {
		$data = $this->getResultsForUpcomingMatches();
		if($data['errors'] === '') {
			$data_html = $this->generateTabHtml($data['data']);

			if(empty($data_html)){
				$data_html = '<p class="no-matches">There are no upcoming matches at the moment. Please check back later for updates.</p>';
			}
		}else{
			$data_html = '<div class="snooker-org-error">' . $data['errors'] . '</div>';
		}

		return '<p>Upcoming Matches</p></p><div class="snooker-tournaments-wrapper">'
		       . $data_html . '</div>' . $this->showSnookerOrgLogoHtml();

	}

	protected function processMatchesData($result):array {
		$prepared_results_data = [];

		if(!empty($result['response']) && is_array($result['response'])){
			foreach ($result['response'] as $one_match) {

				if(!empty($one_match['EventID'])){
					$player_1_data = $this->getPlayerDataFromPlayerId($one_match['Player1ID']);
					$player_2_data = $this->getPlayerDataFromPlayerId($one_match['Player2ID']);

					$match_temp_data = [
						'player_1_name' => $player_1_data['full_name'],
						'player_1_score' => $one_match['Score1'],
						'player_1_country' => $player_1_data['nationality'],
						'player_1_winner' => $this->isPlayerWinner($one_match['Player1ID'], $one_match),
						'player_1_walkover' => $one_match['Walkover1'],
						'player_2_name' => $player_2_data['full_name'],
						'player_2_score' => $one_match['Score2'],
						'player_2_country' => $player_2_data['nationality'],
						'player_2_winner' => $this->isPlayerWinner($one_match['Player2ID'], $one_match),
						'player_2_walkover' => $one_match['Walkover2'],
						'maximum_number_of_frames' => $this->getMaximumNumberOfFrames($one_match['EventID']),
						'winner'
					];

					$prepared_results_data[$one_match['EventID']]['matches'][] = $match_temp_data;
				}
			}
		}

		foreach ($prepared_results_data as $event_id => $event_data) {
			if(!isset($prepared_results_data[$event_id]['event_name'])){
				$prepared_results_data[$event_id]['event_name'] = $this->getEventNameFromEventId($event_id);
			}
		}

		return $prepared_results_data;

	}

	protected function generateTabHtml($data): string {
		$output_html = '';

		if(is_array($data) && !empty($data)){
			if(count($data) > 0){
				$output_html = '<div class="snooker-tournament">';
			}

			foreach ($data as $one_event)
			{
				$output_html.= '<h2 class="tournament-title">' . $one_event['event_name'] . '</h2>';

				if(is_array($one_event['matches']) && !empty($one_event['matches'])){
					foreach ($one_event['matches'] as $one_match) {
						$output_html.=
							'<div class="snooker-match">
						<div class="player">
							<img src="' . $this->getFlagFromPlayerData($one_match['player_1_country'] ) .  '" alt="" />
							<span ' . $this->isPlayerWinnerStyle($one_match['player_1_winner']) . '>' . $one_match['player_1_name'] . '</span>
						</div>
						<div class="score">' . $one_match['player_1_score'] . ' (' . $one_match['maximum_number_of_frames'] . ') '
							. $one_match['player_2_score'] . '</div>
						<div class="player">
							<span ' . $this->isPlayerWinnerStyle($one_match['player_2_winner']) . '>' . $one_match['player_2_name'] . '</span>
							<img src="' . $this->getFlagFromPlayerData($one_match['player_2_country'] ) .  '" alt="" />
						</div>
					</div>';

						if($one_match['player_1_walkover'] || $one_match['player_2_walkover']){
							if($one_match['player_1_walkover']){
								$player_name = $one_match['player_1_name'];
							}else{
								$player_name = $one_match['player_2_name'];
							}

							$output_html.= '<div class="match-note">Note: ' . $player_name . ' lost by walkover.</div>';
						}

					}
				}

			}

			if(count($data) > 0){
				$output_html.= '</div>';
			}
		}

		return $output_html;
	}

	public function generateOutputHtml():string {
		$last_few_days_html = $this->generateHtmlOutputForLastFewDays();
		$snooker_org_logo = $this->showSnookerOrgLogoHtml();

		return <<<HTML
				<div class="snooker-org-tabs">
				    <div class="snooker-org-tab-buttons">
				        <button class="snooker-org-tab-btn active" data-target="previous">Previous</button>
				        <button class="snooker-org-tab-btn" data-target="current">Current</button>
				        <button class="snooker-org-tab-btn" data-target="upcoming">Coming</button>
				    </div>
				    <div class="snooker-org-tab-wrapper">
				        <div class="snooker-org-tab-content active" id="snooker_org_previous_tab">
				            {$last_few_days_html}
				        </div>
				        <div class="snooker-org-tab-content" id="snooker_org_current_tab">
				            <p>Ongoing Matches</p>
				            <div class="snooker-tournaments-wrapper"></div>
				            {$snooker_org_logo}
				        </div>
				        <div class="snooker-org-tab-content" id="snooker_org_upcoming_tab">
				            <p>Upcoming Matches</p>
				            <div class="snooker-tournaments-wrapper"></div>
				            {$snooker_org_logo}
				        </div>
				    </div>
				</div>
				HTML;


		return <<<HTML
				<div class="snooker-org-tabs">
				    <div class="snooker-org-tab-buttons">
				        <button class="snooker-org-tab-btn active" data-target="previous">Previous</button>
				        <button class="snooker-org-tab-btn" data-target="current">Current</button>
				        <button class="snooker-org-tab-btn" data-target="upcoming">Coming</button>
				    </div>
				    <div class="snooker-org-tab-wrapper">
				        <div class="snooker-org-tab-content active" id="snooker_org_previous_tab">
				            <p>Finished Matches</p>
				            <div class="snooker-tournaments-wrapper">
				                {$last_few_days_html}
				            </div>
				        </div>
				        <div class="snooker-org-tab-content" id="snooker_org_current_tab">
				            <p>Ongoing Matches</p>
				            <div class="snooker-tournaments-wrapper">
				                {$ongoing_matches_html}
				            </div>
				        </div>
				        <div class="snooker-org-tab-content" id="snooker_org_upcoming_tab">
				            <p>Upcoming Matches</p>
				            <div class="snooker-tournaments-wrapper">
				                {$upcoming_matches_html}
				            </div>
				        </div>
				    </div>
				</div>
				HTML;
	}


	protected function isPlayerWinner($player_id, $event_data):bool {
		if(isset($event_data['WinnerID'])){
			if($event_data['WinnerID'] == $player_id){
				return true;
			}
		}

		return false;
	}

	protected function isPlayerWinnerStyle($is_winner):string {
		return $is_winner ? 'class="winner"' : '';
	}

	protected function getMaximumNumberOfFrames($event_id): int {
		$events_with_number_of_frames_data = $this->numberOfFramesData();
		$number_of_frames = 0;

		if(is_array($events_with_number_of_frames_data) && !empty($events_with_number_of_frames_data)){
			foreach ($events_with_number_of_frames_data as $one_event){
				if(isset($one_event['EventID'])){
					if($one_event['EventID'] == $event_id){
						$number_of_frames = 2 * ( (int) $one_event['Distance']) - 1;
						break;
					}
				}
			}
		}

		if($number_of_frames < 0) return 0;

		return $number_of_frames;

	}

	protected function numberOfFramesData() {
		// Try to get cached data
		$cached = SnookerOrgCache::get('snooker_org_maximum_number_of_frames');
		if ($cached !== false) {
			return $cached;
		}

		// Fetch fresh data from API
		$result = $this->getDataFromSnookerOrgAPI(
			[
				't'  => '12'
			]
		);

		// If no error, cache it
		if ($result['errors'] === '') {
			SnookerOrgCache::set('snooker_org_maximum_number_of_frames', $result['response'], self::EVERY_10_MINUTES);
		}

		return $result['response'];

	}


	/*
	 * Helper functions
	 */
	protected function getEventNameFromEventId($event_id): string {
		$events_in_season = $this->getEventsInSeason();
		$result = '';

		if(!empty($events_in_season) && is_array($events_in_season)){
			foreach ($events_in_season as $event) {
				if(($event_id == $event['ID']) ){
					$event_name = $event['Name'] ? $event['Name'] : '';
					$result = $event_name . ' (' . $this->formatDateRange($event['StartDate'], $event['EndDate']) . ')';
				}
			}
		}

		return $result;
	}

	protected function getPlayerDataFromPlayerId($player_id): array {
		$all_players = $this->getAllPlayers();
		$full_name = '';
		$nationality = '';

		if(!empty($all_players) && is_array($all_players)){
			foreach ($all_players as $player) {
				if (isset($player['ID'])){

					if(((int) $player_id) == ((int) $player['ID'])){
						$player_first_name = !empty($player['FirstName']) ?  $player['FirstName'] : '';
						$player_middle_name = !empty($player['MiddleName'])  ?  $player['MiddleName'] : '';
						$player_last_name = !empty($player['LastName'])  ?  $player['LastName'] : '';

						if(isset($player_middle_name) && ($player_middle_name != '')){
							$full_name = $player_first_name . ' ' . $player_middle_name . ' ' . $player_last_name;
						}else{
							$full_name = $player_first_name . ' ' . $player_last_name;
						}

						if(isset($player['Nationality'])){
							$nationality = $player['Nationality'];
						}

						return [
							'full_name' => $full_name,
							'nationality' => $nationality
						];

					}
				}
			}
		}

		return [
			'full_name' => $full_name,
			'nationality' => $nationality
		];

	}

	protected function getCurrentYear(): int {
		return (int) date('Y');
	}

	/**
	 * Convert ISO Date format to custom format
	 *
	 * ISO Date example 2025-07-10T19:00:00Z
	 */
	protected function convertTime( string $isoDate, string $format = 'd-m-Y H:i', ?string $timezone = null ): string {
		try {
			$tz   = $timezone ? new DateTimeZone( $timezone ) : wp_timezone();
			$date = new DateTime( $isoDate, new DateTimeZone( 'UTC' ) );
			$date->setTimezone( $tz );
			return $date->format( $format );
		} catch ( Exception $e ) {
			return '';
		}
	}

	/**
	 * Used for tournament start and end date.
	 */
	protected function formatDateRange( string $start, string $end ): string {
		try {
			$tz     = wp_timezone();
			$startD = new DateTime( $start, $tz );
			$endD   = new DateTime( $end, $tz );

			$sameDay   = $startD->format('Y-m-d') === $endD->format('Y-m-d');
			$sameMonth = $startD->format('Y-m') === $endD->format('Y-m');
			$sameYear  = $startD->format('Y') === $endD->format('Y');

			if ( $sameDay ) {
				return $startD->format('j M Y'); // e.g. 11 Jul 2025
			}

			if ( $sameMonth && $sameYear ) {
				return sprintf(
					'%s–%s %s %s',
					$startD->format('j'),
					$endD->format('j'),
					$startD->format('M'),
					$startD->format('Y')
				);
			}

			if ( $sameYear ) {
				return sprintf(
					'%s %s – %s %s %s',
					$startD->format('j'),
					$startD->format('M'),
					$endD->format('j'),
					$endD->format('M'),
					$startD->format('Y')
				);
			}

			// Completely different years
			return sprintf(
				'%s – %s',
				$startD->format('j M Y'),
				$endD->format('j M Y')
			);

		} catch ( Exception $e ) {
			return '';
		}
	}
	protected function getFlagFromPlayerData( string $country_name ): string {
		// Convert spaces to underscores, keep capitalization
		$file_name = str_replace( ' ', '_', $country_name ) . '.png';

		// Absolute path on the server to the file
		$file_path = plugin_dir_path( dirname( __FILE__ ) ) . 'assets/img/' . $file_name;

		// If file does not exist, use 'unknown.png' instead
		if ( ! file_exists( $file_path ) ) {
			$file_name = 'unknown.png';
		}

		// Return the full URL to the image
		$flag_url = plugins_url( 'assets/img/' . $file_name, dirname(__FILE__) );

		return esc_url( $flag_url );
	}

	protected function showSnookerOrgLogoHtml(): string {
		// Return the full URL to the image
		$logo_url = plugins_url( 'assets/img/snooker_logo.png' , dirname(__FILE__) );

		return '<div class="powered-by-wrapper">
					<div class="powered-by">
						<strong>Powered by:</strong>
						<a href="https://www.snooker.org/" target="_blank">
						<img src="' . $logo_url . '" alt="Snooker.org Logo"></a>
					</div>
				</div>';

	}

	protected function check_response_code_for_errors( $response ): string {

		if ( is_wp_error( $response ) ) {
			return '<div class="snooker-org-error">Unable to connect to snooker.org API.</div>';
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code === 401 ) {
			return '<div class="snooker-org-error">Invalid Snooker.org Header Value (X-Requested-By). Please check your settings.</div>';
		}

		if ( $status_code !== 200 ) {
			return '<div class="snooker-org-error">Unexpected API error. Status code: ' . esc_html( $status_code ) . '</div>';
		}

		// No errors found
		return '';

	}

}
