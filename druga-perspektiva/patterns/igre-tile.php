<?php
/**
 * Title: Igre (pločica za naslovnicu)
 * Slug: druga-perspektiva/igre-tile
 * Categories: druga-perspektiva
 * Keywords: igre, wordle, igra
 * Description: Plava pločica koja vodi na stranicu /igre.
 * Inserter: true
 */
?>
<!-- wp:group {"className":"dp-igre-tile","style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"},"blockGap":"0.9rem"},"elements":{"link":{"color":{"text":"var:preset|color|papir"}}}},"backgroundColor":"plava-chip","textColor":"papir","layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group dp-igre-tile has-papir-color has-plava-chip-background-color has-text-color has-background has-link-color" style="padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">
	<!-- wp:heading {"level":2,"className":"dp-igre-title"} -->
	<h2 class="wp-block-heading dp-igre-title"><a href="/igre/">Igre</a> <span class="dp-beta">beta</span></h2>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<div class="dp-igre-tiles" aria-hidden="true"><span class="is-hit">R</span><span>I</span><span class="is-near">J</span><span>E</span><span class="is-hit">Č</span></div>
	<!-- /wp:html -->

	<!-- wp:paragraph {"className":"dp-igre-text"} -->
	<p class="dp-igre-text">Pogodite skrivenu riječ u šest pokušaja. Igra je još u probnoj verziji, pa nam javite ako nešto ne radi.</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"className":"dp-igre-cta"} -->
	<p class="dp-igre-cta">Igrajte</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
