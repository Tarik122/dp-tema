<?php
/**
 * Admin screens: WP admin → Druga perspektiva.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Druga perspektiva', 'Druga perspektiva', 'manage_options', 'dp-postavke', 'dpp_admin_page', 'dashicons-admin-customizer', 3 );
	}
);

function dpp_admin_tabs() {
	return array(
		'naslovnica' => 'Naslovnica',
		'clanak'     => 'Članak',
		'podnozje'   => 'Podnožje',
		'kategorije' => 'Kategorije',
		'opcije'     => 'Opcije',
		'ciscenje'   => 'Čišćenje',
	);
}

function dpp_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tabs = dpp_admin_tabs();
	$tab  = isset( $_GET['tab'], $tabs[ $_GET['tab'] ] ) ? sanitize_key( $_GET['tab'] ) : 'naslovnica';
	$msg  = sanitize_key( $_GET['dpp_msg'] ?? '' );

	echo '<div class="wrap dpp"><h1>Druga perspektiva</h1>';
	if ( 'druga-perspektiva' !== get_template() ) {
		echo '<div class="notice notice-warning"><p>Tema <strong>Druga perspektiva</strong> nije uključena, pa postavke s ovih stranica neće imati efekta.</p></div>';
	}
	if ( 'saved' === $msg || 'reset' === $msg ) {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s <a href="%s" target="_blank">Pogledaj sajt</a></p></div>',
			'saved' === $msg ? 'Spremljeno.' : 'Vraćene su postavke teme.',
			esc_url( home_url( '/' ) )
		);
	}
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $key => $label ) {
		printf(
			'<a href="%s" class="nav-tab %s">%s</a>',
			esc_url( add_query_arg( array( 'page' => 'dp-postavke', 'tab' => $key ), admin_url( 'admin.php' ) ) ),
			$key === $tab ? 'nav-tab-active' : '',
			esc_html( $label )
		);
	}
	echo '</nav>';
	call_user_func( 'dpp_admin_tab_' . $tab, $msg );
	echo '</div>';
	dpp_admin_styles();
}

function dpp_form_open( $tab ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="dpp_save"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
	wp_nonce_field( 'dpp_save' );
}

/** Save button, and a separate small form to go back to the theme's defaults. */
function dpp_form_close( $tab ) {
	submit_button( 'Spremi' );
	echo '</form>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Vratiti postavke teme na ovoj kartici?\');">';
	echo '<input type="hidden" name="action" value="dpp_reset"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
	wp_nonce_field( 'dpp_reset' );
	echo '<button type="submit" class="button-link dpp-reset">Vrati postavke teme za ovu karticu</button></form>';
}

function dpp_category_select( $name, $selected, $placeholder = '' ) {
	$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
	$html  = '<select name="' . esc_attr( $name ) . '">';
	foreach ( is_array( $terms ) ? $terms : array() as $t ) {
		$html .= sprintf( '<option value="%s" %s>%s (%d)</option>', esc_attr( $t->slug ), selected( $selected, $t->slug, false ), esc_html( $t->name ), (int) $t->count );
	}
	return $html . '</select>';
}

/* ---------- Naslovnica ---------- */

function dpp_admin_tab_naslovnica() {
	$s       = dpp_settings();
	$posts   = get_posts( array( 'numberposts' => 60, 'post_status' => 'publish' ) );
	$subs    = dpp_subtitles();
	$names   = dpp_parts();
	$sources = dpp_sources();

	dpp_form_open( 'naslovnica' );

	echo '<h2>Glavna priča</h2>';
	echo '<p>Velika priča na vrhu naslovnice.</p>';
	echo '<select name="lead_id" style="max-width:100%"><option value="0">Automatski: zakačeni članak, a ako ga nema, najnoviji</option>';
	foreach ( $posts as $p ) {
		printf( '<option value="%d" %s>%s (%s)</option>', (int) $p->ID, selected( (int) $s['lead_id'], (int) $p->ID, false ), esc_html( get_the_title( $p ) ), esc_html( get_the_date( 'j. n. Y.', $p ) ) );
	}
	echo '</select>';
	printf( '<p><label><input type="checkbox" name="show_latest" value="1" %s> Stupac "Najnovije" desno od glavne priče (samo na većim ekranima)</label></p>', checked( $s['show_latest'], true, false ) );

	echo '<h2>Rubrike na naslovnici</h2>';
	echo '<p>Redoslijed mijenjate strelicama. Rubrika bez kvačice se ne prikazuje. Vrh naslovnice je uvijek prvi. Prazan podnaslov znači da ostaje tekst iz teme.</p>';
	echo '<table class="widefat dpp-parts"><thead><tr><th>Prikaži</th><th>Rubrika</th><th>Kategorija i broj članaka</th><th>Podnaslov</th><th>Redoslijed</th></tr></thead><tbody>';
	foreach ( $s['order'] as $key ) {
		if ( ! isset( $names[ $key ] ) ) {
			continue;
		}
		echo '<tr>';
		printf( '<td><input type="checkbox" name="shown[]" value="%1$s" %2$s aria-label="Prikaži %3$s"><input type="hidden" name="order[]" value="%1$s"></td>', esc_attr( $key ), checked( ! in_array( $key, (array) $s['hidden'], true ), true, false ), esc_attr( $names[ $key ] ) );
		echo '<td><strong>' . esc_html( $names[ $key ] ) . '</strong></td><td>';
		foreach ( dpp_part_sources( $key ) as $src ) {
			$info = $sources[ $src ];
			echo '<div class="dpp-src">';
			if ( count( dpp_part_sources( $key ) ) > 1 ) {
				echo '<span class="dpp-src-label">' . esc_html( $info[0] ) . ':</span> ';
			}
			echo dpp_category_select( 'cats[' . $src . ']', $s['cats'][ $src ] ?? $info[1] ); // phpcs:ignore WordPress.Security.EscapeOutput
			printf( ' <input type="number" min="1" max="12" name="counts[%s]" value="%d" class="small-text" aria-label="Broj članaka"> čl.', esc_attr( $src ), (int) ( $s['counts'][ $src ] ?? $info[2] ) );
			echo '</div>';
		}
		if ( 'igre' === $key ) {
			echo '<span class="description">Igre sa stranice Igre</span>';
		} elseif ( 'citat' === $key ) {
			echo '<span class="description">Najnoviji istaknuti citat (blok Pullquote)</span>';
		}
		echo '</td><td>';
		$sub_keys = 'sportnauka' === $key ? array( 'sport', 'nauka' ) : ( isset( $subs[ $key ] ) ? array( $key ) : array() );
		foreach ( $sub_keys as $sk ) {
			printf( '<input type="text" class="regular-text dpp-sub" name="subtitles[%s]" value="%s" placeholder="%s" aria-label="Podnaslov">', esc_attr( $sk ), esc_attr( $s['subtitles'][ $sk ] ?? '' ), esc_attr( $subs[ $sk ] ) );
		}
		echo '</td><td class="dpp-move"><button type="button" class="button dpp-up" aria-label="Pomjeri gore">↑</button> <button type="button" class="button dpp-down" aria-label="Pomjeri dole">↓</button></td></tr>';
	}
	echo '</tbody></table>';
	printf( '<p><label>"Iz arhive" uzima tekstove starije od <input type="number" min="0" max="60" name="archive_months" value="%d" class="small-text"> mjeseci. Izbor se mijenja svaki dan.</label></p>', (int) $s['archive_months'] );

	dpp_form_close( 'naslovnica' );
	?>
	<script>
	document.querySelectorAll('.dpp-parts .dpp-up, .dpp-parts .dpp-down').forEach(function (b) {
		b.addEventListener('click', function () {
			var row = b.closest('tr');
			if (b.classList.contains('dpp-up') && row.previousElementSibling) row.parentNode.insertBefore(row, row.previousElementSibling);
			if (b.classList.contains('dpp-down') && row.nextElementSibling) row.parentNode.insertBefore(row.nextElementSibling, row);
			b.focus();
		});
	});
	</script>
	<?php
}

/* ---------- Članak ---------- */

function dpp_admin_tab_clanak() {
	$s = dpp_settings();
	dpp_form_open( 'clanak' );
	echo '<h2>Dijelovi članka</h2><fieldset>';
	foreach ( dpp_article_options() as $key => $label ) {
		printf( '<p><label><input type="checkbox" name="article[%s]" value="1" %s> %s</label></p>', esc_attr( $key ), checked( ! empty( $s['article'][ $key ] ), true, false ), esc_html( $label ) );
	}
	echo '</fieldset>';
	printf( '<p><label>"Pročitajte još" prikazuje <input type="number" min="1" max="9" name="read_more_count" value="%d" class="small-text"> članka (prvo iz iste rubrike, pa najnovije).</label></p>', (int) $s['read_more_count'] );
	echo '<p class="description">Autor piše svoju biografiju za okvir o autoru u <a href="' . esc_url( admin_url( 'profile.php' ) ) . '">Korisnici → Profil → Biografske informacije</a>.</p>';
	dpp_form_close( 'clanak' );
}

/* ---------- Podnožje ---------- */

function dpp_admin_tab_podnozje() {
	$s     = dpp_settings();
	$links = $s['footer_links'] ? $s['footer_links'] : array(
		array( 'label' => 'Igre', 'url' => home_url( '/igre/' ) ),
		array( 'label' => 'Instagram: @drugaperspektiva.dgs', 'url' => 'https://www.instagram.com/drugaperspektiva.dgs/' ),
	);
	$links = array_pad( $links, 5, array( 'label' => '', 'url' => '' ) );
	dpp_form_open( 'podnozje' );
	echo '<table class="form-table"><tbody>';
	printf( '<tr><th scope="row"><label for="dpp-about">Tekst o novinama</label></th><td><textarea id="dpp-about" name="footer_about" rows="3" class="large-text" placeholder="%s">%s</textarea><p class="description">Prazno znači da ostaje tekst iz teme.</p></td></tr>', esc_attr( 'Urednički i finansijski nezavisne novine koje pišu i uređuju učenici Druge gimnazije Sarajevo. Novine je pokrenuo Tarik Bećarević.' ), esc_textarea( $s['footer_about'] ) );
	echo '<tr><th scope="row">Linkovi</th><td><table class="dpp-links"><thead><tr><th>Tekst</th><th>Adresa</th></tr></thead><tbody>';
	foreach ( $links as $i => $l ) {
		printf( '<tr><td><input type="text" name="footer_links[%1$d][label]" value="%2$s" class="regular-text" aria-label="Tekst linka"></td><td><input type="url" name="footer_links[%1$d][url]" value="%3$s" class="regular-text" aria-label="Adresa linka" placeholder="https://"></td></tr>', (int) $i, esc_attr( $l['label'] ), esc_attr( $l['url'] ) );
	}
	echo '</tbody></table><p class="description">Prazni redovi se preskaču. Npr. Instagram, e-mail redakcije (mailto:ime@primjer.ba), TikTok.</p></td></tr>';
	printf( '<tr><th scope="row"><label for="dpp-note">Mala poruka na dnu</label></th><td><input type="text" id="dpp-note" name="footer_note" value="%s" class="large-text" placeholder="%s"></td></tr>', esc_attr( $s['footer_note'] ), esc_attr( 'Imate priču ili prijedlog? Pišite nam na Instagramu.' ) );
	echo '</tbody></table>';
	echo '<p class="description">Rubrike u podnožju se prikazuju same (samo one koje imaju članke).</p>';
	dpp_form_close( 'podnozje' );
}

/* ---------- Kategorije ---------- */

function dpp_admin_tab_kategorije() {
	$s     = dpp_settings();
	$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false, 'parent' => 0 ) );
	dpp_form_open( 'kategorije' );
	echo '<p>Boja oznake rubrike iznad naslova (kao na Instagramu). Podrubrike dobijaju boju svoje rubrike.</p>';
	echo '<table class="widefat dpp-colors"><thead><tr><th>Kategorija</th><th>Boja</th><th>Izgled</th></tr></thead><tbody>';
	foreach ( is_array( $terms ) ? $terms : array() as $t ) {
		$current = $s['colors'][ $t->term_id ] ?? ( function_exists( 'dp_category_color' ) ? dp_category_color( $t ) : 'tinta' );
		echo '<tr><td><strong>' . esc_html( $t->name ) . '</strong> <span class="description">(' . (int) $t->count . ')</span></td><td><select name="colors[' . (int) $t->term_id . ']">';
		foreach ( dpp_colors() as $slug => $name ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $slug ), selected( $current, $slug, false ), esc_html( $name ) );
		}
		printf( '</select></td><td><span class="dpp-chip" style="background:var(--wp--preset--color--%1$s, %2$s)">%3$s</span></td></tr>', esc_attr( $current ), esc_attr( array( 'plava-chip' => '#3B57E8', 'narandza-chip' => '#B34E0F', 'zelena' => '#1F6B4F', 'tinta' => '#141414' )[ $current ] ?? '#141414' ), esc_html( $t->name ) );
	}
	echo '</tbody></table>';
	dpp_form_close( 'kategorije' );
}

/* ---------- Opcije ---------- */

function dpp_admin_tab_opcije() {
	$s = dpp_settings();
	dpp_form_open( 'opcije' );
	echo '<table class="form-table"><tbody>';
	printf( '<tr><th scope="row">Datum u zaglavlju</th><td><label><input type="checkbox" name="show_date" value="1" %s> Prikaži današnji datum lijevo od logotipa (na većim ekranima)</label></td></tr>', checked( $s['show_date'], true, false ) );
	printf( '<tr><th scope="row"><label for="dpp-new-days">Oznaka "Novo!" na igrama</label></th><td><input type="number" min="0" max="365" id="dpp-new-days" name="new_days" value="%d" class="small-text"> dana nakon objave igre (0 = nikad)</td></tr>', (int) $s['new_days'] );
	echo '</tbody></table>';
	dpp_form_close( 'opcije' );

	echo '<h2>Gdje je ostalo?</h2><ul class="ul-disc">';
	echo '<li><strong>Meni i logo:</strong> <a href="' . esc_url( admin_url( 'site-editor.php' ) ) . '">Izgled → Editor</a> → Zaglavlje.</li>';
	echo '<li><strong>Izvodi (sažeci) članaka:</strong> u samom članku, desna kolona → Izvod.</li>';
	if ( dpp_plugin_active( 'dp-igre' ) ) {
		echo '<li><strong>Igre (Riječ dana, Kontekst):</strong> <a href="' . esc_url( admin_url( 'admin.php?page=dpig' ) ) . '">Riječ dana</a>.</li>';
	}
	echo '</ul>';
}

/* ---------- Čišćenje ---------- */

function dpp_admin_tab_ciscenje( $msg ) {
	if ( 'backup' === $msg ) {
		echo '<div class="notice notice-error"><p>Označite da imate sigurnosnu kopiju, pa ponovo kliknite Očisti.</p></div>';
	}
	if ( 'cleaned' === $msg ) {
		$report = get_transient( 'dpp_cleanup_report_' . get_current_user_id() );
		delete_transient( 'dpp_cleanup_report_' . get_current_user_id() );
		if ( is_array( $report ) ) {
			$scan  = dpp_cleanup_scan();
			$lines = array();
			foreach ( $report as $key => $n ) {
				$lines[] = esc_html( ( $scan[ $key ]['title'] ?? $key ) . ': ' . $n );
			}
			echo '<div class="notice notice-success"><p><strong>Očišćeno.</strong><br>' . ( $lines ? implode( '<br>', $lines ) : 'Ništa nije bilo izabrano.' ) . '</p></div>';
		}
	}

	$groups = dpp_cleanup_scan();
	$any    = false;

	echo '<p>Ovdje su stvari koje je ostavila stara tema i dodaci koji se više ne koriste. Pregledajte listu, označite šta želite obrisati i kliknite <strong>Očisti izabrano</strong>. Ništa se ne briše dok ne kliknete.</p>';
	echo '<p><strong>Prije čišćenja napravite sigurnosnu kopiju baze</strong> (na hostingu ili dodatkom kao što je UpdraftPlus). Većina brisanja se ne može poništiti.</p>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="dpp_cleanup">';
	wp_nonce_field( 'dpp_cleanup' );
	echo '<div class="dpp-groups">';
	foreach ( $groups as $key => $g ) {
		$count = (int) ( $g['count'] ?? count( $g['items'] ) );
		echo '<div class="dpp-group' . ( $count ? '' : ' is-empty' ) . '">';
		if ( $count ) {
			$any = true;
			printf( '<label class="dpp-group-title"><input type="checkbox" name="groups[]" value="%s" %s> %s <span class="count">(%d)</span></label>', esc_attr( $key ), checked( $g['default'], true, false ), esc_html( $g['title'] ), (int) $count );
		} else {
			printf( '<p class="dpp-group-title">%s <span class="count">(ništa za čišćenje)</span></p>', esc_html( $g['title'] ) );
		}
		echo '<p class="description">' . esc_html( $g['desc'] ) . ( $count ? ' <strong>' . ( $g['permanent'] ? 'Brisanje je trajno.' : 'Može se vratiti iz Otpada.' ) . '</strong>' : '' ) . '</p>';
		if ( $count ) {
			echo '<details><summary>Pogledaj šta je pronađeno</summary><ul>';
			foreach ( $g['items'] as $id => $label ) {
				if ( ! empty( $g['pick'] ) ) {
					printf( '<li><label><input type="checkbox" name="pick[%s][]" value="%s" checked> %s</label></li>', esc_attr( $key ), esc_attr( $id ), esc_html( $label ) );
				} else {
					echo '<li>' . esc_html( $label ) . '</li>';
				}
			}
			echo '</ul></details>';
		}
		echo '</div>';
	}
	echo '</div>';
	if ( $any ) {
		echo '<p><label><input type="checkbox" name="backup" value="1"> Imam sigurnosnu kopiju baze.</label></p>';
		submit_button( 'Očisti izabrano', 'delete' );
	} else {
		echo '<p><strong>Sve je čisto.</strong></p>';
	}
	echo '</form>';
}

function dpp_admin_styles() {
	?>
	<style>
	.dpp h2 { margin-top: 2em; }
	.dpp-parts td { vertical-align: middle; }
	.dpp-parts .dpp-sub { display: block; margin: 2px 0; }
	.dpp-src { margin: 2px 0; white-space: nowrap; }
	.dpp-src-label { display: inline-block; min-width: 3.5em; }
	.dpp-move { white-space: nowrap; }
	.dpp-reset { margin-top: -10px; color: #b32d2e; }
	.dpp-links th { padding: 0 8px 4px 0; font-weight: 400; }
	.dpp-links td { padding: 0 8px 6px 0; }
	.dpp-colors { max-width: 700px; }
	.dpp-chip { display: inline-block; padding: 3px 8px; color: #fff; font-weight: 700; font-size: 12px; }
	.dpp-groups { display: grid; gap: 12px; max-width: 900px; margin: 16px 0; }
	.dpp-group { padding: 12px 16px; background: #fff; border: 1px solid #dcdcde; border-left: 4px solid #d63638; }
	.dpp-group.is-empty { border-left-color: #00a32a; opacity: 0.75; }
	.dpp-group-title { display: block; margin: 0 0 4px; font-size: 14px; font-weight: 600; }
	.dpp-group .count { font-weight: 400; color: #646970; }
	.dpp-group details { margin-top: 6px; }
	.dpp-group ul { max-height: 240px; overflow: auto; margin: 6px 0 0 18px; list-style: disc; }
	</style>
	<?php
}
