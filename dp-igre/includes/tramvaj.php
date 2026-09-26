<?php
/**
 * Tramvaj: draw one line through every square, visiting the stops in order.
 *
 * The puzzles are made ahead of time (tools/tramvaj/generate.mjs) and each has
 * exactly one solution. The puzzle is only sent when the player presses
 * "Kreni", and for signed-in players the server keeps the time from then on,
 * so the leaderboard is fair.
 *
 * Shortcode: [dp_tramvaj]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Tram stops in Sarajevo, used to name each day's line. */
const DPIG_TV_WEST = array( 'Ilidža', 'Stup', 'Nedžarići', 'Alipašino Polje', 'Otoka', 'Čengić Vila', 'Dolac Malta', 'Pofalići' );
const DPIG_TV_EAST = array( 'Baščaršija', 'Vijećnica', 'Latinska ćuprija', 'Drvenija', 'Skenderija', 'Marijin Dvor', 'Muzeji' );

function dpig_tv_puzzles() {
	static $list = null;
	if ( null === $list ) {
		$list = json_decode( (string) file_get_contents( DPIG_DIR . 'data/tramvaj/puzzles.json' ), true );
		if ( ! is_array( $list ) ) {
			$list = array();
		}
	}
	return $list;
}

function dpig_tv_day( $date ) {
	$list   = dpig_tv_puzzles();
	$number = dpig_day_number( 'dpig_tv_start', $date );
	$count  = max( 1, count( $list ) );
	$puzzle = $list[ ( ( $number - 1 ) % $count + $count ) % $count ] ?? array( 'n' => 0, 's' => array(), 'w' => array() );
	$from   = DPIG_TV_WEST[ ( $number * 5 ) % count( DPIG_TV_WEST ) ];
	$to     = DPIG_TV_EAST[ ( $number * 3 ) % count( DPIG_TV_EAST ) ];
	return array(
		'number' => $number,
		'puzzle' => $puzzle,
		'route'  => array( 'from' => $from, 'to' => $to ),
	);
}

/**
 * A path is a list of cell numbers (row * n + column). It must pass through
 * every cell once, move one step up, down, left or right at a time, never
 * cross a wall, and meet the stops in order, starting and ending on a stop.
 */
function dpig_tv_check_path( array $puzzle, $path ) {
	$n = (int) $puzzle['n'];
	if ( ! is_array( $path ) || count( $path ) !== $n * $n ) {
		return false;
	}
	$walls = array();
	foreach ( $puzzle['w'] as $w ) {
		$walls[ min( $w ) . ',' . max( $w ) ] = true;
	}
	$stop_index = array_flip( $puzzle['s'] );
	$seen       = array();
	$next_stop  = 0;
	$prev       = null;
	foreach ( array_values( $path ) as $cell ) {
		if ( ! is_int( $cell ) || $cell < 0 || $cell >= $n * $n || isset( $seen[ $cell ] ) ) {
			return false;
		}
		if ( null !== $prev ) {
			$dr = abs( intdiv( $cell, $n ) - intdiv( $prev, $n ) );
			$dc = abs( $cell % $n - $prev % $n );
			if ( 1 !== $dr + $dc || isset( $walls[ min( $cell, $prev ) . ',' . max( $cell, $prev ) ] ) ) {
				return false;
			}
		}
		if ( isset( $stop_index[ $cell ] ) ) {
			if ( $stop_index[ $cell ] !== $next_stop ) {
				return false;
			}
			$next_stop++;
		} elseif ( null === $prev ) {
			return false; // The line starts at stop 1.
		}
		$seen[ $cell ] = true;
		$prev          = $cell;
	}
	return count( $puzzle['s'] ) === $next_stop && end( $puzzle['s'] ) === $prev;
}

function dpig_tv_result_payload( $result ) {
	if ( ! $result ) {
		return null;
	}
	return array(
		'status'  => $result['status'],
		'ms'      => 'won' === $result['status'] ? (int) $result['score'] : null,
		'started' => true,
		'elapsed' => 'playing' === $result['status'] ? (int) round( ( microtime( true ) - (float) ( $result['data']['t0'] ?? microtime( true ) ) ) * 1000 ) : null,
		'path'    => 'won' === $result['status'] ? ( $result['data']['path'] ?? array() ) : null,
	);
}

function dpig_tv_puzzle_payload( array $day ) {
	return array(
		'n' => (int) $day['puzzle']['n'],
		's' => $day['puzzle']['s'],
		'w' => $day['puzzle']['w'],
	);
}

function dpig_rest_tv_state() {
	$date   = dpig_today();
	$day    = dpig_tv_day( $date );
	$player = dpig_current_player();
	$result = $player ? dpig_result_get( $player['id'], 'tramvaj', $date ) : null;
	return dpig_response(
		array(
			'date'   => $date,
			'number' => $day['number'],
			'n'      => (int) $day['puzzle']['n'],
			'route'  => $day['route'],
			'nextIn' => dpig_seconds_to_midnight(),
			'player' => dpig_result_player_payload( $player, 'tramvaj' ),
			'game'   => dpig_tv_result_payload( $result ),
			// Players who already started (or finished) today get the puzzle straight away.
			'puzzle' => $result ? dpig_tv_puzzle_payload( $day ) : null,
		)
	);
}

function dpig_rest_tv_start( WP_REST_Request $request ) {
	if ( $request->get_param( 'date' ) !== dpig_today() ) {
		return dpig_response( array( 'error' => 'dpig_new_day', 'message' => 'Stigla je nova vožnja! Osvježi stranicu.' ), 409 );
	}
	$date   = dpig_today();
	$day    = dpig_tv_day( $date );
	$player = dpig_current_player();
	if ( $player && ! dpig_result_get( $player['id'], 'tramvaj', $date ) ) {
		dpig_result_save( $player['id'], 'tramvaj', $date, array( 't0' => microtime( true ) ) );
	}
	return dpig_response( array( 'puzzle' => dpig_tv_puzzle_payload( $day ) ) );
}

function dpig_rest_tv_solve( WP_REST_Request $request ) {
	if ( $request->get_param( 'date' ) !== dpig_today() ) {
		return dpig_response( array( 'error' => 'dpig_new_day', 'message' => 'Stigla je nova vožnja! Osvježi stranicu.' ), 409 );
	}
	$date = dpig_today();
	$day  = dpig_tv_day( $date );
	$path = $request->get_param( 'path' );
	if ( ! dpig_tv_check_path( $day['puzzle'], $path ) ) {
		return dpig_error_response( new WP_Error( 'dpig_path', 'Linija nije ispravna.' ) );
	}
	$player = dpig_current_player();
	$ms     = max( 0, (int) $request->get_param( 'ms' ) );
	if ( $player ) {
		$result = dpig_result_get( $player['id'], 'tramvaj', $date );
		if ( $result && 'won' === $result['status'] ) {
			$ms = (int) $result['score'];
		} else {
			$t0 = (float) ( $result['data']['t0'] ?? ( microtime( true ) - $ms / 1000 ) );
			$ms = (int) round( ( microtime( true ) - $t0 ) * 1000 );
			dpig_result_save( $player['id'], 'tramvaj', $date, array( 't0' => $t0, 'path' => array_values( $path ) ), 'won', $ms );
		}
	}
	return dpig_response(
		array(
			'ms'     => $ms,
			'player' => dpig_result_player_payload( $player, 'tramvaj' ),
		)
	);
}

add_action(
	'rest_api_init',
	function () {
		dpig_route( 'tramvaj/state', 'GET', 'dpig_rest_tv_state' );
		dpig_route( 'tramvaj/start', 'POST', 'dpig_rest_tv_start' );
		dpig_route( 'tramvaj/solve', 'POST', 'dpig_rest_tv_solve' );
	}
);

add_shortcode(
	'dp_tramvaj',
	function () {
		dpig_enqueue_game( 'tramvaj', array( 'title' => 'Tramvaj' ) );
		return '<div class="dpig dpig-tv" id="dpig-root"><noscript>Za igru je potreban JavaScript.</noscript></div>';
	}
);
