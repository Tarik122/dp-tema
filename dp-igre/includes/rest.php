<?php
/**
 * REST API used by the game page: /wp-json/dpig/v1/...
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'dpig_register_routes' );

function dpig_register_routes() {
	$routes = array(
		'state'       => array( 'GET', 'dpig_rest_state' ),
		'guess'       => array( 'POST', 'dpig_rest_guess' ),
		'login'       => array( 'POST', 'dpig_rest_login' ),
		'logout'      => array( 'POST', 'dpig_rest_logout' ),
		'preferences' => array( 'POST', 'dpig_rest_preferences' ),
		'leaderboard' => array( 'GET', 'dpig_rest_leaderboard' ),
	);
	foreach ( $routes as $path => $route ) {
		register_rest_route(
			'dpig/v1',
			'/' . $path,
			array(
				'methods'             => $route[0],
				'callback'            => $route[1],
				'permission_callback' => 'dpig_rest_permission',
			)
		);
	}
}

/**
 * Anyone may play. POSTs must carry our custom header: browsers do not let
 * other sites send it, which protects the session cookie from CSRF.
 */
function dpig_rest_permission( WP_REST_Request $request ) {
	if ( 'POST' === $request->get_method() && '1' !== $request->get_header( 'x_dpig' ) ) {
		return new WP_Error( 'dpig_forbidden', 'Zabranjeno.', array( 'status' => 403 ) );
	}
	return true;
}

function dpig_response( $data, $status = 200 ) {
	$response = new WP_REST_Response( $data, $status );
	$response->header( 'Cache-Control', 'no-store, private' );
	return $response;
}

function dpig_error_response( WP_Error $error ) {
	return dpig_response(
		array(
			'error'   => $error->get_error_code(),
			'message' => $error->get_error_message(),
		),
		400
	);
}

/** A guest's earlier guesses today, as sent by the browser. */
function dpig_history_param( WP_REST_Request $request ) {
	$history = $request->get_param( 'history' );
	if ( ! is_array( $history ) ) {
		return array();
	}
	return array_values( array_filter( $history, 'is_string' ) );
}

function dpig_player_payload( $player ) {
	if ( ! $player ) {
		return null;
	}
	return array(
		'name'      => $player['name'],
		'email'     => $player['email'],
		'anonymous' => (bool) $player['anonymous'],
		'stats'     => dpig_player_stats( $player['id'] ),
	);
}

/** Everything the page needs on load. */
function dpig_rest_state() {
	$date   = dpig_today();
	$day    = dpig_day( $date );
	$player = dpig_current_player();
	$game   = null;

	if ( $player ) {
		$replay = dpig_replay( dpig_game_guesses( dpig_get_game( $player['id'], $date ) ), $day['word'] );
		if ( ! is_wp_error( $replay ) ) {
			$game = dpig_game_payload( $replay, $day );
		}
	}

	return dpig_response(
		array(
			'date'       => $date,
			'number'     => dpig_puzzle_number( $date ),
			'length'     => DPIG_WORD_LENGTH,
			'maxGuesses' => DPIG_MAX_GUESSES,
			'special'    => 'custom' === $day['source'],
			'nextIn'     => ( new DateTimeImmutable( 'tomorrow', wp_timezone() ) )->getTimestamp() - time(),
			'player'     => dpig_player_payload( $player ),
			'game'       => $game,
		)
	);
}

/**
 * Submit one guess.
 *
 * Signed in: the server keeps the guesses, one game per day.
 * Guest: the browser keeps its own guesses and sends them with each request
 * ("history"); nothing is stored and guests do not appear on leaderboards.
 */
function dpig_rest_guess( WP_REST_Request $request ) {
	$date = dpig_today();
	if ( $request->get_param( 'date' ) !== $date ) {
		return dpig_response( array( 'error' => 'dpig_new_day', 'message' => 'Stigla je nova riječ! Osvježi stranicu.' ), 409 );
	}
	$day    = dpig_day( $date );
	$guess  = dpig_normalize( (string) $request->get_param( 'guess' ) );
	$player = dpig_current_player();

	if ( ! dpig_is_playable( $guess ) ) {
		return dpig_error_response( new WP_Error( 'dpig_length', 'Riječ mora imati ' . DPIG_WORD_LENGTH . ' slova.' ) );
	}

	if ( $player ) {
		$previous = dpig_game_guesses( dpig_get_game( $player['id'], $date ) );
	} else {
		$previous = dpig_history_param( $request );
	}
	$replay = dpig_replay( $previous, $day['word'] );
	if ( is_wp_error( $replay ) ) {
		return dpig_error_response( $replay );
	}
	if ( 'playing' !== $replay['status'] ) {
		return dpig_error_response( new WP_Error( 'dpig_over', 'Današnja igra je završena.' ) );
	}

	$guesses = array_merge( array_map( 'dpig_normalize', $previous ), array( $guess ) );
	$replay  = dpig_replay( $guesses, $day['word'] );
	if ( is_wp_error( $replay ) ) {
		return dpig_error_response( $replay );
	}
	if ( $player ) {
		dpig_save_game( $player['id'], $date, $guesses, $replay['status'] );
	}

	return dpig_response(
		array(
			'game'   => dpig_game_payload( $replay, $day ),
			'player' => dpig_player_payload( $player ),
		)
	);
}

/**
 * Sign in with a Google ID token. If the visitor already played today as a
 * guest, those guesses carry over (only when their account has no game yet).
 */
function dpig_rest_login( WP_REST_Request $request ) {
	$claims = dpig_verify_google_token( (string) $request->get_param( 'credential' ) );
	if ( is_wp_error( $claims ) ) {
		return dpig_error_response( $claims );
	}
	$player = dpig_find_or_create_player( strtolower( $claims['email'] ) );
	if ( ! $player ) {
		return dpig_error_response( new WP_Error( 'dpig_db', 'Greška pri spremanju. Pokušaj ponovo.' ) );
	}
	dpig_set_session( $player['id'] );

	$date    = dpig_today();
	$history = array_map( 'dpig_normalize', dpig_history_param( $request ) );
	if ( $history && $request->get_param( 'date' ) === $date && ! dpig_get_game( $player['id'], $date ) ) {
		$day    = dpig_day( $date );
		$replay = dpig_replay( $history, $day['word'] );
		if ( ! is_wp_error( $replay ) ) {
			dpig_save_game( $player['id'], $date, $history, $replay['status'] );
		}
	}

	return dpig_response( array( 'ok' => true ) );
}

function dpig_rest_logout() {
	dpig_clear_session();
	return dpig_response( array( 'ok' => true ) );
}

function dpig_rest_preferences( WP_REST_Request $request ) {
	$player = dpig_current_player();
	if ( ! $player ) {
		return dpig_response( array( 'error' => 'dpig_login', 'message' => 'Prijavi se.' ), 401 );
	}
	global $wpdb;
	$wpdb->update(
		dpig_table( 'players' ),
		array( 'anonymous' => $request->get_param( 'anonymous' ) ? 1 : 0 ),
		array( 'id' => $player['id'] )
	);
	dpig_flush_leaderboards();
	return dpig_response( array( 'ok' => true ) );
}

function dpig_rest_leaderboard( WP_REST_Request $request ) {
	$type = $request->get_param( 'type' );
	if ( ! in_array( $type, array( 'today', 'streak', 'month', 'all' ), true ) ) {
		$type = 'today';
	}
	$player = dpig_current_player();
	$rows   = array();
	foreach ( dpig_leaderboard( $type ) as $i => $row ) {
		$row['rank'] = $i + 1;
		$row['you']  = $player && (int) $player['id'] === $row['id'];
		unset( $row['id'] );
		$rows[] = $row;
	}
	return dpig_response( array( 'type' => $type, 'rows' => $rows ) );
}
