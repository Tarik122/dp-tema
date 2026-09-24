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
const DP_QUERY_FEATURES  = 2; // Tri izdvojena teksta ispod glavne priče.
const DP_QUERY_LATEST    = 3; // "Najnovije", lista naslova.
const DP_QUERY_OPINION   = 4; // Blok "Mišljenje".
const DP_QUERY_ARCHIVE   = 5; // "Iz arhive".
const DP_QUERY_READ_MORE = 9; // "Pročitajte još" ispod članka.

/*
 * Rubrike na naslovnici: queryId => slug kategorije. Da promijenite koja se
 * rubrika prikazuje, promijenite slug ovdje i naslov/link u šablonu naslovnice.
 */
const DP_HOME_SECTIONS = array(
	6 => 'vijestiskola',
	7 => 'sport',
	8 => 'kultura',
);

/*
 * Rubrike: slug kategorije => boja oznake (slug boje iz theme.json).
 * Slugovi su isti kao na starom sajtu da stari linkovi rade.
 * Nova kategorija koja nije ovdje dobija navy oznaku.
 */
const DP_CATEGORY_COLORS = array(
	'vijestiskola'   => 'plava-chip',
	'ostale-vijesti' => 'plava-chip',
	'vijesti'        => 'plava-chip',
	'opinion'        => 'navy',
	'sport'          => 'narandza-chip',
	'kultura'        => 'narandza-chip',
	'nauka'          => 'zelena',
);

/*
 * Kategorije koje se nikad ne prikazuju kao rubrika: "Izdvojeno" je samo
 * oznaka za naslovnicu, a ostalo su ostaci demo sadržaja starog sajta.
 */
const DP_HIDDEN_CATEGORIES = array( 'izdvojeno', 'zzdvojeno', 'uncategorized', 'arhiva', 'movies', 'music', 'news', 'ostalevijesti' );

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

	return $visible[0];
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
	return 'navy';
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
 * Blok "Categories" sa klasom "dp-rubrike": bez skrivenih kategorija i
 * bez roditelja "Vijesti" (njegove podrubrike su već na listi).
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
				'slug'       => array_merge( DP_HIDDEN_CATEGORIES, array( 'vijesti' ) ),
				'fields'     => 'ids',
				'hide_empty' => false,
			)
		);
		$ids = is_array( $ids ) ? $ids : array();
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
 * - glavna priča: zalijepljeni (sticky) članak, inače najnoviji "Izdvojeno",
 *   inače najnoviji članak;
 * - izdvojeni: sljedeća tri "Izdvojeno", dopunjeno najnovijim;
 * - najnovije: pet najnovijih koji nisu već prikazani;
 * - mišljenje: tri najnovija iz rubrike Mišljenje koja nisu već prikazana;
 * - iz arhive: jedan izdvojeni stariji od šest mjeseci, mijenja se svaki dan.
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
	if ( ! $lead && $featured ) {
		$ids  = dp_post_ids( array( 'cat' => $featured->term_id, 'numberposts' => 1 ) );
		$lead = $ids ? $ids[0] : 0;
	}
	if ( ! $lead ) {
		$ids  = dp_post_ids( array( 'numberposts' => 1 ) );
		$lead = $ids ? $ids[0] : 0;
	}
	if ( $lead ) {
		$shown[] = $lead;
	}

	// Izdvojeni.
	$features = $featured ? dp_post_ids( array( 'cat' => $featured->term_id, 'numberposts' => 3, 'post__not_in' => $shown ) ) : array();
	if ( count( $features ) < 3 ) {
		$features = array_merge( $features, dp_post_ids( array( 'numberposts' => 3 - count( $features ), 'post__not_in' => array_merge( $shown, $features ) ) ) );
	}
	$shown = array_merge( $shown, $features );

	// Mišljenje (prije liste najnovijih, da kolumne ostanu u svom bloku).
	$opinions = $opinion ? dp_post_ids( array( 'cat' => $opinion->term_id, 'numberposts' => 3, 'post__not_in' => $shown ) ) : array();
	$shown    = array_merge( $shown, $opinions );

	// Najnovije.
	$latest = dp_post_ids( array( 'numberposts' => 6, 'post__not_in' => $shown ) );
	$shown  = array_merge( $shown, $latest );

	// Rubrike: po tri teksta iz svake, bez onih koji su već gore.
	$sections = array();
	foreach ( DP_HOME_SECTIONS as $query_id => $slug ) {
		$term                  = get_term_by( 'slug', $slug, 'category' );
		$sections[ $query_id ] = $term ? dp_post_ids( array( 'cat' => $term->term_id, 'numberposts' => 3, 'post__not_in' => $shown ) ) : array();
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
	$archive = $pool ? array( $pool[ (int) floor( time() / DAY_IN_SECONDS ) % count( $pool ) ] ) : array();

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
	} elseif ( in_array( $query_id, array_merge( array( DP_QUERY_LEAD, DP_QUERY_FEATURES, DP_QUERY_LATEST, DP_QUERY_OPINION, DP_QUERY_ARCHIVE ), array_keys( DP_HOME_SECTIONS ) ), true ) ) {
		$layout = dp_home_layout();
		$ids    = $layout[ $query_id ];
	}

	if ( null === $ids ) {
		return $query;
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
 * Sitnice
 * ---------------------------------------------------------------------- */

/** Automatski izvod bez "[…]". */
add_filter(
	'excerpt_more',
	function () {
		return '…';
	}
);

/** Pretraga traži samo članke, ne stranice i priloge. */
function dp_search_posts_only( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		$query->set( 'post_type', 'post' );
	}
}
add_action( 'pre_get_posts', 'dp_search_posts_only' );
