<?php
/**
 * Druga perspektiva — funkcije teme.
 *
 * Sve što se ovdje dešava može se objasniti jednom rečenicom iznad svake
 * funkcije. Izgled je u theme.json i assets/css/theme.css, a raspored
 * blokova u templates/ i parts/.
 *
 * @package druga-perspektiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Query ID-evi koje koriste šabloni (atribut "queryId" u bloku Query Loop).
 * Ako ih mijenjate u šablonima, promijenite ih i ovdje.
 */
const DP_QUERY_LEAD      = 1; // Glavna priča na naslovnici.
const DP_QUERY_FEATURES  = 2; // Dva teksta lijevo od glavne priče.
const DP_QUERY_LATEST    = 3; // "Najnovije", desno od glavne priče.
const DP_QUERY_OPINION   = 4; // Blok "Mišljenje".
const DP_QUERY_ARCHIVE   = 5; // "Iz arhive".
const DP_QUERY_READ_MORE = 9; // "Pročitajte još" ispod članka.
const DP_QUERY_GAMES     = 12; // Sve igre, na stranici /igre.
const DP_QUERY_MORE_GAMES = 13; // "Više igara" ispod jedne igre.

/*
 * Igre: stranica sa ovim slugom (/igre) je početna za sve igre. Svaka igra
 * je posebna stranica kojoj je "Igre" roditeljska stranica (/igre/rijec/).
 */
const DP_GAMES_SLUG = 'igre';

/*
 * Rubrike na naslovnici: queryId => slug kategorije. Da promijenite koja se
 * rubrika prikazuje, promijenite slug ovdje i naslov/link u šablonu naslovnice.
 */
const DP_HOME_SECTIONS = array(
	6 => 'vijesti',
	7 => 'sport',
	8 => 'kultura',
	10 => 'nauka',
);

/*
 * Rubrike: slug kategorije => boja oznake (slug boje iz theme.json).
 * Slugovi su isti kao na starom sajtu da stari linkovi rade.
 * Nova kategorija koja nije ovdje dobija crnu oznaku.
 */
const DP_CATEGORY_COLORS = array(
	'vijestiskola'   => 'plava-chip',
	'ostale-vijesti' => 'plava-chip',
	'vijesti'        => 'plava-chip',
	'opinion'        => 'tinta',
	'sport'          => 'narandza-chip',
	'kultura'        => 'narandza-chip',
	'nauka'          => 'zelena',
);

/*
 * Kategorije koje se nikad ne prikazuju kao rubrika: "Izdvojeno" je samo
 * oznaka za naslovnicu, a ostalo su ostaci demo sadržaja starog sajta.
 */
const DP_HIDDEN_CATEGORIES = array( 'izdvojeno', 'zzdvojeno', 'uncategorized', 'arhiva', 'movies', 'music', 'news', 'ostalevijesti' );

/*
 * Rubrike čije se podrubrike prikazuju pod imenom roditelja. "Vijesti o školi"
 * i "Ostale vijesti" su na sajtu jedna rubrika: "Vijesti". Stari linkovi na
 * podrubrike i dalje rade.
 */
const DP_MERGED_PARENTS = array( 'vijesti' );

/** Kategorija čiji članci pune izdvojene module na naslovnici. */
const DP_FEATURED_CATEGORY = 'izdvojeno';

/** Kategorija za blok "Mišljenje". */
const DP_OPINION_CATEGORY = 'opinion';

/* -------------------------------------------------------------------------
 * Osnovno: stilovi, podrška, uzorci
 * ---------------------------------------------------------------------- */

/** Stilovi teme na sajtu. */
function dp_enqueue_assets() {
	wp_enqueue_style(
		'druga-perspektiva',
		get_theme_file_uri( 'assets/css/theme.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'dp_enqueue_assets' );

/** Isti stilovi u editoru, da urednici vide ono što čitaoci vide. */
function dp_setup() {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/theme.css' );
	remove_theme_support( 'core-block-patterns' );

	// Stranice dobijaju polje "Izvod": za igre je to kratak opis na kartici.
	add_post_type_support( 'page', 'excerpt' );
}
add_action( 'after_setup_theme', 'dp_setup' );

/** Stilovi blokova, kategorija uzoraka i izvor za vrijeme čitanja. */
function dp_register() {
	register_block_style(
		'core/paragraph',
		array(
			'name'  => 'pitanje',
			'label' => 'Pitanje (intervju)',
		)
	);

	register_block_style(
		'core/image',
		array(
			'name'  => 'siroka-fotografija',
			'label' => 'Široka fotografija',
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

/** "4 min čitanja", računato na 200 riječi u minuti. */
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

/* -------------------------------------------------------------------------
 * Datumi: "11. april 2026."
 * ---------------------------------------------------------------------- */

/**
 * Bosanski prevod WordPressa piše mjesece velikim slovom i u genitivu
 * ("11. Aprila 2026."). Ovdje ih mijenjamo u "11. april 2026.".
 */
function dp_bosnian_months( $date, $format ) {
	if ( false === strpos( $format, 'F' ) && false === strpos( $format, 'M' ) ) {
		return $date;
	}

	global $wp_locale;
	if ( ! $wp_locale ) {
		return $date;
	}

	$ours = array( 'januar', 'februar', 'mart', 'april', 'maj', 'juni', 'juli', 'august', 'septembar', 'oktobar', 'novembar', 'decembar' );
	$map  = array();

	for ( $i = 1; $i <= 12; $i++ ) {
		$key   = zeroise( $i, 2 );
		$month = $ours[ $i - 1 ];
		foreach ( array( $wp_locale->month[ $key ], $wp_locale->month_genitive[ $key ] ) as $theirs ) {
			$map[ $theirs ] = $month;
		}
	}

	// Duži nazivi prvi, da "Aprila" ne postane "aprila".
	uksort(
		$map,
		function ( $a, $b ) {
			return mb_strlen( $b ) - mb_strlen( $a );
		}
	);

	return strtr( $date, $map );
}
add_filter(
	'wp_date',
	function ( $date, $format ) {
		return dp_bosnian_months( $date, $format );
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Rubrike i oznake (chip)
 * ---------------------------------------------------------------------- */

/**
 * Glavna rubrika članka: najuža kategorija koja nije skrivena.
 * "Vijesti o školi" ima prednost nad svojim roditeljem "Vijesti".
 */
function dp_primary_category( $post_id ) {
	$terms = get_the_category( $post_id );
	if ( ! $terms ) {
		return null;
	}

	$visible = array_filter(
		$terms,
		function ( $term ) {
			return ! in_array( $term->slug, DP_HIDDEN_CATEGORIES, true );
		}
	);
	if ( ! $visible ) {
		return null;
	}

	// Djeca prije roditelja.
	usort(
		$visible,
		function ( $a, $b ) {
			return (int) ( 0 === $a->parent ) - (int) ( 0 === $b->parent );
		}
	);

	$term = $visible[0];

	// Podrubrike spojenih rubrika ("Vijesti o školi") prikazuju se kao roditelj ("Vijesti").
	if ( $term->parent ) {
		$parent = get_term( $term->parent, 'category' );
		if ( $parent && ! is_wp_error( $parent ) && in_array( $parent->slug, DP_MERGED_PARENTS, true ) ) {
			return $parent;
		}
	}

	return $term;
}

/** Boja oznake za kategoriju (slug boje iz theme.json). */
function dp_category_color( $term ) {
	if ( $term && isset( DP_CATEGORY_COLORS[ $term->slug ] ) ) {
		return DP_CATEGORY_COLORS[ $term->slug ];
	}
	if ( $term && $term->parent ) {
		$parent = get_term( $term->parent, 'category' );
		if ( $parent && ! is_wp_error( $parent ) && isset( DP_CATEGORY_COLORS[ $parent->slug ] ) ) {
			return DP_CATEGORY_COLORS[ $parent->slug ];
		}
	}
	return 'tinta';
}

/**
 * Blok "Post Terms" sa klasom "dp-chip" prikazuje samo jednu oznaku u boji
 * rubrike, kao na Instagramu, umjesto liste svih kategorija.
 */
function dp_render_chip( $content, $block, $instance ) {
	$class = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
	if ( false === strpos( $class, 'dp-chip' ) ) {
		return $content;
	}

	$post_id = isset( $instance->context['postId'] ) ? $instance->context['postId'] : get_the_ID();
	$term    = dp_primary_category( $post_id );
	if ( ! $term ) {
		return '';
	}

	return sprintf(
		'<div class="%1$s" style="--dp-chip: var(--wp--preset--color--%2$s)"><a href="%3$s">%4$s</a></div>',
		esc_attr( trim( 'wp-block-post-terms ' . $class ) ),
		esc_attr( dp_category_color( $term ) ),
		esc_url( get_category_link( $term ) ),
		esc_html( $term->name )
	);
}
add_filter( 'render_block_core/post-terms', 'dp_render_chip', 10, 3 );

/** Boja podvlake naslova na stranici rubrike. */
function dp_category_body_style() {
	if ( ! is_category() ) {
		return;
	}
	$color = dp_category_color( get_queried_object() );
	printf( "<style>body{--dp-rubrika:var(--wp--preset--color--%s)}</style>\n", esc_attr( $color ) );
}
add_action( 'wp_head', 'dp_category_body_style' );

/**
 * Blok "Categories" sa klasom "dp-rubrike": bez skrivenih kategorija i bez
 * podrubrika "Vijesti" (one su dio rubrike "Vijesti").
 * Prazne kategorije WordPress sam sakriva, a brojeve članaka ne prikazujemo.
 */
function dp_rubrike_before( $pre, $block ) {
	if ( 'core/categories' === $block['blockName'] && false !== strpos( $block['attrs']['className'] ?? '', 'dp-rubrike' ) ) {
		add_filter( 'get_terms_args', 'dp_rubrike_terms_args', 10, 2 );
	}
	return $pre;
}
add_filter( 'pre_render_block', 'dp_rubrike_before', 10, 2 );

/** ID-evi kategorija koje se ne prikazuju na listi rubrika. */
function dp_rubrike_excluded_ids() {
	static $ids = null;
	if ( null === $ids ) {
		$ids = get_terms(
			array(
				'taxonomy'   => 'category',
				'slug'       => DP_HIDDEN_CATEGORIES,
				'fields'     => 'ids',
				'hide_empty' => false,
			)
		);
		$ids = is_array( $ids ) ? $ids : array();

		// Podrubrike spojenih rubrika se ne nabrajaju; na listi je samo roditelj.
		foreach ( DP_MERGED_PARENTS as $slug ) {
			$parent = get_term_by( 'slug', $slug, 'category' );
			if ( $parent ) {
				$ids = array_merge( $ids, get_term_children( $parent->term_id, 'category' ) );
			}
		}
	}
	return $ids;
}

function dp_rubrike_terms_args( $args, $taxonomies ) {
	if ( in_array( 'category', (array) $taxonomies, true ) ) {
		// Filter se skida prije vlastitog upita, da se ne bi pozivao u krug.
		remove_filter( 'get_terms_args', 'dp_rubrike_terms_args', 10 );
		$args['exclude'] = dp_rubrike_excluded_ids();
		add_filter( 'get_terms_args', 'dp_rubrike_terms_args', 10, 2 );
	}
	return $args;
}

function dp_rubrike_after( $content, $block ) {
	if ( false === strpos( $block['attrs']['className'] ?? '', 'dp-rubrike' ) ) {
		return $content;
	}

	remove_filter( 'get_terms_args', 'dp_rubrike_terms_args', 10 );

	// Svaka rubrika dobija svoju boju (koristi se na naslovnici).
	return preg_replace_callback(
		'/<li class="cat-item cat-item-(\d+)/',
		function ( $m ) {
			$term = get_term( (int) $m[1], 'category' );
			return sprintf( '<li style="--dp-chip: var(--wp--preset--color--%s)" class="cat-item cat-item-%d', esc_attr( dp_category_color( $term ) ), (int) $m[1] );
		},
		$content
	);
}
add_filter( 'render_block_core/categories', 'dp_rubrike_after', 10, 2 );

/** Linkovi u meniju koji vode na praznu ili nepostojeću rubriku se ne prikazuju. */
function dp_hide_empty_category_links( $content, $block ) {
	$url = $block['attrs']['url'] ?? '';
	if ( ! preg_match( '#/category/(?:[^/]+/)*([^/?]+)/?$#', $url, $m ) ) {
		return $content;
	}

	$term = get_term_by( 'slug', sanitize_title( $m[1] ), 'category' );
	if ( ! $term ) {
		return '';
	}

	// Rubrika sa podrubrikama je prazna samo ako su i one prazne.
	$ids   = array_merge( array( $term->term_id ), get_term_children( $term->term_id, 'category' ) );
	$posts = get_posts(
		array(
			'category__in'   => $ids,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	return $posts ? $content : '';
}
add_filter( 'render_block_core/navigation-link', 'dp_hide_empty_category_links', 10, 2 );

/** Dugme za meni na telefonu piše "Rubrike" umjesto "Menu"/"Izbornik". */
function dp_menu_button_label( $content, $block ) {
	if ( false === strpos( $block['attrs']['className'] ?? '', 'dp-nav' ) ) {
		return $content;
	}
	return preg_replace(
		'#(<button[^>]*wp-block-navigation__responsive-container-open[^>]*>)(.*?)(</button>)#s',
		'$1Rubrike$3',
		$content,
		1
	);
}
add_filter( 'render_block_core/navigation', 'dp_menu_button_label', 10, 2 );

/* -------------------------------------------------------------------------
 * Logo
 * ---------------------------------------------------------------------- */

/**
 * Ako logo nije postavljen u editoru, blok "Site Logo" koristi DP logo
 * iz teme. Urednici ga mogu zamijeniti u Site Editoru.
 */
function dp_site_logo_fallback( $content, $block ) {
	if ( '' !== trim( $content ) ) {
		return $content;
	}

	$width = isset( $block['attrs']['width'] ) ? (int) $block['attrs']['width'] : 72;
	$class = trim( 'wp-block-site-logo ' . ( $block['attrs']['className'] ?? '' ) );

	return sprintf(
		'<div class="%1$s"><a href="%2$s" class="custom-logo-link" rel="home"><img class="custom-logo" src="%3$s" width="%4$d" height="%5$d" alt="%6$s"></a></div>',
		esc_attr( $class ),
		esc_url( home_url( '/' ) ),
		esc_url( get_theme_file_uri( 'assets/images/logo-dp.png' ) ),
		$width,
		(int) round( $width * 346 / 409 ),
		esc_attr( get_bloginfo( 'name' ) . ', naslovnica' )
	);
}
add_filter( 'render_block_core/site-logo', 'dp_site_logo_fallback', 10, 2 );

/**
 * Pri aktivaciji teme DP logo se jednom dodaje u biblioteku medija i
 * postavlja kao logo sajta, da ga urednici vide i u Site Editoru.
 * Ako sajt već ima logo, ništa se ne mijenja.
 */
function dp_set_default_logo() {
	if ( get_theme_mod( 'custom_logo' ) || get_option( 'site_logo' ) ) {
		return;
	}

	$source = get_theme_file_path( 'assets/images/logo-dp.png' );
	$upload = wp_upload_bits( 'druga-perspektiva-logo.png', null, file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! empty( $upload['error'] ) ) {
		return;
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => 'Druga perspektiva logo',
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Druga perspektiva' );
	set_theme_mod( 'custom_logo', $attachment_id );
	update_option( 'site_logo', $attachment_id );
}
add_action( 'after_switch_theme', 'dp_set_default_logo' );

/* -------------------------------------------------------------------------
 * Podnaslov (dek)
 * ---------------------------------------------------------------------- */

/**
 * Podnaslov ispod naslova prikazuje se samo kad autor napiše ručni izvod
 * (Excerpt), da se ne ponavlja prvi pasus teksta.
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

/* -------------------------------------------------------------------------
 * Naslovnica: ko ide u koji modul
 * ---------------------------------------------------------------------- */

/** ID-evi objavljenih članaka po zadanim uslovima, najnoviji prvi. */
function dp_post_ids( $args ) {
	return array_map(
		'intval',
		get_posts(
			array_merge(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'fields'              => 'ids',
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'suppress_filters'    => false,
				),
				$args
			)
		)
	);
}

/**
 * Raspored naslovnice, izračunat jednom po učitavanju:
 * - glavna priča: zalijepljeni (sticky) članak, inače najnoviji članak;
 * - izdvojeni i najnovije: sljedećih 3 + 8 članaka, strogo od najnovijeg;
 * - mišljenje: tri najnovija iz rubrike Mišljenje koja nisu već prikazana;
 * - iz arhive: tri izdvojena starija od šest mjeseci, mijenjaju se svaki dan.
 */
function dp_home_layout() {
	static $layout = null;
	if ( null !== $layout ) {
		return $layout;
	}

	$featured = get_term_by( 'slug', DP_FEATURED_CATEGORY, 'category' );
	$opinion  = get_term_by( 'slug', DP_OPINION_CATEGORY, 'category' );
	$shown    = array();

	// Glavna priča.
	$lead   = 0;
	$sticky = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts' ) ) );
	if ( $sticky ) {
		$ids  = dp_post_ids( array( 'post__in' => $sticky, 'numberposts' => 1 ) );
		$lead = $ids ? $ids[0] : 0;
	}
	if ( ! $lead ) {
		$ids  = dp_post_ids( array( 'numberposts' => 1 ) );
		$lead = $ids ? $ids[0] : 0;
	}
	if ( $lead ) {
		$shown[] = $lead;
	}

	// Izdvojeni i Najnovije: redom od najnovijeg, bez preskakanja.
	$stream   = dp_post_ids( array( 'numberposts' => 6, 'post__not_in' => $shown ) );
	$features = array_slice( $stream, 0, 2 );
	$latest   = array_slice( $stream, 2, 4 );
	$shown    = array_merge( $shown, $stream );

	// Mišljenje: najnovije kolumne koje već nisu gore.
	$opinions = $opinion ? dp_post_ids( array( 'cat' => $opinion->term_id, 'numberposts' => 3, 'post__not_in' => $shown ) ) : array();
	$shown    = array_merge( $shown, $opinions );

	// Rubrike: po četiri teksta iz svake, bez onih koji su već gore.
	$sections = array();
	foreach ( DP_HOME_SECTIONS as $query_id => $slug ) {
		$term                  = get_term_by( 'slug', $slug, 'category' );
		$sections[ $query_id ] = $term ? dp_post_ids( array( 'cat' => $term->term_id, 'numberposts' => 4, 'post__not_in' => $shown ) ) : array();
		$shown                 = array_merge( $shown, $sections[ $query_id ] );
	}

	// Iz arhive.
	$old_args = array(
		'numberposts'  => 50,
		'post__not_in' => $shown,
		'date_query'   => array( array( 'before' => '6 months ago' ) ),
	);
	$pool     = $featured ? dp_post_ids( array_merge( $old_args, array( 'cat' => $featured->term_id ) ) ) : array();
	if ( ! $pool ) {
		$pool = dp_post_ids( $old_args );
	}
	// Tri starija teksta; izbor se pomjera svaki dan.
	$archive = array();
	if ( $pool ) {
		$start = (int) floor( time() / DAY_IN_SECONDS ) % count( $pool );
		for ( $i = 0; $i < min( 3, count( $pool ) ); $i++ ) {
			$archive[] = $pool[ ( $start + $i ) % count( $pool ) ];
		}
	}

	$layout = array(
		DP_QUERY_LEAD     => $lead ? array( $lead ) : array(),
		DP_QUERY_FEATURES => $features,
		DP_QUERY_LATEST   => $latest,
		DP_QUERY_OPINION  => $opinions,
		DP_QUERY_ARCHIVE  => $archive,
	) + $sections;

	return $layout;
}

/**
 * "Pročitajte još": tri članka iz iste rubrike; ako ih nema dovoljno,
 * dopunjava se najnovijim člancima.
 */
function dp_read_more_ids( $post_id ) {
	$term = dp_primary_category( $post_id );
	$ids  = $term ? dp_post_ids( array( 'cat' => $term->term_id, 'numberposts' => 3, 'post__not_in' => array( $post_id ) ) ) : array();

	if ( count( $ids ) < 3 ) {
		$ids = array_merge( $ids, dp_post_ids( array( 'numberposts' => 3 - count( $ids ), 'post__not_in' => array_merge( array( $post_id ), $ids ) ) ) );
	}

	return $ids;
}

/** Svaki modul dobija svoje članke, bez ponavljanja. */
function dp_query_vars( $query, $block ) {
	$query_id = isset( $block->context['queryId'] ) ? (int) $block->context['queryId'] : 0;
	$ids      = null;

	if ( DP_QUERY_READ_MORE === $query_id ) {
		$ids = is_singular( 'post' ) ? dp_read_more_ids( get_queried_object_id() ) : dp_post_ids( array( 'numberposts' => 3 ) );
	} elseif ( DP_QUERY_GAMES === $query_id ) {
		$hub = is_page() ? get_queried_object_id() : dp_games_hub_id();
		$ids = dp_game_ids( $hub );
	} elseif ( DP_QUERY_MORE_GAMES === $query_id ) {
		$current = is_page() ? get_queried_object_id() : 0;
		$ids     = $current ? dp_game_ids( wp_get_post_parent_id( $current ), 3, $current ) : array();
	} elseif ( in_array( $query_id, array_merge( array( DP_QUERY_LEAD, DP_QUERY_FEATURES, DP_QUERY_LATEST, DP_QUERY_OPINION, DP_QUERY_ARCHIVE ), array_keys( DP_HOME_SECTIONS ) ), true ) ) {
		$layout = dp_home_layout();
		$ids    = $layout[ $query_id ];
	}

	if ( null === $ids ) {
		return $query;
	}

	if ( in_array( $query_id, array( DP_QUERY_GAMES, DP_QUERY_MORE_GAMES ), true ) ) {
		$query['post_type'] = 'page';
	}

	$query['post__in']            = $ids ? $ids : array( 0 );
	$query['orderby']             = 'post__in';
	$query['ignore_sticky_posts'] = true;
	$query['posts_per_page']      = max( 1, count( $ids ) );
	unset( $query['offset'], $query['cat'], $query['category__in'], $query['tax_query'] );

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'dp_query_vars', 10, 2 );

/**
 * Modul sa klasom "dp-min-N" ne prikazuje se uopšte (ni naslov) ako u njemu
 * ima manje od N članaka. Tako naslovnica nikad nema praznih rubrika.
 */
function dp_hide_small_modules( $content, $block ) {
	$class = $block['attrs']['className'] ?? '';
	if ( ! preg_match( '/\bdp-min-(\d+)\b/', $class, $m ) ) {
		return $content;
	}

	$count = preg_match_all( '/<li[^>]+class="[^"]*\bwp-block-post\b/', $content );

	return $count >= (int) $m[1] ? $content : '';
}
add_filter( 'render_block_core/query', 'dp_hide_small_modules', 10, 2 );

/* -------------------------------------------------------------------------
 * Opis i autor naslovne fotografije
 * ---------------------------------------------------------------------- */

/**
 * Ispod naslovne fotografije članka ispisuje se njen opis (Caption iz
 * biblioteke medija), npr. "Učenici na Igmanu. Foto: Ime Prezime".
 */
function dp_featured_image_caption( $content, $block, $instance ) {
	if ( false === strpos( $block['attrs']['className'] ?? '', 'dp-article-image' ) || '' === trim( $content ) ) {
		return $content;
	}

	$post_id = isset( $instance->context['postId'] ) ? $instance->context['postId'] : get_the_ID();
	$caption = wp_get_attachment_caption( get_post_thumbnail_id( $post_id ) );

	if ( ! $caption ) {
		return $content;
	}

	$figcaption = '<figcaption class="wp-element-caption">' . wp_kses_post( $caption ) . '</figcaption>';

	return preg_replace( '#</figure>\s*$#', $figcaption . '</figure>', $content, 1 );
}
add_filter( 'render_block_core/post-featured-image', 'dp_featured_image_caption', 10, 3 );

/* -------------------------------------------------------------------------
 * Naslov stranice s rezultatima pretrage
 * ---------------------------------------------------------------------- */

/** "Rezultati za „riječ“" umjesto engleskog "Search results for". */
function dp_search_title( $content, $block ) {
	if ( 'search' !== ( $block['attrs']['type'] ?? '' ) ) {
		return $content;
	}

	$term  = get_search_query();
	$title = '' === $term ? 'Pretraga' : sprintf( 'Rezultati za „%s“', esc_html( $term ) );

	return preg_replace( '#(<h1[^>]*>).*?(</h1>)#s', '$1' . $title . '$2', $content, 1 );
}
add_filter( 'render_block_core/query-title', 'dp_search_title', 10, 2 );

/* -------------------------------------------------------------------------
 * Naslovnica: citat
 * ---------------------------------------------------------------------- */

/** Traži prvi blok "Pullquote" sa tekstom (i unutar grupa i kolona). */
function dp_find_pullquote( $blocks ) {
	foreach ( $blocks as $block ) {
		if ( 'core/pullquote' === $block['blockName'] ) {
			$html = $block['innerHTML'];
			$cite = '';
			if ( preg_match( '#<cite[^>]*>(.*?)</cite>#s', $html, $m ) ) {
				$cite = trim( wp_strip_all_tags( $m[1] ) );
				$html = str_replace( $m[0], '', $html );
			}
			$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) );
			if ( mb_strlen( $text ) >= 20 && mb_strlen( $text ) <= 320 ) {
				return array( 'text' => $text, 'cite' => $cite );
			}
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = dp_find_pullquote( $block['innerBlocks'] );
			if ( $found ) {
				return $found;
			}
		}
	}
	return null;
}

/**
 * Grupa sa klasom "dp-citat" prikazuje najnoviji istaknuti citat (Pullquote)
 * iz objavljenih članaka, sa imenom i linkom na članak. Bez citata je nema.
 */
function dp_render_citat( $content, $block ) {
	if ( false === strpos( $block['attrs']['className'] ?? '', 'dp-citat' ) ) {
		return $content;
	}

	foreach ( get_posts( array( 'numberposts' => 30, 'no_found_rows' => true ) ) as $post ) {
		if ( ! has_block( 'core/pullquote', $post ) ) {
			continue;
		}
		$quote = dp_find_pullquote( parse_blocks( $post->post_content ) );
		if ( ! $quote ) {
			continue;
		}

		$cite  = $quote['cite'] ? sprintf( '<p class="dp-citat-who">%s</p>', esc_html( $quote['cite'] ) ) : '';
		$image = get_the_post_thumbnail( $post, 'large', array( 'class' => 'dp-citat-img', 'alt' => '', 'loading' => 'lazy' ) );
		$image = $image ? sprintf( '<a class="dp-citat-photo" href="%s" tabindex="-1" aria-hidden="true">%s</a>', esc_url( get_permalink( $post ) ), $image ) : '';

		return sprintf(
			'<figure class="wp-block-group dp-citat%1$s">%2$s<div class="dp-citat-body"><p class="dp-citat-label">Rečeno</p><blockquote><p class="dp-citat-text">%3$s</p></blockquote><figcaption>%4$s<p class="dp-citat-source">Iz teksta <a href="%5$s">%6$s</a></p></figcaption></div></figure>',
			$image ? ' has-photo' : '',
			$image,
			esc_html( trim( $quote['text'], " \t\n\"„“”" ) ),
			$cite,
			esc_url( get_permalink( $post ) ),
			esc_html( get_the_title( $post ) )
		);
	}

	return '';
}
add_filter( 'render_block_core/group', 'dp_render_citat', 10, 2 );

/* -------------------------------------------------------------------------
 * Članak: znak kraja teksta
 * ---------------------------------------------------------------------- */

/**
 * Veliko početno slovo: samo ako prvi pasus teksta počinje slovom (ne
 * navodnikom ili slikom) i dovoljno je dug da slovo ima smisla.
 */
function dp_drop_cap( $content, $m ) {
	if ( 0 !== strpos( ltrim( $content ), '<p' ) && ! preg_match( '#^\\s*<div[^>]*>\\s*<p#', $content ) ) {
		return $content;
	}

	$inner = $m[1][0][0];
	if ( mb_strlen( wp_strip_all_tags( $inner ) ) < 200 ) {
		return $content;
	}

	if ( ! preg_match( '/^(?:\\s|\\x{200B}|&nbsp;)*(\\p{Lu})/u', $inner, $letter, PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}

	$start = $m[1][0][1] + $letter[1][1];
	$len   = strlen( $letter[1][0] );

	return substr_replace( $content, '<span class="dp-dropcap">' . $letter[1][0] . '</span>', $start, $len );
}

/** Mali DP znak na kraju posljednjeg pasusa članka, kao u magazinima. */
function dp_end_mark( $content, $block ) {
	if ( ! is_singular( 'post' ) || false === strpos( $block['attrs']['className'] ?? '', 'dp-article-body' ) ) {
		return $content;
	}

	if ( ! preg_match_all( '#<p(?:\s[^>]*)?>(.*?)</p>#s', $content, $m, PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}

	$content = dp_drop_cap( $content, $m );
	preg_match_all( '#<p(?:\s[^>]*)?>(.*?)</p>#s', $content, $m, PREG_OFFSET_CAPTURE );

	for ( $i = count( $m[0] ) - 1; $i >= 0; $i-- ) {
		if ( '' !== trim( wp_strip_all_tags( $m[1][ $i ][0] ), " \t\n\r\0\x0B\xC2\xA0" ) ) {
			$end = $m[0][ $i ][1] + strlen( $m[0][ $i ][0] ) - 4;
			return substr_replace( $content, '<span class="dp-endmark" aria-hidden="true"></span>', $end, 0 );
		}
	}

	return $content;
}
add_filter( 'render_block_core/post-content', 'dp_end_mark', 10, 2 );

/* -------------------------------------------------------------------------
 * Igre: početna stranica za sve igre i stranica jedne igre
 * ---------------------------------------------------------------------- */

/** ID stranice /igre (0 ako je nema). */
function dp_games_hub_id() {
	static $id = null;
	if ( null === $id ) {
		$hub = get_page_by_path( DP_GAMES_SLUG );
		$id  = ( $hub && 'publish' === $hub->post_status ) ? (int) $hub->ID : 0;
	}
	return $id;
}

/**
 * Igre su objavljene podstranice stranice $parent_id. Redoslijed: polje
 * "Redoslijed" (Order) u postavkama stranice, pa po datumu objave (nova igra
 * ide na kraj). Tako svaka igra zadržava svoje mjesto i svoju boju.
 */
function dp_game_ids( $parent_id, $limit = 50, $exclude = 0 ) {
	if ( ! $parent_id ) {
		return array();
	}

	return array_map(
		'intval',
		get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'publish',
				'post_parent'      => (int) $parent_id,
				'numberposts'      => $limit,
				'post__not_in'     => $exclude ? array( (int) $exclude ) : array(),
				'orderby'          => array(
					'menu_order' => 'ASC',
					'date'       => 'ASC',
				),
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		)
	);
}

/**
 * Slova naziva kao pločice iz igre (prva riječ, najviše 7 slova). Prvo i
 * zadnje slovo su "pogođena" (narandžasta), treće je bijelo, ostala prazna.
 */
function dp_letter_tiles( $text, $max = 7 ) {
	$text  = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
	$words = preg_split( '/[^\p{L}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
	$chars = $words ? mb_str_split( mb_substr( mb_strtoupper( $words[0] ), 0, $max ) ) : array();
	$count = count( $chars );
	$tiles = '';

	foreach ( $chars as $i => $char ) {
		$class = '';
		if ( 0 === $i || ( $count > 3 && $count - 1 === $i ) ) {
			$class = ' class="is-hit"';
		} elseif ( 2 === $i ) {
			$class = ' class="is-near"';
		}
		$tiles .= sprintf( '<span%s>%s</span>', $class, esc_html( $char ) );
	}

	return '<span class="dp-tiles" aria-hidden="true">' . $tiles . '</span>';
}


/** Naslov sa klasom "dp-tiles-title" (stranica /igre) prikazuje se kao pločice. */
function dp_tiles_title( $content, $block, $instance ) {
	if ( false === strpos( $block['attrs']['className'] ?? '', 'dp-tiles-title' ) ) {
		return $content;
	}

	$post_id = isset( $instance->context['postId'] ) ? $instance->context['postId'] : get_the_ID();
	$title   = get_the_title( $post_id );
	$level   = isset( $block['attrs']['level'] ) ? (int) $block['attrs']['level'] : 2;

	return sprintf(
		'<h%1$d class="%4$s"><span class="screen-reader-text">%2$s</span>%3$s</h%1$d>',
		$level,
		esc_html( $title ),
		dp_letter_tiles( $title ),
		esc_attr( trim( 'wp-block-post-title ' . $block['attrs']['className'] ) )
	);
}
add_filter( 'render_block_core/post-title', 'dp_tiles_title', 10, 3 );

/** Igra bez istaknute slike dobija sliku od slova svog naziva. */
function dp_game_visual( $content, $block, $instance ) {
	$class = $block['attrs']['className'] ?? '';
	if ( false === strpos( $class, 'dp-igra-visual' ) || '' !== trim( $content ) ) {
		return $content;
	}

	$post_id = isset( $instance->context['postId'] ) ? $instance->context['postId'] : get_the_ID();

	return sprintf(
		'<figure class="wp-block-post-featured-image %1$s dp-igra-visual--tiles">%2$s</figure>',
		esc_attr( $class ),
		dp_letter_tiles( get_the_title( $post_id ) )
	);
}
add_filter( 'render_block_core/post-featured-image', 'dp_game_visual', 10, 3 );

/** Oznaka "Novo" na kartici igre stoji prvih 30 dana nakon objave. */
function dp_game_new_badge( $content, $block ) {
	if ( false === strpos( $block['attrs']['className'] ?? '', 'dp-igra-novo' ) ) {
		return $content;
	}
	$published = (int) get_post_time( 'U', true );
	return ( $published && time() - $published < 30 * DAY_IN_SECONDS ) ? $content : '';
}
add_filter( 'render_block_core/paragraph', 'dp_game_new_badge', 10, 2 );

/** Plava traka "Igre" na naslovnici nabraja igre (najviše pet). */
function dp_game_links( $content, $block ) {
	if ( false === strpos( $block['attrs']['className'] ?? '', 'dp-igre-links' ) ) {
		return $content;
	}

	$links = '';
	foreach ( dp_game_ids( dp_games_hub_id(), 5 ) as $id ) {
		$links .= sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $id ) ), esc_html( get_the_title( $id ) ) );
	}

	return $links ? '<p class="dp-igre-links">' . $links . '</p>' : '';
}
add_filter( 'render_block_core/paragraph', 'dp_game_links', 10, 2 );

/**
 * Podstranice stranice /igre automatski koriste šablon jedne igre
 * ("Igre: jedna igra"), osim ako je urednik ručno odabrao drugi šablon.
 */
function dp_game_page_template( $templates ) {
	$page = get_queried_object();
	if ( ! $page instanceof WP_Post || ! $page->post_parent || get_page_template_slug( $page ) ) {
		return $templates;
	}

	if ( (int) $page->post_parent === dp_games_hub_id() ) {
		array_unshift( $templates, 'page-igra.php' );
	}

	return $templates;
}
add_filter( 'page_template_hierarchy', 'dp_game_page_template' );

/* -------------------------------------------------------------------------
 * Sitnice
 * ---------------------------------------------------------------------- */

/**
 * Naslovi zalijepljeni iz Worda ili Google Docsa često imaju "tvrde" razmake
 * (&nbsp;) zbog kojih se red ne može prelomiti. Pri prikazu ih pretvaramo u
 * obične razmake; sačuvani naslov se ne mijenja.
 */
function dp_normal_spaces_in_titles( $title ) {
	if ( is_admin() ) {
		return $title;
	}
	return str_replace( array( "\xC2\xA0", '&nbsp;', '&#160;' ), ' ', $title );
}
add_filter( 'the_title', 'dp_normal_spaces_in_titles' );

/** Automatski izvod bez "[…]". */
add_filter(
	'excerpt_more',
	function () {
		return '…';
	}
);

/** Pretraga traži samo članke (ne stranice i priloge), od najnovijeg. */
function dp_search_posts_only( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		$query->set( 'post_type', 'post' );
		$query->set( 'orderby', 'date' );
		$query->set( 'order', 'DESC' );
	}
}
add_action( 'pre_get_posts', 'dp_search_posts_only' );

/**
 * Paragraf sa klasom "dp-danas" u zaglavlju prikazuje današnji datum,
 * npr. "Četvrtak, 24. septembar 2026.".
 */
function dp_render_today( $content, $block ) {
	if ( false === strpos( $block['attrs']['className'] ?? '', 'dp-danas' ) ) {
		return $content;
	}
	$date = wp_date( 'l, j. F Y.' );
	$date = mb_strtoupper( mb_substr( $date, 0, 1 ) ) . mb_substr( $date, 1 );
	return sprintf( '<p class="dp-danas"><time datetime="%s">%s</time></p>', esc_attr( wp_date( 'Y-m-d' ) ), esc_html( $date ) );
}
add_filter( 'render_block_core/paragraph', 'dp_render_today', 10, 2 );
