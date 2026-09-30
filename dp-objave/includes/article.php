<?php
/**
 * Pravi početni dizajn iz članka: naslovnica (fotografija, rubrika, naslov),
 * slajd s prvim pasusom i slajdovi s citatima iz teksta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dpo_design_from_article( $post_id ) {
	$post   = get_post( $post_id );
	$design = dpo_blank_design();
	if ( ! $post ) {
		return $design;
	}
	$colors = dpo_colors();
	$cat    = dpo_article_category( $post_id );
	$photo  = get_post_thumbnail_id( $post_id );
	$title  = html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' );

	$cover = array_merge(
		$design['slides'][0],
		array(
			'title'     => $title,
			'chip'      => $cat ? html_entity_decode( $cat->name, ENT_QUOTES, 'UTF-8' ) : '',
			'chipColor' => dpo_chip_hex( $cat ),
			'byline'    => dpo_byline( $post ),
			'credit'    => $photo ? dpo_photo_credit( $photo ) : '',
			'photos'    => $photo ? array( dpo_photo( $photo ) ) : array(),
		)
	);
	$slides = array( $cover );

	$paragraph = dpo_first_paragraph( $post );
	$excerpt   = has_excerpt( $post ) ? trim( wp_strip_all_tags( $post->post_excerpt ) ) : '';
	if ( $paragraph ) {
		$slides[] = array(
			't'     => 'text',
			'bg'    => $colors['bg']['ljubicasta'][1],
			'logo'  => true,
			'title' => $excerpt,
			'body'  => $paragraph,
			'align' => 'left',
		);
	}
	foreach ( array_slice( dpo_quotes( $post ), 0, 2 ) as $q ) {
		// "Ime Prezime, učenica 3. razreda" -> ime podebljano, opis sivo.
		$parts    = array_map( 'trim', explode( ',', $q['who'], 2 ) );
		$slides[] = array(
			't'       => 'quote',
			'bg'      => $colors['bg']['svijetla'][1],
			'logo'    => true,
			'title'   => '',
			'quote'   => $q['text'],
			'who'     => $parts[0],
			'whoInfo' => $parts[1] ?? '',
		);
	}

	$design['article'] = $post_id;
	$design['slides']  = $slides;
	return $design;
}

/** "Piše: Ime Prezime" (više autora ako ih dodatak Co-Authors Plus prikazuje). */
function dpo_byline( $post ) {
	$name = function_exists( 'coauthors' ) ? coauthors( ', ', ' i ', null, null, false ) : get_the_author_meta( 'display_name', $post->post_author );
	$name = trim( html_entity_decode( wp_strip_all_tags( (string) $name ), ENT_QUOTES, 'UTF-8' ) );
	return '' === $name || 'admin' === strtolower( $name ) ? '' : 'Piše: ' . $name;
}

/** Glavna rubrika članka (prva koja nije "Bez kategorije"). */
function dpo_article_category( $post_id ) {
	$first = null;
	foreach ( get_the_category( $post_id ) as $cat ) {
		// "Izdvojeno" i slične oznake nisu rubrike.
		if ( (int) get_option( 'default_category' ) === $cat->term_id || in_array( $cat->slug, array( 'izdvojeno', 'featured' ), true ) ) {
			continue;
		}
		// Prednost ima rubrika koja na sajtu ima svoju boju.
		if ( function_exists( 'dp_category_color' ) && 'tinta' !== dp_category_color( $cat ) ) {
			return $cat;
		}
		$first = $first ? $first : $cat;
	}
	return $first;
}

/** Boja oznake iz teme (ista kao na sajtu), inače plava. */
function dpo_chip_hex( $cat ) {
	$map = array(
		'plava-chip'    => '#5271fe',
		'narandza-chip' => '#ee8031',
		'zelena'        => '#1f6b4f',
		'tinta'         => '#141414',
	);
	if ( $cat && function_exists( 'dp_category_color' ) ) {
		$slug = dp_category_color( $cat );
		if ( isset( $map[ $slug ] ) && 'tinta' !== $slug ) {
			return $map[ $slug ];
		}
	}
	return $map['plava-chip'];
}

function dpo_photo( $id ) {
	return array(
		'id'   => (int) $id,
		'url'  => (string) wp_get_attachment_image_url( $id, 'full' ),
		'zoom' => 1,
		'ox'   => 0,
		'oy'   => 0,
	);
}

/** "Foto: Ime Prezime" iz opisa fotografije, ako postoji. */
function dpo_photo_credit( $id ) {
	$caption = wp_strip_all_tags( (string) wp_get_attachment_caption( $id ) );
	return preg_match( '/(Foto|Fotografija|Photo)\s*:\s*(.+)$/iu', $caption, $m ) ? 'Foto: ' . trim( $m[2] ) : '';
}

/** Prvi pravi pasus teksta (bez praznih i vrlo kratkih). */
function dpo_first_paragraph( $post ) {
	foreach ( dpo_blocks( parse_blocks( $post->post_content ) ) as $block ) {
		if ( 'core/paragraph' === $block['blockName'] ) {
			$text = dpo_plain( $block['innerHTML'] );
			if ( mb_strlen( $text ) > 80 ) {
				return mb_strlen( $text ) > 700 ? dpo_cut( $text, 700 ) : $text;
			}
		}
	}
	// Stari članci bez blokova.
	foreach ( preg_split( '/\n\s*\n/', wp_strip_all_tags( $post->post_content ) ) as $text ) {
		$text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) );
		if ( mb_strlen( $text ) > 80 ) {
			return dpo_cut( $text, 700 );
		}
	}
	return '';
}

/** Citati iz blokova Pull quote i Quote: tekst i ko je rekao. */
function dpo_quotes( $post ) {
	$out = array();
	foreach ( dpo_blocks( parse_blocks( $post->post_content ) ) as $block ) {
		if ( ! in_array( $block['blockName'], array( 'core/pullquote', 'core/quote' ), true ) ) {
			continue;
		}
		$html = render_block( $block );
		$who  = preg_match( '#<cite[^>]*>(.*?)</cite>#s', $html, $m ) ? dpo_plain( $m[1] ) : '';
		$html = preg_replace( '#<cite[^>]*>.*?</cite>#s', '', $html );
		$text = trim( dpo_plain( $html ), " \t\n\"“”„" );
		if ( $text ) {
			$out[] = array( 'text' => dpo_cut( $text, 400 ), 'who' => ltrim( $who, '—–- ' ) );
		}
	}
	return $out;
}

/** Svi blokovi, i oni unutar grupa i kolona. */
function dpo_blocks( $blocks ) {
	$out = array();
	foreach ( $blocks as $b ) {
		if ( ! empty( $b['blockName'] ) ) {
			$out[] = $b;
		}
		if ( ! empty( $b['innerBlocks'] ) && ! in_array( $b['blockName'], array( 'core/quote', 'core/pullquote' ), true ) ) {
			$out = array_merge( $out, dpo_blocks( $b['innerBlocks'] ) );
		}
	}
	return $out;
}

function dpo_plain( $html ) {
	return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' ) ) );
}

/** Skraćuje tekst na kraju rečenice (ili riječi) i dodaje "…". */
function dpo_cut( $text, $max ) {
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$part = mb_substr( $text, 0, $max );
	$stop = max( mb_strrpos( $part, '. ' ), mb_strrpos( $part, '! ' ), mb_strrpos( $part, '? ' ) );
	if ( $stop > $max * 0.5 ) {
		return mb_substr( $part, 0, $stop + 1 );
	}
	return rtrim( mb_substr( $part, 0, (int) mb_strrpos( $part, ' ' ) ), ',;: ' ) . '…';
}
