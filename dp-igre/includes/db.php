<?php
/**
 * Database tables and options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dpig_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'dpig_' . $name;
}

function dpig_activate() {
	dpig_install_tables();

	if ( ! get_option( 'dpig_secret' ) ) {
		update_option( 'dpig_secret', wp_generate_password( 64, true, true ), false );
	}
	if ( ! get_option( 'dpig_start_date' ) ) {
		update_option( 'dpig_start_date', wp_date( 'Y-m-d' ) );
	}
	add_option( 'dpig_domain', '2gimnazija.edu.ba' );
	add_option( 'dpig_client_id', '' );
	add_option( 'dpig_title', 'Riječ dana' );
	// Kontekst and Tramvaj number their days from the day they were installed.
	add_option( 'dpig_kx_start', wp_date( 'Y-m-d' ) );
	add_option( 'dpig_tv_start', wp_date( 'Y-m-d' ) );
	// Work out Kontekst's ranking just after midnight, so the first player of the day does not wait.
	if ( ! wp_next_scheduled( 'dpig_kx_warm' ) ) {
		wp_schedule_event( ( new DateTimeImmutable( 'tomorrow', wp_timezone() ) )->getTimestamp() + 60, 'daily', 'dpig_kx_warm' );
	}
}

function dpig_deactivate() {
	wp_clear_scheduled_hook( 'dpig_kx_warm' );
}

function dpig_maybe_upgrade() {
	if ( get_option( 'dpig_db_version' ) !== DPIG_DB_VERSION ) {
		dpig_activate();
	}
}

function dpig_install_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset = $wpdb->get_charset_collate();
	$players = dpig_table( 'players' );
	$games   = dpig_table( 'games' );
	$days    = dpig_table( 'days' );

	// Players are identified by their verified school e-mail.
	dbDelta(
		"CREATE TABLE $players (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(190) NOT NULL,
			name varchar(190) NOT NULL,
			anonymous tinyint(1) NOT NULL DEFAULT 0,
			hidden tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email)
		) $charset;"
	);

	// One game per player per day. guesses is a JSON array of words.
	dbDelta(
		"CREATE TABLE $games (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			player_id bigint(20) unsigned NOT NULL,
			puzzle_date date NOT NULL,
			guesses text NOT NULL,
			status varchar(10) NOT NULL DEFAULT 'playing',
			num_guesses tinyint(3) unsigned NOT NULL DEFAULT 0,
			started_at datetime NOT NULL,
			finished_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY player_day (player_id,puzzle_date),
			KEY puzzle_date (puzzle_date)
		) $charset;"
	);

	// The word for each day. Custom words are planned ahead in the admin
	// panel; automatic words are picked (and locked in) the first time a day
	// is requested, so editing the word list never changes a running puzzle.
	dbDelta(
		"CREATE TABLE $days (
			puzzle_date date NOT NULL,
			word varchar(40) NOT NULL,
			source varchar(10) NOT NULL DEFAULT 'auto',
			note varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (puzzle_date)
		) $charset;"
	);

	// Results of the other games (Kontekst, Tramvaj): one row per player, game and day.
	// data holds the game's own details as JSON; score is what the leaderboard sorts by
	// (number of guesses for Kontekst, milliseconds for Tramvaj).
	$results = dpig_table( 'results' );
	dbDelta(
		"CREATE TABLE $results (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			player_id bigint(20) unsigned NOT NULL,
			game varchar(20) NOT NULL,
			puzzle_date date NOT NULL,
			data longtext NOT NULL,
			status varchar(10) NOT NULL DEFAULT 'playing',
			score int(10) unsigned NOT NULL DEFAULT 0,
			started_at datetime NOT NULL,
			finished_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY player_game_day (player_id,game,puzzle_date),
			KEY game_day (game,puzzle_date)
		) $charset;"
	);

	update_option( 'dpig_db_version', DPIG_DB_VERSION );
}
