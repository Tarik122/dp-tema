<?php
/**
 * Results, statistics, streaks and leaderboards for Kontekst and Tramvaj.
 *
 * Riječ dana keeps its own table (dpig_games). The newer games share
 * dpig_results: one row per player, game and day.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Day number of a game, counted from the option holding its first day. */
function dpig_day_number( $option, $date ) {
	$start = new DateTimeImmutable( get_option( $option, $date ) );
	$day   = new DateTimeImmutable( $date );
	return (int) $start->diff( $day )->format( '%r%a' ) + 1;
}

/** Seconds until the next puzzle (midnight in the site's time zone). */
function dpig_seconds_to_midnight() {
	return ( new DateTimeImmutable( 'tomorrow', wp_timezone() ) )->getTimestamp() - time();
}

function dpig_result_get( $player_id, $game, $date ) {
	global $wpdb;
	$row = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT * FROM ' . dpig_table( 'results' ) . ' WHERE player_id = %d AND game = %s AND puzzle_date = %s',
			$player_id,
			$game,
			$date
		),
		ARRAY_A
	);
	if ( $row ) {
		$data        = json_decode( $row['data'], true );
		$row['data'] = is_array( $data ) ? $data : array();
	}
	return $row;
}

/**
 * Create or update a player's result for a day. Leaderboards are refreshed
 * when a game is finished.
 */
function dpig_result_save( $player_id, $game, $date, array $data, $status = 'playing', $score = 0 ) {
	global $wpdb;
	$table  = dpig_table( 'results' );
	$now    = current_time( 'mysql' );
	$fields = array(
		'data'   => wp_json_encode( $data ),
		'status' => $status,
		'score'  => max( 0, (int) $score ),
	);
	$old = dpig_result_get( $player_id, $game, $date );
	if ( $old ) {
		if ( 'playing' !== $status && 'playing' === $old['status'] ) {
			$fields['finished_at'] = $now;
		}
		$wpdb->update( $table, $fields, array( 'id' => $old['id'] ) );
	} else {
		$fields += array(
			'player_id'   => $player_id,
			'game'        => $game,
			'puzzle_date' => $date,
			'started_at'  => $now,
			'finished_at' => 'playing' === $status ? null : $now,
		);
		$wpdb->insert( $table, $fields );
	}
	if ( 'playing' !== $status ) {
		dpig_flush_leaderboards();
	}
}

/** How a score is written: guesses for Kontekst, minutes and seconds for Tramvaj. */
function dpig_result_format( $game, $score ) {
	$score = (int) $score;
	if ( 'tramvaj' === $game ) {
		$s = (int) round( $score / 1000 );
		return sprintf( '%d:%02d', intdiv( $s, 60 ), $s % 60 );
	}
	return (string) $score;
}

function dpig_result_stats( $player_id, $game ) {
	global $wpdb;
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT puzzle_date, status, score FROM ' . dpig_table( 'results' ) . " WHERE player_id = %d AND game = %s AND status <> 'playing' ORDER BY puzzle_date",
			$player_id,
			$game
		),
		ARRAY_A
	);
	$won    = array();
	$lost   = array();
	$scores = array();
	foreach ( $rows as $r ) {
		if ( 'won' === $r['status'] ) {
			$won[]    = $r['puzzle_date'];
			$scores[] = (int) $r['score'];
		} else {
			$lost[] = $r['puzzle_date'];
		}
	}
	$streaks = dpig_streaks( $won, $lost, dpig_today() );
	return array(
		'played'        => count( $rows ),
		'won'           => count( $won ),
		'currentStreak' => $streaks['current'],
		'maxStreak'     => $streaks['best'],
		'best'          => $scores ? min( $scores ) : null,
		'average'       => $scores ? (int) round( array_sum( $scores ) / count( $scores ) ) : null,
	);
}

/**
 * @param string $game kontekst | tramvaj
 * @param string $type today | streak | month
 */
function dpig_result_leaderboard( $game, $type ) {
	global $wpdb;
	$today = dpig_today();
	$key   = 'dpig_lb_' . $game . '_' . $type . '_' . $today . '_' . (int) get_option( 'dpig_lb_version', 0 );
	$rows  = get_transient( $key );
	if ( is_array( $rows ) ) {
		return $rows;
	}

	$players = dpig_table( 'players' );
	$results = dpig_table( 'results' );
	$rows    = array();

	if ( 'today' === $type ) {
		$found = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.id, p.name, p.anonymous, r.score
				FROM $results r JOIN $players p ON p.id = r.player_id
				WHERE r.game = %s AND r.puzzle_date = %s AND r.status = 'won' AND p.hidden = 0
				ORDER BY r.score ASC, r.finished_at ASC LIMIT 100",
				$game,
				$today
			),
			ARRAY_A
		);
		foreach ( $found as $r ) {
			$rows[] = array(
				'id'    => (int) $r['id'],
				'name'  => dpig_display_name( $r ),
				'value' => dpig_result_format( $game, $r['score'] ),
			);
		}
	} elseif ( 'streak' === $type ) {
		$found     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.id, p.name, p.anonymous, r.puzzle_date, r.status
				FROM $results r JOIN $players p ON p.id = r.player_id
				WHERE r.game = %s AND r.status <> 'playing' AND p.hidden = 0
				ORDER BY p.id, r.puzzle_date",
				$game
			),
			ARRAY_A
		);
		$by_player = array();
		foreach ( $found as $r ) {
			$by_player[ $r['id'] ]['player']         = $r;
			$by_player[ $r['id'] ][ $r['status'] ][] = $r['puzzle_date'];
		}
		foreach ( $by_player as $id => $p ) {
			$s = dpig_streaks( $p['won'] ?? array(), $p['lost'] ?? array(), $today );
			if ( $s['current'] > 0 ) {
				$rows[] = array(
					'id'    => (int) $id,
					'name'  => dpig_display_name( $p['player'] ),
					'value' => $s['current'],
					'best'  => $s['best'],
				);
			}
		}
		usort(
			$rows,
			function ( $a, $b ) {
				return array( $b['value'], $b['best'] ) <=> array( $a['value'], $a['best'] );
			}
		);
		$rows = array_slice( $rows, 0, 100 );
		foreach ( $rows as &$row ) {
			$row['value'] = $row['value'] . ' 🔥';
			unset( $row['best'] );
		}
		unset( $row );
	} else {
		// This month: most days solved, then the better average.
		$found = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.id, p.name, p.anonymous, COUNT(*) AS wins, AVG(r.score) AS average
				FROM $results r JOIN $players p ON p.id = r.player_id
				WHERE r.game = %s AND r.puzzle_date >= %s AND r.status = 'won' AND p.hidden = 0
				GROUP BY p.id, p.name, p.anonymous
				ORDER BY wins DESC, average ASC LIMIT 100",
				$game,
				wp_date( 'Y-m-01' )
			),
			ARRAY_A
		);
		foreach ( $found as $r ) {
			$rows[] = array(
				'id'    => (int) $r['id'],
				'name'  => dpig_display_name( $r ),
				'value' => (int) $r['wins'] . ' ' . ( 1 === (int) $r['wins'] % 10 && 11 !== (int) $r['wins'] % 100 ? 'dan' : 'dana' ),
				'extra' => 'prosjek ' . dpig_result_format( $game, $r['average'] ),
			);
		}
	}

	set_transient( $key, $rows, 10 * MINUTE_IN_SECONDS );
	return $rows;
}

/** GET /board?game=kontekst&type=today */
function dpig_rest_board( WP_REST_Request $request ) {
	$game = $request->get_param( 'game' );
	if ( ! in_array( $game, array( 'kontekst', 'tramvaj' ), true ) ) {
		return dpig_error_response( new WP_Error( 'dpig_game', 'Nepoznata igra.' ) );
	}
	$type = $request->get_param( 'type' );
	if ( ! in_array( $type, array( 'today', 'streak', 'month' ), true ) ) {
		$type = 'today';
	}
	$player = dpig_current_player();
	$rows   = array();
	foreach ( dpig_result_leaderboard( $game, $type ) as $i => $row ) {
		$row['rank'] = $i + 1;
		$row['you']  = $player && (int) $player['id'] === $row['id'];
		unset( $row['id'] );
		$rows[] = $row;
	}
	return dpig_response( array( 'type' => $type, 'rows' => $rows ) );
}

/** The player part of a state response for Kontekst or Tramvaj. */
function dpig_result_player_payload( $player, $game ) {
	if ( ! $player ) {
		return null;
	}
	return array(
		'name'      => $player['name'],
		'email'     => $player['email'],
		'anonymous' => (bool) $player['anonymous'],
		'stats'     => dpig_result_stats( $player['id'], $game ),
	);
}

/** Register a route with the same rules as the Riječ dana routes. */
function dpig_route( $path, $method, $callback ) {
	register_rest_route(
		'dpig/v1',
		'/' . $path,
		array(
			'methods'             => $method,
			'callback'            => $callback,
			'permission_callback' => 'dpig_rest_permission',
		)
	);
}

add_action(
	'rest_api_init',
	function () {
		dpig_route( 'board', 'GET', 'dpig_rest_board' );
	}
);

/**
 * Enqueue what every game page needs: the shared styles, Google sign-in
 * (only when it is set up) and the shared script, with the settings the
 * browser needs.
 */
function dpig_enqueue_game( $game, array $extra = array() ) {
	$client_id = trim( get_option( 'dpig_client_id', '' ) );
	wp_enqueue_style( 'dpig-wordle', DPIG_URL . 'assets/wordle.css', array(), DPIG_VERSION );
	wp_enqueue_style( 'dpig-' . $game, DPIG_URL . 'assets/' . $game . '.css', array( 'dpig-wordle' ), DPIG_VERSION );
	$deps = array();
	if ( '' !== $client_id ) {
		wp_enqueue_script( 'dpig-google', 'https://accounts.google.com/gsi/client', array(), null, true );
	}
	wp_enqueue_script( 'dpig-core', DPIG_URL . 'assets/core.js', $deps, DPIG_VERSION, true );
	wp_enqueue_script( 'dpig-' . $game, DPIG_URL . 'assets/' . $game . '.js', array( 'dpig-core' ), DPIG_VERSION, true );
	wp_localize_script(
		'dpig-core',
		'DPIG_CONFIG',
		array(
			'api'      => esc_url_raw( rest_url( 'dpig/v1/' ) ),
			'clientId' => $client_id,
			'domain'   => get_option( 'dpig_domain', '' ),
			'pageUrl'  => get_permalink(),
		) + $extra
	);
}
