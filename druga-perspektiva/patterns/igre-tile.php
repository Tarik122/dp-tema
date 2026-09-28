<?php
/**
 * Title: Igre (traka za naslovnicu)
 * Slug: druga-perspektiva/igre-tile
 * Categories: druga-perspektiva
 * Keywords: igre, wordle, igra
 * Description: Plava traka sa karticom za svaku igru (podstranice stranice Igre) i linkom na /igre.
 * Inserter: true
 */
?>
<!-- wp:group {"className":"dp-igre-tile dp-part-igre","style":{"elements":{"link":{"color":{"text":"var:preset|color|papir"}}}},"backgroundColor":"plava-chip","textColor":"papir","layout":{"type":"default"}} -->
<div class="wp-block-group dp-igre-tile dp-part-igre has-papir-color has-plava-chip-background-color has-text-color has-background has-link-color">
	<!-- wp:group {"className":"dp-igre-head","layout":{"type":"default"}} -->
	<div class="wp-block-group dp-igre-head">
		<!-- wp:heading {"level":2,"className":"dp-igre-title"} -->
		<h2 class="wp-block-heading dp-igre-title"><a href="/igre/">Igre</a> <span class="dp-beta">beta</span></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"dp-igre-text"} -->
		<p class="dp-igre-text">Svaki dan nova riječ, nova linija i nova zagonetka. Za veliki odmor i put do kuće.</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"dp-igre-cta"} -->
		<p class="dp-igre-cta"><a href="/igre/">Sve igre</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"className":"dp-igre-links"} -->
	<p class="dp-igre-links">Ovdje se same pojavljuju kartice igara sa stranice Igre.</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
