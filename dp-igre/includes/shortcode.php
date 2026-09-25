<?php
/**
 * [dp_wordle] shortcode: put the game on any page or post.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'dp_wordle', 'dpig_render_shortcode' );

function dpig_render_shortcode() {
	wp_enqueue_style( 'dpig-wordle', DPIG_URL . 'assets/wordle.css', array(), DPIG_VERSION );
	wp_enqueue_script( 'dpig-google', 'https://accounts.google.com/gsi/client', array(), null, true );
	wp_enqueue_script( 'dpig-wordle', DPIG_URL . 'assets/wordle.js', array(), DPIG_VERSION, true );
	wp_localize_script(
		'dpig-wordle',
		'DPIG_CONFIG',
		array(
			'api'      => esc_url_raw( rest_url( 'dpig/v1/' ) ),
			'clientId' => trim( get_option( 'dpig_client_id', '' ) ),
			'domain'   => get_option( 'dpig_domain', '' ),
			'title'    => get_option( 'dpig_title', 'Riječ dana' ),
			'pageUrl'  => get_permalink(),
		)
	);

	return '<div class="dpig" id="dpig-root"><noscript>Za igru je potreban JavaScript.</noscript></div>';
}
