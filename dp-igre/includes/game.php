<?php
/**
 * Playing a guess, player statistics, streaks and leaderboards.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Points for a finished game: 6 for a first-try win down to 1 for a win on the sixth guess. */
function dpig_points( $status, $num_guesses ) {
	return 'won' === $status ? DPIG_MAX_GUESSES + 1 - (int) $num_guesses : 0;
}

/**
 * Score a list of guesses against a day's word.
 *
 * @return array{rows: array, status: string}|WP_Error
 */
function dpig_replay( array $guesses, $answer ) {
	if ( count( $guesses ) > DPIG_MAX_GUESSES ) {
		return new WP_Error( 'dpig_too_many', 'Nema više pokušaja.' );
	}
	$rows   = array();
	$status = 'playing';
	foreach ( $guesses as $i => $word ) {
		if ( 'playing' !== $status ) {
			return new WP_Error( 'dpig_over', 'Igra je završena.' );
		}
		$word = dpig_normalize( $word );
		if ( ! dpig_is_valid_guess( $word ) ) {
			return new WP_Error( 'dpig_invalid', 'Riječ nije na listi.' );
		}
		$rows[] = array(
			'word'   => $word,
			'tiles'  => dpig_tiles( $word ),
			'result' => dpig_score_guess( $word, $answer ),
		);
		if ( $word === $answer ) {
			$status = 'won';
		} elseif ( $i + 1 >= DPIG_MAX_GUESSES ) {
			$status = 'lost';
		}
	}
	return array(
		'rows'   => $rows,
		'status' => $status,
	);
}

/** What the browser gets to see about a game. The word is only revealed once it is over. */
function dpig_game_payload( array $replay, array $day ) {
	$payload = array(
		'rows'   => $replay['rows'],
		'status' => $replay['status'],
	);
	if ( 'playing' !== $replay['status'] ) {
		$payload['answer'] = $day['word'];
		$payload['note']   = $day['note'];
	}
	return $payload;
}

function dpig_get_game( $player_id, $date ) {
	global $wpdb;
	return $wpdb->get_row(
		$wpdb->prepare( 'SELECT * FROM ' . dpig_table( 'games' ) . ' WHERE player_id = %d AND puzzle_date = %s', $player_id, $date ),
		ARRAY_A
	);
}

/**
 * Store a signed-in player's guesses for a day (all of them, in order).
 */
function dpig_save_game( $player_id, $date, array $guesses, $status ) {
	global $wpdb;
	$table = dpig_table( 'games' );
	$now   = current_time( 'mysql' );
	$data  = array(
		'guesses'     => wp_json_encode( array_values( $guesses ) ),
		'status'      => $status,
		'num_guesses' => count( $guesses ),
		'finished_at' => 'playing' === $status ? null : $now,
	);
	if ( dpig_get_game( $player_id, $date ) ) {
		$wpdb->update( $table, $data, array( 'player_id' => $player_id, 'puzzle_date' => $date ) );
	} else {
		$wpdb->insert( $table, $data + array( 'player_id' => $player_id, 'puzzle_date' => $date, 'started_at' => $now ) );
	}
	if ( 'playing' !== $status ) {
		dpig_flush_leaderboards();
	}
}

function dpig_game_guesses( $game ) {
	$guesses = $game ? json_decode( $game['guesses'], true ) : array();
	return is_array( $guesses ) ? $guesses : array();
}

/**
 * Longest run of consecutive days in a sorted list of Y-m-d dates, and the run
 * that is still alive (ending today, or yesterday if today is not finished).
 */
function dpig_streaks( array $won_dates, array $lost_dates, $today ) {
	$won  = array_flip( $won_dates );
	$best = 0;
	$run  = 0;
	$prev = null;
	foreach ( $won_dates as $date ) {
		$run  = ( $prev && gmdate( 'Y-m-d', strtotime( $prev . ' +1 day' ) ) === $date ) ? $run + 1 : 1;
		$best = max( $best, $run );
		$prev = $date;
	}

	$current = 0;
	if ( ! in_array( $today, $lost_dates, true ) ) {
		$day = isset( $won[ $today ] ) ? $today : gmdate( 'Y-m-d', strtotime( $today . ' -1 day' ) );
		while ( isset( $won[ $day ] ) ) {
			$current++;
			$day = gmdate( 'Y-m-d', strtotime( $day . ' -1 day' ) );
		}
	}
	return array(
		'current' => $current,
		'best'    => $best,
	);
}

function dpig_player_stats( $player_id ) {
	global $wpdb;
	$games = $wpdb->get_results(
		$wpdb->prepare( 'SELECT puzzle_date, status, num_guesses FROM ' . dpig_table( 'games' ) . " WHERE player_id = %d AND status <> 'playing' ORDER BY puzzle_date", $player_id ),
		ARRAY_A
	);
	$won          = array();
	$lost         = array();
	$distribution = array_fill( 1, DPIG_MAX_GUESSES, 0 );
	$points       = 0;
	foreach ( $games as $g ) {
		if ( 'won' === $g['status'] ) {
			$won[] = $g['puzzle_date'];
			$distribution[ (int) $g['num_guesses'] ]++;
		} else {
			$lost[] = $g['puzzle_date'];
		}
		$points += dpig_points( $g['status'], $g['num_guesses'] );
	}
	$streaks = dpig_streaks( $won, $lost, dpig_today() );
	return array(
		'played'        => count( $games ),
		'won'           => count( $won ),
		'points'        => $points,
		'currentStreak' => $streaks['current'],
		'maxStreak'     => $streaks['best'],
		'distribution'  => array_values( $distribution ),
	);
}

function dpig_display_name( $player ) {
	return (int) $player['anonymous'] ? 'Anonimni igrač' : $player['name'];
}

function dpig_flush_leaderboards() {
	update_option( 'dpig_lb_version', (int) get_option( 'dpig_lb_version', 0 ) + 1, false );
}

/**
 * @param string $type today | streak | month | all
 */
function dpig_leaderboard( $type ) {
	global $wpdb;
	$today = dpig_today();
	$key   = 'dpig_lb_' . $type . '_' . $today . '_' . (int) get_option( 'dpig_lb_version', 0 );
	$rows  = get_transient( $key );
	if ( is_array( $rows ) ) {
		return $rows;
	}

	$players = dpig_table( 'players' );
	$games   = dpig_table( 'games' );
	$rows    = array();

	if ( 'today' === $type ) {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.id, p.name, p.anonymous, g.num_guesses
				FROM $games g JOIN $players p ON p.id = g.player_id
				WHERE g.puzzle_date = %s AND g.status = 'won' AND p.hidden = 0
				ORDER BY g.num_guesses ASC, g.finished_at ASC LIMIT 100",
				$today
			),
			ARRAY_A
		);
		foreach ( $results as $r ) {
			$rows[] = array(
				'id'    => (int) $r['id'],
				'name'  => dpig_display_name( $r ),
				'value' => (int) $r['num_guesses'] . '/' . DPIG_MAX_GUESSES,
			);
		}
	} elseif ( 'streak' === $type ) {
		$results = $wpdb->get_results(
			"SELECT p.id, p.name, p.anonymous, g.puzzle_date, g.status
			FROM $games g JOIN $players p ON p.id = g.player_id
			WHERE g.status <> 'playing' AND p.hidden = 0
			ORDER BY p.id, g.puzzle_date",
			ARRAY_A
		);
		$by_player = array();
		foreach ( $results as $r ) {
			$by_player[ $r['id'] ]['player'] = $r;
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
		}
		unset( $row );
	} else {
		$since   = 'month' === $type ? wp_date( 'Y-m-01' ) : '1970-01-01';
		$max     = DPIG_MAX_GUESSES + 1;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.id, p.name, p.anonymous,
					SUM(CASE WHEN g.status = 'won' THEN $max - g.num_guesses ELSE 0 END) AS points,
					SUM(CASE WHEN g.status = 'won' THEN 1 ELSE 0 END) AS wins,
					COUNT(*) AS played
				FROM $games g JOIN $players p ON p.id = g.player_id
				WHERE g.puzzle_date >= %s AND g.status <> 'playing' AND p.hidden = 0
				GROUP BY p.id, p.name, p.anonymous
				HAVING points > 0
				ORDER BY points DESC, wins DESC, played ASC LIMIT 100",
				$since
			),
			ARRAY_A
		);
		foreach ( $results as $r ) {
			$rows[] = array(
				'id'    => (int) $r['id'],
				'name'  => dpig_display_name( $r ),
				'value' => (int) $r['points'] . ' b',
				'extra' => (int) $r['wins'] . '/' . (int) $r['played'],
			);
		}
	}

	set_transient( $key, $rows, 10 * MINUTE_IN_SECONDS );
	return $rows;
}
