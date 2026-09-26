<?php
/**
 * Kontekst: guess the secret word by meaning.
 *
 * Every guess gets a place in the day's ranking: the secret word is #1 and
 * the words used in the most similar way are right behind it. The ranking
 * comes from word vectors (see tools/kontekst/README.md) and is worked out
 * once a day, then kept in the options table.
 *
 * Shortcode: [dp_kontekst]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DPIG_KX_RECORD = 154; // Bytes per word in vectors.bin: float32 + 150 bytes of 4-bit values.

function dpig_kx_file( $name ) {
	return DPIG_DIR . 'data/kontekst/' . $name;
}

/** Base words in id order. */
function dpig_kx_words() {
	static $words = null;
	if ( null === $words ) {
		$words = file( dpig_kx_file( 'words.txt' ), FILE_IGNORE_NEW_LINES );
	}
	return $words;
}

function dpig_kx_word( $id ) {
	$words = dpig_kx_words();
	return $words[ $id ] ?? '';
}

/**
 * The id of the base word for any form of it ("kuće" → id of "kuća"), or null.
 * forms.txt is sorted, so this is a binary search in the file.
 */
function dpig_kx_lookup( $word ) {
	$file = dpig_kx_file( 'forms.txt' );
	$fh   = fopen( $file, 'rb' );
	if ( ! $fh ) {
		return null;
	}
	$lo = 0;
	$hi = filesize( $file );
	while ( $lo < $hi ) {
		$mid = intdiv( $lo + $hi, 2 );
		// Read the first line that starts at $mid or later.
		if ( $mid > 0 ) {
			fseek( $fh, $mid - 1 );
			fgets( $fh );
		} else {
			fseek( $fh, 0 );
		}
		$pos  = ftell( $fh );
		$line = fgets( $fh );
		if ( false === $line || $pos >= $hi ) {
			$hi = $mid;
			continue;
		}
		$parts = explode( "\t", rtrim( $line, "\n" ) );
		$cmp   = strcmp( $word, $parts[0] );
		if ( 0 === $cmp ) {
			fclose( $fh );
			return (int) $parts[1];
		}
		if ( $cmp < 0 ) {
			$hi = $mid;
		} else {
			$lo = $pos + strlen( $line );
		}
	}
	fclose( $fh );
	return null;
}

/**
 * Rank every word by similarity to one word. Returns a packed string with a
 * 16-bit rank per word id (the word itself is #1).
 */
function dpig_kx_rank_all( $answer_id ) {
	$data = file_get_contents( dpig_kx_file( 'vectors.bin' ) );
	$n    = intdiv( strlen( $data ), DPIG_KX_RECORD );

	$a = array();
	foreach ( unpack( 'C150', $data, $answer_id * DPIG_KX_RECORD + 4 ) as $b ) {
		$a[] = ( $b & 15 ) - 8;
		$a[] = ( $b >> 4 ) - 8;
	}
	// One lookup table per byte: what that byte adds to the dot product.
	$lut = array();
	for ( $j = 0; $j < 150; $j++ ) {
		$x = $a[ 2 * $j ];
		$y = $a[ 2 * $j + 1 ];
		$t = array();
		for ( $b = 0; $b < 256; $b++ ) {
			$t[ $b ] = $x * ( ( $b & 15 ) - 8 ) + $y * ( ( $b >> 4 ) - 8 );
		}
		$lut[ $j ] = $t;
	}

	$scores = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$off = $i * DPIG_KX_RECORD;
		$inv = unpack( 'g', $data, $off )[1];
		$sum = 0;
		$j   = 0;
		foreach ( unpack( 'C150', $data, $off + 4 ) as $b ) {
			$sum += $lut[ $j ][ $b ];
			$j++;
		}
		$scores[ $i ] = $sum * $inv;
	}
	$scores[ $answer_id ] = PHP_INT_MAX;
	arsort( $scores );

	$ranks = array_fill( 0, $n, 0 );
	$r     = 1;
	foreach ( $scores as $i => $unused ) {
		$ranks[ $i ] = $r++;
	}
	return pack( 'n*', ...$ranks );
}

/** Today's (or any day's) secret word and ranking. */
function dpig_kx_day( $date ) {
	$cached = get_option( 'dpig_kx_day' );
	if ( is_array( $cached ) && ( $cached['date'] ?? '' ) === $date && ( $cached['version'] ?? '' ) === DPIG_VERSION ) {
		return $cached;
	}
	$answers = file( dpig_kx_file( 'answers.txt' ), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	$number  = dpig_day_number( 'dpig_kx_start', $date );
	$count   = count( $answers );
	$word    = $answers[ ( ( $number - 1 ) % $count + $count ) % $count ];
	$id      = dpig_kx_lookup( $word );
	$day     = array(
		'date'    => $date,
		'version' => DPIG_VERSION,
		'number'  => $number,
		'answer'  => (int) $id,
		'ranks'   => base64_encode( dpig_kx_rank_all( (int) $id ) ),
	);
	update_option( 'dpig_kx_day', $day, false );
	return $day;
}

function dpig_kx_rank( array $day, $id ) {
	static $ranks = array();
	if ( ! isset( $ranks[ $day['date'] ] ) ) {
		$ranks[ $day['date'] ] = base64_decode( $day['ranks'] );
	}
	return unpack( 'n', $ranks[ $day['date'] ], $id * 2 )[1];
}

/** Word ids in order of rank: $order[1] is the answer. */
function dpig_kx_order( array $day ) {
	$order = array();
	foreach ( unpack( 'n*', base64_decode( $day['ranks'] ) ) as $i => $rank ) {
		$order[ $rank ] = $i - 1;
	}
	return $order;
}

function dpig_kx_guess_payload( $id, $rank, $hint = false ) {
	return array(
		'w' => dpig_kx_word( $id ),
		'r' => (int) $rank,
		'h' => (bool) $hint,
	);
}

/** A signed-in player's game: guesses as [id, rank, hint]. */
function dpig_kx_result_payload( $result, array $day ) {
	if ( ! $result ) {
		return null;
	}
	$guesses = array();
	foreach ( $result['data']['g'] ?? array() as $g ) {
		$guesses[] = dpig_kx_guess_payload( $g[0], $g[1], ! empty( $g[2] ) );
	}
	$payload = array(
		'guesses' => $guesses,
		'status'  => $result['status'],
	);
	if ( 'playing' !== $result['status'] ) {
		$payload['answer'] = dpig_kx_word( $day['answer'] );
	}
	return $payload;
}

function dpig_kx_check_date( WP_REST_Request $request ) {
	if ( $request->get_param( 'date' ) !== dpig_today() ) {
		return dpig_response( array( 'error' => 'dpig_new_day', 'message' => 'Stigla je nova riječ! Osvježi stranicu.' ), 409 );
	}
	return null;
}

function dpig_rest_kx_state() {
	$date   = dpig_today();
	$day    = dpig_kx_day( $date );
	$player = dpig_current_player();
	return dpig_response(
		array(
			'date'   => $date,
			'number' => $day['number'],
			'total'  => count( dpig_kx_words() ),
			'nextIn' => dpig_seconds_to_midnight(),
			'player' => dpig_result_player_payload( $player, 'kontekst' ),
			'game'   => $player ? dpig_kx_result_payload( dpig_result_get( $player['id'], 'kontekst', $date ), $day ) : null,
		)
	);
}

/**
 * Signed in: the server keeps the guesses. Guests keep their own guesses in
 * the browser; they do not appear on leaderboards.
 */
function dpig_rest_kx_guess( WP_REST_Request $request ) {
	$wrong = dpig_kx_check_date( $request );
	if ( $wrong ) {
		return $wrong;
	}
	$word = dpig_normalize( (string) $request->get_param( 'word' ) );
	if ( '' === $word || ! preg_match( '/^[a-zčćđšž]+$/u', $word ) ) {
		return dpig_error_response( new WP_Error( 'dpig_word', 'Upiši jednu riječ, samo slovima.' ) );
	}
	$id = dpig_kx_lookup( $word );
	if ( null === $id ) {
		return dpig_error_response( new WP_Error( 'dpig_unknown', 'Ne znam riječ „' . $word . '“. Probaj neku drugu.' ) );
	}

	$date   = dpig_today();
	$day    = dpig_kx_day( $date );
	$rank   = dpig_kx_rank( $day, $id );
	$player = dpig_current_player();
	$status = 1 === $rank ? 'won' : 'playing';

	if ( $player ) {
		$result  = dpig_result_get( $player['id'], 'kontekst', $date );
		$guesses = $result['data']['g'] ?? array();
		if ( $result && 'playing' !== $result['status'] ) {
			return dpig_error_response( new WP_Error( 'dpig_over', 'Današnja igra je završena.' ) );
		}
		foreach ( $guesses as $g ) {
			if ( (int) $g[0] === $id ) {
				return dpig_response( array( 'guess' => dpig_kx_guess_payload( $id, $rank ), 'repeat' => true ) );
			}
		}
		$guesses[] = array( $id, $rank, 0 );
		dpig_result_save( $player['id'], 'kontekst', $date, array( 'g' => $guesses ), $status, count( $guesses ) );
	}

	$response = array(
		'guess'  => dpig_kx_guess_payload( $id, $rank ),
		'status' => $status,
	);
	if ( 'won' === $status ) {
		$response['answer'] = dpig_kx_word( $id );
		$response['player'] = dpig_result_player_payload( $player, 'kontekst' );
	}
	return dpig_response( $response );
}

/**
 * A hint: a word about twice as close as the best guess so far
 * (or #300 if nothing has been guessed yet).
 */
function dpig_rest_kx_hint( WP_REST_Request $request ) {
	$wrong = dpig_kx_check_date( $request );
	if ( $wrong ) {
		return $wrong;
	}
	$date   = dpig_today();
	$day    = dpig_kx_day( $date );
	$player = dpig_current_player();
	$result = $player ? dpig_result_get( $player['id'], 'kontekst', $date ) : null;
	if ( $result && 'playing' !== $result['status'] ) {
		return dpig_error_response( new WP_Error( 'dpig_over', 'Današnja igra je završena.' ) );
	}

	if ( $player ) {
		$best = 0;
		foreach ( $result['data']['g'] ?? array() as $g ) {
			$best = $best ? min( $best, (int) $g[1] ) : (int) $g[1];
		}
	} else {
		$best = max( 0, (int) $request->get_param( 'best' ) );
	}
	if ( $best && $best <= 2 ) {
		return dpig_error_response( new WP_Error( 'dpig_close', 'Već si na korak do cilja. Bez pomoći!' ) );
	}
	$target = ( ! $best || $best > 600 ) ? 300 : max( 2, intdiv( $best, 2 ) );
	$order  = dpig_kx_order( $day );
	$id     = $order[ $target ];

	if ( $player ) {
		$guesses   = $result['data']['g'] ?? array();
		$guesses[] = array( $id, $target, 1 );
		dpig_result_save( $player['id'], 'kontekst', $date, array( 'g' => $guesses ), 'playing', count( $guesses ) );
	}
	return dpig_response( array( 'guess' => dpig_kx_guess_payload( $id, $target, true ) ) );
}

function dpig_rest_kx_giveup( WP_REST_Request $request ) {
	$wrong = dpig_kx_check_date( $request );
	if ( $wrong ) {
		return $wrong;
	}
	$date   = dpig_today();
	$day    = dpig_kx_day( $date );
	$player = dpig_current_player();
	if ( $player ) {
		$result = dpig_result_get( $player['id'], 'kontekst', $date );
		if ( ! $result || 'playing' === $result['status'] ) {
			$guesses = $result['data']['g'] ?? array();
			dpig_result_save( $player['id'], 'kontekst', $date, array( 'g' => $guesses ), 'lost', count( $guesses ) );
		}
	}
	return dpig_response(
		array(
			'answer' => dpig_kx_word( $day['answer'] ),
			'player' => dpig_result_player_payload( $player, 'kontekst' ),
		)
	);
}

/** The 100 closest words, shown after the game. */
function dpig_rest_kx_top( WP_REST_Request $request ) {
	$wrong = dpig_kx_check_date( $request );
	if ( $wrong ) {
		return $wrong;
	}
	$date   = dpig_today();
	$day    = dpig_kx_day( $date );
	$player = dpig_current_player();
	if ( $player ) {
		$result = dpig_result_get( $player['id'], 'kontekst', $date );
		if ( ! $result || 'playing' === $result['status'] ) {
			return dpig_error_response( new WP_Error( 'dpig_playing', 'Najbliže riječi vidiš kad završiš igru.' ) );
		}
	}
	$order = dpig_kx_order( $day );
	$words = array();
	for ( $r = 1; $r <= 100 && isset( $order[ $r ] ); $r++ ) {
		$words[] = dpig_kx_guess_payload( $order[ $r ], $r );
	}
	return dpig_response( array( 'words' => $words ) );
}

add_action(
	'rest_api_init',
	function () {
		dpig_route( 'kontekst/state', 'GET', 'dpig_rest_kx_state' );
		dpig_route( 'kontekst/guess', 'POST', 'dpig_rest_kx_guess' );
		dpig_route( 'kontekst/hint', 'POST', 'dpig_rest_kx_hint' );
		dpig_route( 'kontekst/giveup', 'POST', 'dpig_rest_kx_giveup' );
		dpig_route( 'kontekst/top', 'GET', 'dpig_rest_kx_top' );
	}
);

/** Scheduled just after midnight (see dpig_activate). */
add_action(
	'dpig_kx_warm',
	function () {
		dpig_kx_day( dpig_today() );
	}
);

add_shortcode(
	'dp_kontekst',
	function () {
		dpig_enqueue_game( 'kontekst', array( 'title' => 'Kontekst' ) );
		return '<div class="dpig dpig-kx" id="dpig-root"><noscript>Za igru je potreban JavaScript.</noscript></div>';
	}
);
