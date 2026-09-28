<?php
/**
 * Settings and the theme hooks they drive.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Homepage sections in their default order: key => name shown in the admin. */
function dpp_parts() {
	return array(
		'igre'       => 'Igre (plava traka)',
		'misljenje'  => 'Mišljenje',
		'vijesti'    => 'Vijesti',
		'kultura'    => 'Kultura',
		'citat'      => 'Rečeno (citat iz nekog članka)',
		'sportnauka' => 'Sport i Nauka',
		'arhiva'     => 'Iz arhive',
	);
}

/** Section subtitles that can be changed: key => default text (from the theme). */
function dpp_subtitles() {
	return array(
		'misljenje' => 'Stavovi, kolumne i eseji naših autora.',
		'vijesti'   => 'Šta se dešava u školi i u gradu.',
		'kultura'   => 'Predstave, koncerti, serije i knjige.',
		'sport'     => 'Utakmice, turniri i naši sportisti.',
		'nauka'     => 'Nauka i tehnologija, objašnjene jednostavno.',
		'arhiva'    => 'Tekstovi koje vrijedi ponovo pročitati.',
	);
}

function dpp_defaults() {
	return array(
		'lead_id'     => 0,
		'order'       => array_keys( dpp_parts() ),
		'hidden'      => array(),
		'subtitles'   => array(),
		'show_latest' => true,
		'show_date'   => true,
		'new_days'    => 30,
	);
}

function dpp_settings() {
	$saved = get_option( 'dpp_settings', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), dpp_defaults() );
}

/* ---------- Hooks the theme listens to ---------- */

add_filter(
	'dp_home_lead_id',
	function ( $id ) {
		$lead = (int) dpp_settings()['lead_id'];
		return $lead ? $lead : $id;
	}
);

add_filter(
	'dp_home_parts',
	function () {
		$s     = dpp_settings();
		$parts = array();
		foreach ( $s['order'] as $key ) {
			if ( isset( dpp_parts()[ $key ] ) ) {
				$parts[ $key ] = ! in_array( $key, (array) $s['hidden'], true );
			}
		}
		return $parts;
	}
);

add_filter(
	'dp_section_subtitle',
	function ( $text, $key ) {
		$subs = dpp_settings()['subtitles'];
		return isset( $subs[ $key ] ) && '' !== trim( $subs[ $key ] ) ? $subs[ $key ] : $text;
	},
	10,
	2
);

add_filter( 'dp_home_show_latest', function () { return (bool) dpp_settings()['show_latest']; } );
add_filter( 'dp_show_header_date', function () { return (bool) dpp_settings()['show_date']; } );
add_filter( 'dp_game_new_days', function () { return max( 0, (int) dpp_settings()['new_days'] ); } );

/** Save the Naslovnica and Opcije forms. */
function dpp_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	check_admin_referer( 'dpp_save' );
	$s   = dpp_settings();
	$tab = sanitize_key( $_POST['tab'] ?? 'naslovnica' );

	if ( 'naslovnica' === $tab ) {
		$s['lead_id'] = absint( $_POST['lead_id'] ?? 0 );
		$order        = array_map( 'sanitize_key', (array) wp_unslash( $_POST['order'] ?? array() ) );
		$order        = array_values( array_intersect( $order, array_keys( dpp_parts() ) ) );
		$s['order']   = array_values( array_unique( array_merge( $order, array_keys( dpp_parts() ) ) ) );
		$shown        = array_map( 'sanitize_key', (array) wp_unslash( $_POST['shown'] ?? array() ) );
		$s['hidden']  = array_values( array_diff( array_keys( dpp_parts() ), $shown ) );
		$subs         = array();
		foreach ( dpp_subtitles() as $key => $default ) {
			$text = sanitize_text_field( wp_unslash( $_POST['subtitles'][ $key ] ?? '' ) );
			if ( '' !== $text && $text !== $default ) {
				$subs[ $key ] = $text;
			}
		}
		$s['subtitles']   = $subs;
		$s['show_latest'] = ! empty( $_POST['show_latest'] );
	} elseif ( 'opcije' === $tab ) {
		$s['show_date'] = ! empty( $_POST['show_date'] );
		$s['new_days']  = min( 365, absint( $_POST['new_days'] ?? 30 ) );
	}

	update_option( 'dpp_settings', $s );
	wp_safe_redirect( add_query_arg( array( 'page' => 'dp-postavke', 'tab' => $tab, 'dpp_msg' => 'saved' ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_dpp_save', 'dpp_save_settings' );
