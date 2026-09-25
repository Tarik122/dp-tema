<?php
/**
 * Google sign-in (school accounts only) and the player session cookie.
 *
 * Players are not WordPress users. After Google confirms the e-mail we store
 * a signed cookie holding the player id.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DPIG_COOKIE = 'dpig_session';

/**
 * Check a Google ID token and return its claims, or a WP_Error.
 */
function dpig_verify_google_token( $credential ) {
	$client_id = trim( get_option( 'dpig_client_id', '' ) );
	$domain    = strtolower( trim( get_option( 'dpig_domain', '' ) ) );
	if ( '' === $client_id ) {
		return new WP_Error( 'dpig_config', 'Google prijava još nije podešena.' );
	}

	$response = wp_remote_get(
		add_query_arg( 'id_token', rawurlencode( $credential ), 'https://oauth2.googleapis.com/tokeninfo' ),
		array( 'timeout' => 10 )
	);
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'dpig_token', 'Prijava nije uspjela. Pokušaj ponovo.' );
	}
	$claims = json_decode( wp_remote_retrieve_body( $response ), true );

	$email = strtolower( $claims['email'] ?? '' );
	$ok    = is_array( $claims )
		&& ( $claims['aud'] ?? '' ) === $client_id
		&& in_array( $claims['iss'] ?? '', array( 'accounts.google.com', 'https://accounts.google.com' ), true )
		&& (int) ( $claims['exp'] ?? 0 ) > time()
		&& in_array( $claims['email_verified'] ?? '', array( true, 'true' ), true );
	if ( ! $ok ) {
		return new WP_Error( 'dpig_token', 'Prijava nije uspjela. Pokušaj ponovo.' );
	}
	if ( '' !== $domain && ( ( $claims['hd'] ?? '' ) !== $domain || ! str_ends_with( $email, '@' . $domain ) ) ) {
		return new WP_Error( 'dpig_domain', sprintf( 'Prijavi se školskim e-mailom (@%s).', $domain ) );
	}
	return $claims;
}

/** "amina.hodzic" → "Amina Hodzic". */
function dpig_name_from_email( $email ) {
	$local = strtok( strtolower( $email ), '@' );
	$local = preg_replace( '/\d+/', '', $local );
	$parts = preg_split( '/[._\-]+/', $local, -1, PREG_SPLIT_NO_EMPTY );
	$name  = mb_convert_case( implode( ' ', $parts ), MB_CASE_TITLE, 'UTF-8' );
	return '' !== $name ? $name : $email;
}

function dpig_find_or_create_player( $email ) {
	global $wpdb;
	$table  = dpig_table( 'players' );
	$player = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE email = %s", $email ), ARRAY_A );
	if ( $player ) {
		return $player;
	}
	$wpdb->query(
		$wpdb->prepare(
			"INSERT IGNORE INTO $table (email, name, anonymous, hidden, created_at) VALUES (%s, %s, 0, 0, %s)",
			$email,
			dpig_name_from_email( $email ),
			current_time( 'mysql' )
		)
	);
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE email = %s", $email ), ARRAY_A );
}

function dpig_sign( $data ) {
	return hash_hmac( 'sha256', $data, get_option( 'dpig_secret' ) . wp_salt( 'auth' ) );
}

function dpig_set_session( $player_id ) {
	$expires = time() + 180 * DAY_IN_SECONDS;
	$data    = $player_id . '|' . $expires;
	dpig_send_cookie( $data . '|' . dpig_sign( $data ), $expires );
}

function dpig_clear_session() {
	dpig_send_cookie( '', time() - YEAR_IN_SECONDS );
}

function dpig_send_cookie( $value, $expires ) {
	setcookie(
		DPIG_COOKIE,
		$value,
		array(
			'expires'  => $expires,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/** The signed-in player, or null for guests. */
function dpig_current_player() {
	static $player = false;
	if ( false !== $player ) {
		return $player;
	}
	$player = null;

	$parts = explode( '|', wp_unslash( $_COOKIE[ DPIG_COOKIE ] ?? '' ) );
	if ( 3 !== count( $parts ) ) {
		return null;
	}
	list( $id, $expires, $sig ) = $parts;
	if ( (int) $expires < time() || ! hash_equals( dpig_sign( $id . '|' . $expires ), $sig ) ) {
		return null;
	}

	global $wpdb;
	$row    = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dpig_table( 'players' ) . ' WHERE id = %d', (int) $id ), ARRAY_A );
	$player = $row ? $row : null;
	return $player;
}
