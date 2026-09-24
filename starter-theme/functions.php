<?php
/**
 * Druga perspektiva — funkcije teme.
 *
 * @package druga-perspektiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Query ID-evi koje koriste šabloni. Ako ih mijenjate u šablonima,
 * promijenite ih i ovdje.
 */
const DP_QUERY_LEAD  = 1; // Glavni članak na naslovnici.
const DP_QUERY_GRID  = 2; // Najnoviji članci ispod glavnog.
const DP_QUERY_REST  = 9; // "Pročitajte još" ispod članka.

/**
 * Stilovi teme (frontend i editor).
 */
function dp_enqueue_assets() {
	wp_enqueue_style(
		'druga-perspektiva',
		get_theme_file_uri( 'assets/css/theme.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'dp_enqueue_assets' );

function dp_setup() {
	add_editor_style( 'assets/css/theme.css' );
}
add_action( 'after_setup_theme', 'dp_setup' );

/**
 * Stilovi blokova, kategorija uzoraka i izvor za vrijeme čitanja.
 */
function dp_register() {
	register_block_style(
		'core/site-title',
		array(
			'name'  => 'second-view',
			'label' => 'Druga perspektiva',
		)
	);

	register_block_style(
		'core/paragraph',
		array(
			'name'  => 'pitanje',
			'label' => 'Pitanje (intervju)',
		)
	);

	register_block_pattern_category(
		'druga-perspektiva',
		array( 'label' => 'Druga perspektiva' )
	);

	if ( function_exists( 'register_block_bindings_source' ) ) {
		register_block_bindings_source(
			'druga-perspektiva/reading-time',
			array(
				'label'              => 'Vrijeme čitanja',
				'get_value_callback' => 'dp_reading_time',
				'uses_context'       => array( 'postId' ),
			)
		);
	}
}
add_action( 'init', 'dp_register' );

/**
 * "4 min čitanja", računato na 200 riječi u minuti.
 */
function dp_reading_time( $source_args, $block_instance ) {
	$post_id = isset( $block_instance->context['postId'] ) ? $block_instance->context['postId'] : get_the_ID();
	$post    = get_post( $post_id );

	if ( ! $post ) {
		return '';
	}

	$text    = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	$words   = count( preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) );
	$minutes = max( 1, (int) ceil( $words / 200 ) );

	return sprintf( '%d min čitanja', $minutes );
}

/**
 * Zaglavlje sa stilom "Druga perspektiva": dodaje data-text koji CSS
 * koristi za pomjerenu, obrubljenu kopiju naziva.
 */
function dp_site_title_data_text( $content, $block ) {
	$class = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';

	if ( false === strpos( $class, 'is-style-second-view' ) ) {
		return $content;
	}

	$tags = new WP_HTML_Tag_Processor( $content );
	if ( $tags->next_tag() ) {
		$tags->set_attribute( 'data-text', get_bloginfo( 'name' ) );
	}

	return $tags->get_updated_html();
}
add_filter( 'render_block_core/site-title', 'dp_site_title_data_text', 10, 2 );

/**
 * Podnaslov (standfirst) iznad članka prikazuje se samo kad autor
 * napiše ručni sažetak, da se ne ponavlja prvi pasus teksta.
 */
function dp_standfirst_only_if_manual( $content, $block, $instance ) {
	$class = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';

	if ( false === strpos( $class, 'dp-standfirst' ) ) {
		return $content;
	}

	$post_id = isset( $instance->context['postId'] ) ? $instance->context['postId'] : get_the_ID();

	return has_excerpt( $post_id ) ? $content : '';
}
add_filter( 'render_block_core/post-excerpt', 'dp_standfirst_only_if_manual', 10, 3 );

/**
 * Glavni članak: najnoviji "zalijepljeni" (sticky) članak ako postoji,
 * inače najnoviji objavljeni.
 */
function dp_lead_post_id() {
	static $lead = null;

	if ( null !== $lead ) {
		return $lead;
	}

	$lead   = 0;
	$sticky = get_option( 'sticky_posts' );

	if ( ! empty( $sticky ) ) {
		$ids = get_posts(
			array(
				'post__in'            => $sticky,
				'numberposts'         => 1,
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
			)
		);
		$lead = $ids ? (int) $ids[0] : 0;
	}

	return $lead;
}

function dp_query_vars( $query, $block ) {
	$query_id = isset( $block->context['queryId'] ) ? (int) $block->context['queryId'] : 0;
	$lead     = dp_lead_post_id();

	if ( DP_QUERY_LEAD === $query_id && $lead ) {
		$query['post__in'] = array( $lead );
		unset( $query['offset'] );
	}

	if ( DP_QUERY_GRID === $query_id && $lead ) {
		// Šablon preskače 1 članak (glavni). Ako je glavni sticky, preskakanje
		// ne treba: umjesto toga ga isključujemo.
		$query['post__not_in']   = isset( $query['post__not_in'] ) ? (array) $query['post__not_in'] : array();
		$query['post__not_in'][] = $lead;
		if ( ! empty( $query['offset'] ) ) {
			$query['offset'] = max( 0, (int) $query['offset'] - 1 );
		}
	}

	if ( DP_QUERY_REST === $query_id && is_singular() ) {
		$query['post__not_in']   = isset( $query['post__not_in'] ) ? (array) $query['post__not_in'] : array();
		$query['post__not_in'][] = get_queried_object_id();
	}

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'dp_query_vars', 10, 2 );
