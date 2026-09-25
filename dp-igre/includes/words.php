<?php
/**
 * Bosnian words: tiles, dictionary, daily word, scoring a guess.
 *
 * The Bosnian alphabet has 30 letters. LJ, NJ and DŽ are single letters, so
 * "ljubav" is five tiles: lj-u-b-a-v.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DPIG_ALPHABET = array( 'a', 'b', 'c', 'č', 'ć', 'd', 'dž', 'đ', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'lj', 'm', 'n', 'nj', 'o', 'p', 'r', 's', 'š', 't', 'u', 'v', 'z', 'ž' );

function dpig_normalize( $word ) {
	$word = mb_strtolower( trim( (string) $word ), 'UTF-8' );
	// Accept the single-codepoint digraphs (ǆ, ǉ, ǌ) some keyboards produce.
	return strtr( $word, array( 'ǆ' => 'dž', 'ǉ' => 'lj', 'ǌ' => 'nj' ) );
}

/**
 * Split a word into letters. Returns null when it contains anything that is
 * not a Bosnian letter.
 */
function dpig_tiles( $word ) {
	$word  = dpig_normalize( $word );
	$chars = preg_split( '//u', $word, -1, PREG_SPLIT_NO_EMPTY );
	$tiles = array();
	$count = count( $chars );
	for ( $i = 0; $i < $count; $i++ ) {
		$pair = $chars[ $i ] . ( $chars[ $i + 1 ] ?? '' );
		if ( in_array( $pair, array( 'lj', 'nj', 'dž' ), true ) ) {
			$tiles[] = $pair;
			$i++;
		} elseif ( in_array( $chars[ $i ], DPIG_ALPHABET, true ) ) {
			$tiles[] = $chars[ $i ];
		} else {
			return null;
		}
	}
	return $tiles;
}

function dpig_is_playable( $word ) {
	$tiles = dpig_tiles( $word );
	return null !== $tiles && count( $tiles ) === DPIG_WORD_LENGTH;
}

function dpig_parse_word_list( $text ) {
	$words = preg_split( '/[\s,;]+/u', (string) $text, -1, PREG_SPLIT_NO_EMPTY );
	$words = array_map( 'dpig_normalize', $words );
	return array_values( array_unique( array_filter( $words, 'dpig_is_playable' ) ) );
}

/** Words that can be picked as the word of the day. */
function dpig_answers() {
	$custom = get_option( 'dpig_answers' );
	if ( is_string( $custom ) && '' !== trim( $custom ) ) {
		return dpig_parse_word_list( $custom );
	}
	return dpig_parse_word_list( file_get_contents( DPIG_DIR . 'data/answers.txt' ) );
}

function dpig_default_answers_text() {
	return trim( file_get_contents( DPIG_DIR . 'data/answers.txt' ) );
}

/** Is this an accepted guess? */
function dpig_is_valid_guess( $word ) {
	static $dictionary = null;
	$word = dpig_normalize( $word );
	if ( ! dpig_is_playable( $word ) ) {
		return false;
	}
	if ( null === $dictionary ) {
		$dictionary = "\n" . file_get_contents( DPIG_DIR . 'data/valid.txt' ) . "\n"
			. implode( "\n", dpig_answers() ) . "\n"
			. implode( "\n", dpig_parse_word_list( get_option( 'dpig_extra_words', '' ) ) ) . "\n";
	}
	if ( false !== strpos( $dictionary, "\n" . $word . "\n" ) ) {
		return true;
	}
	// Custom words of the day are always accepted.
	global $wpdb;
	return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM ' . dpig_table( 'days' ) . ' WHERE word = %s LIMIT 1', $word ) );
}

/**
 * Colour each letter of a guess: 2 = right place, 1 = in the word, 0 = not in the word.
 */
function dpig_score_guess( $guess, $answer ) {
	$g      = dpig_tiles( $guess );
	$a      = dpig_tiles( $answer );
	$result = array_fill( 0, count( $g ), 0 );
	$left   = array();

	foreach ( $g as $i => $tile ) {
		if ( isset( $a[ $i ] ) && $a[ $i ] === $tile ) {
			$result[ $i ] = 2;
		} elseif ( isset( $a[ $i ] ) ) {
			$left[ $a[ $i ] ] = ( $left[ $a[ $i ] ] ?? 0 ) + 1;
		}
	}
	foreach ( $g as $i => $tile ) {
		if ( 2 !== $result[ $i ] && ! empty( $left[ $tile ] ) ) {
			$result[ $i ] = 1;
			$left[ $tile ]--;
		}
	}
	return $result;
}

/** Today's date in the site's time zone (Settings → General). */
function dpig_today() {
	return wp_date( 'Y-m-d' );
}

function dpig_puzzle_number( $date ) {
	$start = new DateTimeImmutable( get_option( 'dpig_start_date', $date ) );
	$day   = new DateTimeImmutable( $date );
	return (int) $start->diff( $day )->format( '%r%a' ) + 1;
}

/**
 * The word (and optional message) for a date. Automatic words are chosen the
 * first time the day is requested and stored, preferring words that have not
 * been used yet.
 */
function dpig_day( $date ) {
	global $wpdb;
	$table = dpig_table( 'days' );
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT word, note, source FROM $table WHERE puzzle_date = %s", $date ), ARRAY_A );
	if ( $row ) {
		return $row;
	}

	$answers = dpig_answers();
	$used    = $wpdb->get_col( "SELECT word FROM $table" );
	$fresh   = array_values( array_diff( $answers, $used ) );
	if ( ! $fresh ) {
		// Every word has been used: allow repeats, but not from the last 60 days.
		$recent = $wpdb->get_col( $wpdb->prepare( "SELECT word FROM $table WHERE puzzle_date >= %s", gmdate( 'Y-m-d', strtotime( $date . ' -60 days' ) ) ) );
		$fresh  = array_values( array_diff( $answers, $recent ) );
		if ( ! $fresh ) {
			$fresh = $answers;
		}
	}
	$word = $fresh[ random_int( 0, count( $fresh ) - 1 ) ];

	// INSERT IGNORE: if two visitors arrive at the same moment, the first wins.
	$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $table (puzzle_date, word, source, note) VALUES (%s, %s, 'auto', '')", $date, $word ) );
	return $wpdb->get_row( $wpdb->prepare( "SELECT word, note, source FROM $table WHERE puzzle_date = %s", $date ), ARRAY_A );
}
