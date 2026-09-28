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

/** Sections that take articles from a category: key => [label, default category slug, default count]. */
function dpp_sources() {
	return array(
		'misljenje' => array( 'Mišljenje', 'opinion', 3 ),
		'vijesti'   => array( 'Vijesti', 'vijesti', 4 ),
		'kultura'   => array( 'Kultura', 'kultura', 4 ),
		'sport'     => array( 'Sport', 'sport', 4 ),
		'nauka'     => array( 'Nauka', 'nauka', 4 ),
		'arhiva'    => array( 'Iz arhive', 'izdvojeno', 3 ),
	);
}

/** Which source keys belong to which homepage row. */
function dpp_part_sources( $part ) {
	$map = array(
		'misljenje'  => array( 'misljenje' ),
		'vijesti'    => array( 'vijesti' ),
		'kultura'    => array( 'kultura' ),
		'sportnauka' => array( 'sport', 'nauka' ),
		'arhiva'     => array( 'arhiva' ),
	);
	return $map[ $part ] ?? array();
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

/** Article options: key => label. */
function dpp_article_options() {
	return array(
		'dropcap'      => 'Veliko početno slovo prvog pasusa',
		'endmark'      => 'Mali DP znak na kraju teksta',
		'reading_time' => 'Vrijeme čitanja ispod naslova ("4 min čitanja")',
		'author_box'   => 'Okvir o autoru na kraju teksta',
		'read_more'    => '"Pročitajte još" ispod teksta',
	);
}

/** Chip colours the theme knows: slug => name. */
function dpp_colors() {
	return array(
		'plava-chip'    => 'Plava',
		'narandza-chip' => 'Narandžasta',
		'zelena'        => 'Zelena',
		'tinta'         => 'Crna',
	);
}

function dpp_defaults() {
	return array(
		'lead_id'         => 0,
		'order'           => array_keys( dpp_parts() ),
		'hidden'          => array(),
		'subtitles'       => array(),
		'cats'            => array(),
		'counts'          => array(),
		'archive_months'  => 6,
		'show_latest'     => true,
		'show_date'       => true,
		'new_days'        => 30,
		'article'         => array_fill_keys( array_keys( dpp_article_options() ), true ),
		'read_more_count' => 3,
		'footer_about'    => '',
		'footer_note'     => '',
		'footer_links'    => array(),
		'colors'          => array(),
	);
}

function dpp_settings() {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( 'dpp_settings', array() );
		$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), dpp_defaults() );
		$cache['article'] = wp_parse_args( (array) $cache['article'], dpp_defaults()['article'] );
	}
	return $cache;
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

add_filter(
	'dp_home_section_category',
	function ( $slug, $key ) {
		$cats = dpp_settings()['cats'];
		return ! empty( $cats[ $key ] ) ? $cats[ $key ] : $slug;
	},
	10,
	2
);

add_filter(
	'dp_home_section_count',
	function ( $count, $key ) {
		$counts = dpp_settings()['counts'];
		return ! empty( $counts[ $key ] ) ? (int) $counts[ $key ] : $count;
	},
	10,
	2
);

add_filter( 'dp_archive_months', function () { return (int) dpp_settings()['archive_months']; } );
add_filter( 'dp_home_show_latest', function () { return (bool) dpp_settings()['show_latest']; } );
add_filter( 'dp_show_header_date', function () { return (bool) dpp_settings()['show_date']; } );
add_filter( 'dp_game_new_days', function () { return max( 0, (int) dpp_settings()['new_days'] ); } );
add_filter( 'dp_read_more_count', function () { return (int) dpp_settings()['read_more_count']; } );

add_filter(
	'dp_article_option',
	function ( $on, $key ) {
		$article = dpp_settings()['article'];
		return isset( $article[ $key ] ) ? (bool) $article[ $key ] : $on;
	},
	10,
	2
);

add_filter( 'dp_footer_about', function ( $text ) { return dpp_settings()['footer_about'] ? dpp_settings()['footer_about'] : $text; } );
add_filter( 'dp_footer_note', function ( $text ) { return dpp_settings()['footer_note'] ? dpp_settings()['footer_note'] : $text; } );
add_filter(
	'dp_footer_links',
	function ( $links ) {
		$saved = dpp_settings()['footer_links'];
		return $saved ? $saved : $links;
	}
);

add_filter(
	'dp_category_color',
	function ( $color, $term ) {
		$colors = dpp_settings()['colors'];
		if ( $term && ! empty( $colors[ $term->term_id ] ) ) {
			return $colors[ $term->term_id ];
		}
		if ( $term && $term->parent && ! empty( $colors[ $term->parent ] ) ) {
			return $colors[ $term->parent ];
		}
		return $color;
	},
	10,
	2
);

/* ---------- Saving ---------- */

function dpp_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	check_admin_referer( 'dpp_save' );
	$s    = dpp_settings();
	$tab  = sanitize_key( $_POST['tab'] ?? 'naslovnica' );
	$post = wp_unslash( $_POST );

	if ( 'naslovnica' === $tab ) {
		$s['lead_id'] = absint( $post['lead_id'] ?? 0 );
		$order        = array_map( 'sanitize_key', (array) ( $post['order'] ?? array() ) );
		$order        = array_values( array_intersect( $order, array_keys( dpp_parts() ) ) );
		$s['order']   = array_values( array_unique( array_merge( $order, array_keys( dpp_parts() ) ) ) );
		$shown        = array_map( 'sanitize_key', (array) ( $post['shown'] ?? array() ) );
		$s['hidden']  = array_values( array_diff( array_keys( dpp_parts() ), $shown ) );
		$s['subtitles'] = array();
		foreach ( dpp_subtitles() as $key => $default ) {
			$text = sanitize_text_field( $post['subtitles'][ $key ] ?? '' );
			if ( '' !== $text && $text !== $default ) {
				$s['subtitles'][ $key ] = $text;
			}
		}
		$s['cats']   = array();
		$s['counts'] = array();
		foreach ( dpp_sources() as $key => $info ) {
			$slug = sanitize_title( $post['cats'][ $key ] ?? '' );
			if ( $slug && $slug !== $info[1] && get_term_by( 'slug', $slug, 'category' ) ) {
				$s['cats'][ $key ] = $slug;
			}
			$count = absint( $post['counts'][ $key ] ?? 0 );
			if ( $count && $count !== $info[2] ) {
				$s['counts'][ $key ] = min( 12, $count );
			}
		}
		$s['archive_months'] = min( 60, absint( $post['archive_months'] ?? 6 ) );
		$s['show_latest']    = ! empty( $post['show_latest'] );
	} elseif ( 'clanak' === $tab ) {
		foreach ( array_keys( dpp_article_options() ) as $key ) {
			$s['article'][ $key ] = ! empty( $post['article'][ $key ] );
		}
		$s['read_more_count'] = max( 1, min( 9, absint( $post['read_more_count'] ?? 3 ) ) );
	} elseif ( 'podnozje' === $tab ) {
		$s['footer_about'] = sanitize_textarea_field( $post['footer_about'] ?? '' );
		$s['footer_note']  = sanitize_text_field( $post['footer_note'] ?? '' );
		$s['footer_links'] = array();
		foreach ( (array) ( $post['footer_links'] ?? array() ) as $link ) {
			$label = sanitize_text_field( $link['label'] ?? '' );
			$url   = esc_url_raw( trim( $link['url'] ?? '' ) );
			if ( '' !== $label && '' !== $url ) {
				$s['footer_links'][] = array( 'label' => $label, 'url' => $url );
			}
		}
	} elseif ( 'kategorije' === $tab ) {
		$s['colors'] = array();
		foreach ( (array) ( $post['colors'] ?? array() ) as $term_id => $color ) {
			$color = sanitize_key( $color );
			if ( isset( dpp_colors()[ $color ] ) ) {
				$s['colors'][ absint( $term_id ) ] = $color;
			}
		}
	} elseif ( 'opcije' === $tab ) {
		$s['show_date'] = ! empty( $post['show_date'] );
		$s['new_days']  = min( 365, absint( $post['new_days'] ?? 30 ) );
	}

	update_option( 'dpp_settings', $s );
	wp_safe_redirect( add_query_arg( array( 'page' => 'dp-postavke', 'tab' => $tab, 'dpp_msg' => 'saved' ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_dpp_save', 'dpp_save_settings' );

/** Reset one tab to the theme's defaults. */
function dpp_reset_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	check_admin_referer( 'dpp_reset' );
	$tab    = sanitize_key( $_POST['tab'] ?? '' );
	$s      = dpp_settings();
	$d      = dpp_defaults();
	$fields = array(
		'naslovnica' => array( 'lead_id', 'order', 'hidden', 'subtitles', 'cats', 'counts', 'archive_months', 'show_latest' ),
		'clanak'     => array( 'article', 'read_more_count' ),
		'podnozje'   => array( 'footer_about', 'footer_note', 'footer_links' ),
		'kategorije' => array( 'colors' ),
		'opcije'     => array( 'show_date', 'new_days' ),
	);
	foreach ( $fields[ $tab ] ?? array() as $field ) {
		$s[ $field ] = $d[ $field ];
	}
	update_option( 'dpp_settings', $s );
	wp_safe_redirect( add_query_arg( array( 'page' => 'dp-postavke', 'tab' => $tab, 'dpp_msg' => 'reset' ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_dpp_reset', 'dpp_reset_settings' );
