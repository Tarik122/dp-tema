<?php
/**
 * Ljestve: get from one word to another by changing one letter at a time.
 * Every step must be a real word. LJ, NJ and DŽ are one letter.
 *
 * The daily pairs are made ahead of time (tools/ljestve/generate.py), each
 * with a shortest ladder through common words. Any real word is accepted as
 * a step; hints only suggest common words.
 *
 * Shortcode: [dp_ljestve]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DPIG_LJ_LENGTH = 4;

function dpig_lj_file( $name ) {
	return DPIG_DIR . 'data/ljestve/' . $name;
}

/** Words that count as a step (word => true). */
function dpig_lj_words() {
	static $words = null;
	if ( null === $words ) {
		$words = array_fill_keys( file( dpig_lj_file( 'words.txt' ), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ), true );
	}
	return $words;
}

/** Common words, the only ones hints use. */
function dpig_lj_common() {
	static $words = null;
	if ( null === $words ) {
		$words = file( dpig_lj_file( 'common.txt' ), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	}
	return $words;
}

function dpig_lj_day( $date ) {
	static $list = null;
	if ( null === $list ) {
		$list = json_decode( (string) file_get_contents( dpig_lj_file( 'puzzles.json' ) ), true );
		$list = is_array( $list ) ? $list : array();
	}
	$number = dpig_day_number( 'dpig_lj_start', $date );
	$count  = max( 1, count( $list ) );
	$puzzle = $list[ ( ( $number - 1 ) % $count + $count ) % $count ] ?? array( 'a' => '', 'b' => '', 'n' => 0, 'p' => array() );
	return array( 'number' => $number ) + $puzzle;
}

/** True when two words have the same length and differ in exactly one letter. */
function dpig_lj_one_apart( $a, $b ) {
	$x = dpig_tiles( $a );
	$y = dpig_tiles( $b );
	if ( ! $x || ! $y || count( $x ) !== DPIG_LJ_LENGTH || count( $y ) !== DPIG_LJ_LENGTH ) {
		return false;
	}
	$diff = 0;
	foreach ( $x as $i => $t ) {
		if ( $t !== $y[ $i ] ) {
			$diff++;
		}
	}
	return 1 === $diff;
}

/** Steps from each common word to the target, through common words (word => steps). */
function dpig_lj_distances( $target ) {
	static $cache = array();
	if ( isset( $cache[ $target ] ) ) {
		return $cache[ $target ];
	}
	$common  = dpig_lj_common();
	$buckets = array();
	foreach ( $common as $w ) {
		$t = dpig_tiles( $w );
		for ( $i = 0; $i < DPIG_LJ_LENGTH; $i++ ) {
			$k               = $t;
			$k[ $i ]         = '*';
			$buckets[ implode( '|', $k ) ][] = $w;
		}
	}
	$dist  = array( $target => 0 );
	$queue = array( $target );
	for ( $q = 0; $q < count( $queue ); $q++ ) {
		$u = $queue[ $q ];
		$t = dpig_tiles( $u );
		for ( $i = 0; $i < DPIG_LJ_LENGTH; $i++ ) {
			$k       = $t;
			$k[ $i ] = '*';
			foreach ( $buckets[ implode( '|', $k ) ] ?? array() as $v ) {
				if ( ! isset( $dist[ $v ] ) ) {
					$dist[ $v ] = $dist[ $u ] + 1;
					$queue[]    = $v;
				}
			}
		}
	}
	$cache[ $target ] = $dist;
	return $dist;
}

/** The next common word on a shortest way from $from to the target, or null. */
function dpig_lj_hint_from( $from, $target, array $avoid ) {
	$dist = dpig_lj_distances( $target );
	$best = null;
	foreach ( dpig_lj_common() as $w ) {
		if ( isset( $dist[ $w ] ) && ! in_array( $w, $avoid, true ) && dpig_lj_one_apart( $from, $w ) ) {
			if ( null === $best || $dist[ $w ] < $dist[ $best ] ) {
				$best = $w;
			}
		}
	}
	return $best;
}

/**
 * Score: every typed step counts 1, hints cost 2, 4, 8… (each twice the one before).
 *
 * @param array $steps [[word, hint], …]
 * @param int   $hints hints used today (also ones later undone)
 */
function dpig_lj_score( array $steps, $hints ) {
	$typed = 0;
	foreach ( $steps as $s ) {
		if ( empty( $s[1] ) ) {
			$typed++;
		}
	}
	return $typed + ( $hints ? ( 2 ** ( $hints + 1 ) ) - 2 : 0 );
}

function dpig_lj_game_payload( $result, array $day ) {
	if ( ! $result ) {
		return null;
	}
	$steps = array();
	foreach ( $result['data']['s'] ?? array() as $s ) {
		$steps[] = array( 'w' => $s[0], 'h' => ! empty( $s[1] ) );
	}
	$payload = array(
		'steps'  => $steps,
		'hints'  => (int) ( $result['data']['k'] ?? 0 ),
		'status' => $result['status'],
	);
	if ( 'playing' !== $result['status'] ) {
		$payload['path'] = $day['p'];
	}
	return $payload;
}

function dpig_lj_wrong_day( WP_REST_Request $request ) {
	if ( $request->get_param( 'date' ) !== dpig_today() ) {
		return dpig_response( array( 'error' => 'dpig_new_day', 'message' => 'Stigle su nove ljestve!' ), 409 );
	}
	return null;
}

/** A signed-in player's game for today, as [steps, hints, status]. */
function dpig_lj_load( $player, $date ) {
	$result = dpig_result_get( $player['id'], 'ljestve', $date );
	return array(
		$result['data']['s'] ?? array(),
		(int) ( $result['data']['k'] ?? 0 ),
		$result ? $result['status'] : 'playing',
	);
}

function dpig_lj_save( $player, $date, array $steps, $hints, $status ) {
	dpig_result_save( $player['id'], 'ljestve', $date, array( 's' => $steps, 'k' => $hints ), $status, dpig_lj_score( $steps, $hints ) );
}

function dpig_rest_lj_state() {
	$date   = dpig_today();
	$day    = dpig_lj_day( $date );
	$player = dpig_current_player();
	return dpig_response(
		array(
			'date'   => $date,
			'number' => $day['number'],
			'start'  => $day['a'],
			'target' => $day['b'],
			'par'    => (int) $day['n'],
			'nextIn' => dpig_seconds_to_midnight(),
			'player' => dpig_result_player_payload( $player, 'ljestve' ),
			'game'   => $player ? dpig_lj_game_payload( dpig_result_get( $player['id'], 'ljestve', $date ), $day ) : null,
		)
	);
}

/**
 * One step. Signed in: the server keeps the ladder. Guests send the word
 * they are coming from ("from") and keep their ladder in the browser.
 */
function dpig_rest_lj_step( WP_REST_Request $request ) {
	$wrong = dpig_lj_wrong_day( $request );
	if ( $wrong ) {
		return $wrong;
	}
	$date   = dpig_today();
	$day    = dpig_lj_day( $date );
	$word   = dpig_normalize( (string) $request->get_param( 'word' ) );
	$player = dpig_current_player();

	if ( $player ) {
		list( $steps, $hints, $status ) = dpig_lj_load( $player, $date );
		if ( 'playing' !== $status ) {
			return dpig_error_response( new WP_Error( 'dpig_over', 'Današnje ljestve su završene.' ) );
		}
		$from = $steps ? end( $steps )[0] : $day['a'];
		$used = array_merge( array( $day['a'] ), array_column( $steps, 0 ) );
	} else {
		$from  = dpig_normalize( (string) $request->get_param( 'from' ) );
		$used  = array();
		$steps = array();
		$hints = 0;
	}

	$tiles = dpig_tiles( $word );
	if ( ! $tiles || count( $tiles ) !== DPIG_LJ_LENGTH ) {
		return dpig_error_response( new WP_Error( 'dpig_length', 'Riječ mora imati ' . DPIG_LJ_LENGTH . ' slova.' ) );
	}
	if ( ! dpig_lj_one_apart( $from, $word ) ) {
		return dpig_error_response( new WP_Error( 'dpig_one', 'Promijeni tačno jedno slovo.' ) );
	}
	if ( ! isset( dpig_lj_words()[ $word ] ) && $word !== $day['b'] ) {
		return dpig_error_response( new WP_Error( 'dpig_unknown', 'Ne znam riječ „' . $word . '“.' ) );
	}
	if ( in_array( $word, $used, true ) ) {
		return dpig_error_response( new WP_Error( 'dpig_used', 'Ta riječ je već na ljestvama.' ) );
	}

	$status = $word === $day['b'] ? 'won' : 'playing';
	if ( $player ) {
		$steps[] = array( $word, 0 );
		dpig_lj_save( $player, $date, $steps, $hints, $status );
	}
	$response = array(
		'step'   => array( 'w' => $word, 'h' => false ),
		'status' => $status,
	);
	if ( 'won' === $status ) {
		$response['path']   = $day['p'];
		$response['player'] = dpig_result_player_payload( $player, 'ljestve' );
	}
	return dpig_response( $response );
}

function dpig_rest_lj_undo( WP_REST_Request $request ) {
	$wrong = dpig_lj_wrong_day( $request );
	if ( $wrong ) {
		return $wrong;
	}
	$player = dpig_current_player();
	if ( $player ) {
		$date = dpig_today();
		list( $steps, $hints, $status ) = dpig_lj_load( $player, $date );
		if ( 'playing' === $status && $steps ) {
			array_pop( $steps );
			dpig_lj_save( $player, $date, $steps, $hints, 'playing' );
		}
	}
	return dpig_response( array( 'ok' => true ) );
}

function dpig_rest_lj_hint( WP_REST_Request $request ) {
	$wrong = dpig_lj_wrong_day( $request );
	if ( $wrong ) {
		return $wrong;
	}
	$date   = dpig_today();
	$day    = dpig_lj_day( $date );
	$player = dpig_current_player();
	if ( $player ) {
		list( $steps, $hints, $status ) = dpig_lj_load( $player, $date );
		if ( 'playing' !== $status ) {
			return dpig_error_response( new WP_Error( 'dpig_over', 'Današnje ljestve su završene.' ) );
		}
		$from = $steps ? end( $steps )[0] : $day['a'];
		$used = array_merge( array( $day['a'] ), array_column( $steps, 0 ) );
	} else {
		$from = dpig_normalize( (string) $request->get_param( 'from' ) );
		$used = array_map( 'dpig_normalize', array_filter( (array) $request->get_param( 'used' ), 'is_string' ) );
	}
	if ( dpig_lj_one_apart( $from, $day['b'] ) ) {
		return dpig_error_response( new WP_Error( 'dpig_close', 'Samo još jedan korak do cilja. Bez pomoći!' ) );
	}
	$word = dpig_lj_hint_from( $from, $day['b'], $used );
	if ( null === $word ) {
		return dpig_error_response( new WP_Error( 'dpig_stuck', 'Odavde ne znam dalje. Vrati korak ili dva, pa probaj ponovo.' ) );
	}
	if ( $player ) {
		$steps[] = array( $word, 1 );
		dpig_lj_save( $player, $date, $steps, $hints + 1, 'playing' );
	}
	return dpig_response( array( 'step' => array( 'w' => $word, 'h' => true ) ) );
}

function dpig_rest_lj_giveup( WP_REST_Request $request ) {
	$wrong = dpig_lj_wrong_day( $request );
	if ( $wrong ) {
		return $wrong;
	}
	$date   = dpig_today();
	$day    = dpig_lj_day( $date );
	$player = dpig_current_player();
	if ( $player ) {
		list( $steps, $hints, $status ) = dpig_lj_load( $player, $date );
		if ( 'playing' === $status ) {
			dpig_lj_save( $player, $date, $steps, $hints, 'lost' );
		}
	}
	return dpig_response(
		array(
			'path'   => $day['p'],
			'player' => dpig_result_player_payload( $player, 'ljestve' ),
		)
	);
}

add_action(
	'rest_api_init',
	function () {
		dpig_route( 'ljestve/state', 'GET', 'dpig_rest_lj_state' );
		dpig_route( 'ljestve/step', 'POST', 'dpig_rest_lj_step' );
		dpig_route( 'ljestve/undo', 'POST', 'dpig_rest_lj_undo' );
		dpig_route( 'ljestve/hint', 'POST', 'dpig_rest_lj_hint' );
		dpig_route( 'ljestve/giveup', 'POST', 'dpig_rest_lj_giveup' );
	}
);

add_shortcode(
	'dp_ljestve',
	function () {
		dpig_enqueue_game( 'ljestve', array( 'title' => 'Ljestve', 'length' => DPIG_LJ_LENGTH ) );
		return '<div class="dpig dpig-lj" id="dpig-root"><noscript>Za igru je potreban JavaScript.</noscript></div>';
	}
);
