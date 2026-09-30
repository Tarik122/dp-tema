<?php
/**
 * Plugin Name:       DP objave
 * Description:       Instagram objave i storyji u stilu Druge perspektive, pravljeni iz članaka: naslovnica s fotografijom, tekst, citat. Sve se može urediti i preuzeti kao slika. Objave za mreže → Dodaj novu, ili "Napravi objavu" kod članka.
 * Version:           1.5.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Druga perspektiva
 * Text Domain:       dp-objave
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DPO_VERSION', '1.5.0' );
define( 'DPO_URL', plugin_dir_url( __FILE__ ) );
define( 'DPO_META', '_dpo_design' );

require_once __DIR__ . '/includes/article.php';

/* ---------- Vrsta sadržaja "Objava za mreže" ---------- */

add_action(
	'init',
	function () {
		register_post_type(
			'dp_objava',
			array(
				'labels'        => array(
					'name'               => 'Objave za mreže',
					'singular_name'      => 'Objava za mreže',
					'menu_name'          => 'Objave za mreže',
					'add_new'            => 'Dodaj novu',
					'add_new_item'       => 'Nova objava za mreže',
					'edit_item'          => 'Uredi objavu za mreže',
					'search_items'       => 'Pretraži objave',
					'not_found'          => 'Još nema objava. Napravite prvu iz nekog članka ("Napravi objavu") ili kliknite "Dodaj novu".',
					'not_found_in_trash' => 'U smeću nema objava.',
					'all_items'          => 'Sve objave',
				),
				'public'        => false,
				'show_ui'       => true,
				'show_in_menu'  => true,
				'menu_position' => 6,
				'menu_icon'     => 'dashicons-format-image',
				'supports'      => array( 'title' ),
				'map_meta_cap'  => true,
			)
		);
	}
);

// Klasičan ekran (bez blok-uređivača) sa velikim uređivačem objave.
add_filter(
	'use_block_editor_for_post_type',
	function ( $use, $type ) {
		return 'dp_objava' === $type ? false : $use;
	},
	10,
	2
);

add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return 'dp_objava' === $post->post_type ? 'Naziv (vidite ga samo vi, npr. "DGS Film Festival, objava")' : $text;
	},
	10,
	2
);

add_filter(
	'post_updated_messages',
	function ( $messages ) {
		$saved                  = 'Objava je spremljena. Slike preuzmite dugmetom "Preuzmi sve".';
		$messages['dp_objava'] = array_fill( 1, 10, $saved );
		$messages['dp_objava'][0] = '';
		return $messages;
	}
);

/* ---------- Uređivač ---------- */

add_action(
	'add_meta_boxes_dp_objava',
	function ( $post ) {
		add_meta_box( 'dpo-editor', 'Dizajn', 'dpo_editor_box', 'dp_objava', 'normal', 'high' );
	}
);

function dpo_editor_box( $post ) {
	$design = get_post_meta( $post->ID, DPO_META, true );
	if ( ! $design ) {
		$from   = absint( $_GET['dpo_from'] ?? 0 );
		$design = wp_json_encode( $from && current_user_can( 'edit_post', $from ) ? dpo_design_from_article( $from ) : dpo_blank_design() );
	}
	wp_nonce_field( 'dpo_save', 'dpo_nonce' );
	echo '<input type="hidden" name="dpo_design" id="dpo-design" value="' . esc_attr( $design ) . '">';
	echo '<div id="dpo-app"><p>Učitavanje uređivača…</p></div>';
}

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || 'dp_objava' !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'dpo-editor', DPO_URL . 'assets/editor.css', array(), DPO_VERSION );
		wp_enqueue_script( 'dpo-editor', DPO_URL . 'assets/editor.js', array( 'jquery' ), DPO_VERSION, true );
		wp_localize_script(
			'dpo-editor',
			'DPO',
			array(
				'fonts'    => array(
					'regular' => DPO_URL . 'assets/fonts/lato-400.woff2',
					'bold'    => DPO_URL . 'assets/fonts/lato-700.woff2',
					'black'     => DPO_URL . 'assets/fonts/lato-900.woff2',
					'italic'    => DPO_URL . 'assets/fonts/lato-400-italic.woff2',
				),
				'logo'     => array(
					'white' => DPO_URL . 'assets/logo-bijeli.png',
					'black' => DPO_URL . 'assets/logo-crni.png',
				),
				'colors'   => dpo_colors(),
				'articles' => dpo_recent_articles(),
			)
		);
	}
);

/** Boje: oznake (chip) i pozadine, uzete sa Instagram objava. */
function dpo_colors() {
	return array(
		'chip' => array(
			'plava'      => array( 'Plava', '#5271fe' ),
			'narandza'   => array( 'Narandžasta', '#ee8031' ),
			'zelena'     => array( 'Zelena', '#1f6b4f' ),
			'ljubicasta' => array( 'Ljubičasta', '#402f65' ),
			'crna'       => array( 'Crna', '#141414' ),
		),
		'bg'   => array(
			'svijetla'   => array( 'Svijetlosiva', '#ebebee' ),
			'ljubicasta' => array( 'Ljubičasta', '#402f65' ),
			'plava'      => array( 'Plava', '#5271fe' ),
			'crna'       => array( 'Crna', '#141414' ),
			'narandza'   => array( 'Narandžasta', '#ee8031' ),
			'bijela'     => array( 'Bijela', '#ffffff' ),
		),
	);
}

/** Zadnjih 40 članaka, za "Popuni iz članka" unutar uređivača. */
function dpo_recent_articles() {
	$out = array();
	foreach ( get_posts( array( 'numberposts' => 40, 'post_status' => array( 'publish', 'future', 'draft' ) ) ) as $p ) {
		$out[] = array( 'id' => $p->ID, 'title' => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ) );
	}
	return $out;
}

/* ---------- Spremanje ---------- */

add_action(
	'save_post_dp_objava',
	function ( $post_id ) {
		if ( ! isset( $_POST['dpo_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['dpo_nonce'] ), 'dpo_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$design = dpo_clean_design( json_decode( wp_unslash( $_POST['dpo_design'] ?? '' ), true ) );
		update_post_meta( $post_id, DPO_META, wp_slash( wp_json_encode( $design ) ) );
	}
);

/** Čisti dizajn prije spremanja: samo poznata polja, tekst bez HTML-a. */
function dpo_clean_design( $d ) {
	$d      = is_array( $d ) ? $d : array();
	$colors = dpo_colors();
	$hex    = function ( $v, $fallback ) {
		return is_string( $v ) && preg_match( '/^#[0-9a-f]{6}$/i', $v ) ? strtolower( $v ) : $fallback;
	};
	$num    = function ( $v, $min, $max, $fallback ) {
		return is_numeric( $v ) ? max( $min, min( $max, (float) $v ) ) : $fallback;
	};
	$text   = function ( $v, $len = 2000 ) {
		return mb_substr( sanitize_textarea_field( is_string( $v ) ? $v : '' ), 0, $len );
	};
	$out    = array(
		'format'  => in_array( $d['format'] ?? '', array( 'post', 'story' ), true ) ? $d['format'] : 'post',
		'style'   => 'dp' === ( $d['style'] ?? '' ) ? 'dp' : 'moderni',
		'article' => absint( $d['article'] ?? 0 ),
		'slides'  => array(),
	);
	foreach ( array_slice( (array) ( $d['slides'] ?? array() ), 0, 20 ) as $s ) {
		$type  = in_array( $s['t'] ?? '', array( 'cover', 'text', 'quote', 'score', 'table' ), true ) ? $s['t'] : 'cover';
		$slide = array(
			't'     => $type,
			'bg'    => $hex( $s['bg'] ?? '', $colors['bg']['ljubicasta'][1] ),
			'logo'  => ! empty( $s['logo'] ),
			'title' => $text( $s['title'] ?? '', 300 ),
		);
		// Slajdovi s fotografijom: naslovna, rezultat i raspored.
		if ( in_array( $type, array( 'cover', 'score', 'table' ), true ) ) {
			$slide['credit'] = $text( $s['credit'] ?? '', 120 );
			$slide['darken'] = $num( $s['darken'] ?? 0.8, 0, 1, 0.8 );
			$slide['blur']   = $num( $s['blur'] ?? 0, 0, 30, 0 );
			$slide['photos'] = array();
			foreach ( array_slice( (array) ( $s['photos'] ?? array() ), 0, 3 ) as $p ) {
				$slide['photos'][] = array(
					'id'   => absint( $p['id'] ?? 0 ),
					'url'  => esc_url_raw( $p['url'] ?? '' ),
					'zoom' => $num( $p['zoom'] ?? 1, 1, 4, 1 ),
					'ox'   => $num( $p['ox'] ?? 0, -1, 1, 0 ),
					'oy'   => $num( $p['oy'] ?? 0, -1, 1, 0 ),
				);
			}
		}
		if ( 'cover' === $type ) {
			$slide['chip']      = $text( $s['chip'] ?? '', 60 );
			$slide['chipColor'] = $hex( $s['chipColor'] ?? '', $colors['chip']['plava'][1] );
			$slide['byline']    = $text( $s['byline'] ?? '', 120 );
			$slide['size']      = $num( $s['size'] ?? 1, 0.6, 1.3, 1 );
		} elseif ( 'score' === $type ) {
			$slide['chip']      = $text( $s['chip'] ?? '', 60 );
			$slide['chipColor'] = $hex( $s['chipColor'] ?? '', $colors['chip']['narandza'][1] );
			$slide['home']      = $text( $s['home'] ?? '', 60 );
			$slide['away']      = $text( $s['away'] ?? '', 60 );
			$slide['homeScore'] = $text( $s['homeScore'] ?? '', 6 );
			$slide['awayScore'] = $text( $s['awayScore'] ?? '', 6 );
			$slide['ours']      = in_array( $s['ours'] ?? '', array( 'home', 'away' ), true ) ? $s['ours'] : '';
			$slide['detail']    = $text( $s['detail'] ?? '', 120 );
		} elseif ( 'table' === $type ) {
			$slide['sub']       = $text( $s['sub'] ?? '', 80 );
			$slide['rows']      = $text( $s['rows'] ?? '', 1500 );
			$slide['cellColor'] = $hex( $s['cellColor'] ?? '', $colors['chip']['narandza'][1] );
		} elseif ( 'text' === $type ) {
			$slide['body']  = $text( $s['body'] ?? '', 1500 );
			$slide['align'] = 'justify' === ( $s['align'] ?? '' ) ? 'justify' : 'left';
			$slide['caps']  = ! empty( $s['caps'] );
		} else {
			$slide['quote']   = $text( $s['quote'] ?? '', 600 );
			$slide['who']     = $text( $s['who'] ?? '', 120 );
			$slide['whoInfo'] = $text( $s['whoInfo'] ?? '', 160 );
		}
		$out['slides'][] = $slide;
	}
	if ( ! $out['slides'] ) {
		$out['slides'] = dpo_blank_design()['slides'];
	}
	return $out;
}

function dpo_blank_design() {
	return array(
		'format'  => 'post',
		'style'   => 'moderni',
		'article' => 0,
		'slides'  => array(
			array(
				't'         => 'cover',
				'bg'        => '#141414',
				'logo'      => true,
				'title'     => 'Naslov objave',
				'chip'      => 'Vijesti',
				'chipColor' => '#5271fe',
				'byline'    => '',
				'credit'    => '',
				'darken'    => 0.8,
				'blur'      => 0,
				'size'      => 1,
				'photos'    => array(),
			),
		),
	);
}

/* ---------- Veza s člancima ---------- */

// "Napravi objavu" ispod naslova u listi članaka.
add_filter(
	'post_row_actions',
	function ( $actions, $post ) {
		if ( 'post' === $post->post_type && current_user_can( 'edit_posts' ) ) {
			$actions['dpo'] = '<a href="' . esc_url( dpo_new_url( $post->ID ) ) . '">Napravi objavu</a>';
		}
		return $actions;
	},
	10,
	2
);

// Dugme u bočnoj traci članka.
add_action(
	'add_meta_boxes_post',
	function ( $post ) {
		add_meta_box(
			'dpo-from-article',
			'Instagram',
			function ( $post ) {
				if ( 'auto-draft' === $post->post_status ) {
					echo '<p>Spremite članak, pa ovdje napravite objavu za Instagram.</p>';
					return;
				}
				echo '<p><a class="button button-primary" href="' . esc_url( dpo_new_url( $post->ID ) ) . '">Napravi objavu</a></p>';
				echo '<p class="description">Naslov, rubrika, fotografija i citati se popune sami. Sve se poslije može promijeniti.</p>';
			},
			'post',
			'side',
			'low'
		);
	}
);

function dpo_new_url( $post_id ) {
	return add_query_arg( array( 'post_type' => 'dp_objava', 'dpo_from' => $post_id ), admin_url( 'post-new.php' ) );
}

// Naziv nove objave = naslov članka.
add_filter(
	'default_title',
	function ( $title, $post ) {
		$from = absint( $_GET['dpo_from'] ?? 0 );
		if ( 'dp_objava' === $post->post_type && $from && current_user_can( 'edit_post', $from ) ) {
			return html_entity_decode( get_the_title( $from ), ENT_QUOTES, 'UTF-8' );
		}
		return $title;
	},
	10,
	2
);

// Podaci članka za "Popuni iz članka" unutar uređivača.
add_action(
	'wp_ajax_dpo_article',
	function () {
		check_ajax_referer( 'dpo_save', 'nonce' );
		$id = absint( $_GET['id'] ?? 0 );
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( 'Nemate pristup tom članku.' );
		}
		wp_send_json_success( dpo_design_from_article( $id ) );
	}
);

// Kolona sa formatom u listi objava.
add_filter(
	'manage_dp_objava_posts_columns',
	function ( $cols ) {
		$cols['dpo_format'] = 'Format';
		return $cols;
	}
);
add_action(
	'manage_dp_objava_posts_custom_column',
	function ( $col, $post_id ) {
		if ( 'dpo_format' !== $col ) {
			return;
		}
		$d = json_decode( (string) get_post_meta( $post_id, DPO_META, true ), true );
		$n = count( $d['slides'] ?? array() );
		echo esc_html( ( 'story' === ( $d['format'] ?? '' ) ? 'Story' : 'Objava' ) . ', ' . $n . ( 1 === $n ? ' slajd' : ( $n < 5 ? ' slajda' : ' slajdova' ) ) );
	},
	10,
	2
);
