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

function dpp_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tabs = array(
		'naslovnica' => 'Naslovnica',
		'opcije'     => 'Opcije',
		'ciscenje'   => 'Čišćenje',
	);
	$tab  = isset( $_GET['tab'], $tabs[ $_GET['tab'] ] ) ? sanitize_key( $_GET['tab'] ) : 'naslovnica';
	$msg  = sanitize_key( $_GET['dpp_msg'] ?? '' );

	echo '<div class="wrap dpp"><h1>Druga perspektiva</h1>';
	if ( 'druga-perspektiva' !== get_template() ) {
		echo '<div class="notice notice-warning"><p>Tema <strong>Druga perspektiva</strong> nije uključena, pa postavke s ovih stranica neće imati efekta.</p></div>';
	}
	if ( 'saved' === $msg ) {
		echo '<div class="notice notice-success is-dismissible"><p>Spremljeno. <a href="' . esc_url( home_url( '/' ) ) . '" target="_blank">Pogledaj naslovnicu</a></p></div>';
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

/* ---------- Naslovnica ---------- */

function dpp_admin_tab_naslovnica() {
	$s     = dpp_settings();
	$posts = get_posts( array( 'numberposts' => 60, 'post_status' => 'publish' ) );
	$subs  = dpp_subtitles();
	$names = dpp_parts();

	dpp_form_open( 'naslovnica' );

	echo '<h2>Glavna priča</h2>';
	echo '<p>Velika priča na vrhu naslovnice.</p>';
	echo '<select name="lead_id" style="max-width:100%"><option value="0">Automatski: zakačeni članak, a ako ga nema, najnoviji</option>';
	foreach ( $posts as $p ) {
		printf( '<option value="%d" %s>%s (%s)</option>', (int) $p->ID, selected( (int) $s['lead_id'], (int) $p->ID, false ), esc_html( get_the_title( $p ) ), esc_html( get_the_date( 'j. n. Y.', $p ) ) );
	}
	echo '</select>';

	echo '<h2>Rubrike na naslovnici</h2>';
	echo '<p>Redoslijed mijenjate strelicama. Rubrika bez kvačice se ne prikazuje. Vrh naslovnice (glavna priča i tekstovi pored nje) je uvijek prvi.</p>';
	echo '<table class="widefat dpp-parts"><thead><tr><th>Prikaži</th><th>Rubrika</th><th>Podnaslov</th><th>Redoslijed</th></tr></thead><tbody>';
	foreach ( $s['order'] as $key ) {
		if ( ! isset( $names[ $key ] ) ) {
			continue;
		}
		echo '<tr>';
		printf( '<td><input type="checkbox" name="shown[]" value="%1$s" %2$s aria-label="Prikaži %3$s"><input type="hidden" name="order[]" value="%1$s"></td>', esc_attr( $key ), checked( ! in_array( $key, (array) $s['hidden'], true ), true, false ), esc_attr( $names[ $key ] ) );
		echo '<td><strong>' . esc_html( $names[ $key ] ) . '</strong></td><td>';
		$sub_keys = 'sportnauka' === $key ? array( 'sport', 'nauka' ) : ( isset( $subs[ $key ] ) ? array( $key ) : array() );
		foreach ( $sub_keys as $sk ) {
			printf(
				'<label class="dpp-sub">%s<input type="text" class="regular-text" name="subtitles[%s]" value="%s" placeholder="%s"></label>',
				'sportnauka' === $key ? esc_html( ucfirst( $sk ) ) . ': ' : '',
				esc_attr( $sk ),
				esc_attr( $s['subtitles'][ $sk ] ?? '' ),
				esc_attr( $subs[ $sk ] )
			);
		}
		echo '</td><td class="dpp-move"><button type="button" class="button dpp-up" aria-label="Pomjeri gore">↑</button> <button type="button" class="button dpp-down" aria-label="Pomjeri dole">↓</button></td></tr>';
	}
	echo '</tbody></table>';
	echo '<p class="description">Prazan podnaslov znači da ostaje tekst iz teme (sivi tekst u polju).</p>';

	echo '<h2>Najnovije</h2>';
	printf( '<label><input type="checkbox" name="show_latest" value="1" %s> Prikaži stupac "Najnovije" desno od glavne priče (samo na većim ekranima)</label>', checked( $s['show_latest'], true, false ) );

	submit_button( 'Spremi' );
	echo '</form>';
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

/* ---------- Opcije ---------- */

function dpp_admin_tab_opcije() {
	$s = dpp_settings();
	dpp_form_open( 'opcije' );
	echo '<table class="form-table"><tbody>';
	printf( '<tr><th scope="row">Datum u zaglavlju</th><td><label><input type="checkbox" name="show_date" value="1" %s> Prikaži današnji datum lijevo od logotipa (na većim ekranima)</label></td></tr>', checked( $s['show_date'], true, false ) );
	printf( '<tr><th scope="row"><label for="dpp-new-days">Oznaka "Novo!" na igrama</label></th><td><input type="number" min="0" max="365" id="dpp-new-days" name="new_days" value="%d" class="small-text"> dana nakon objave igre (0 = nikad)</td></tr>', (int) $s['new_days'] );
	echo '</tbody></table>';
	submit_button( 'Spremi' );
	echo '</form>';

	echo '<h2>Gdje je ostalo?</h2><ul class="ul-disc">';
	echo '<li><strong>Meni, zaglavlje i podnožje:</strong> <a href="' . esc_url( admin_url( 'site-editor.php' ) ) . '">Izgled → Editor</a>.</li>';
	echo '<li><strong>Izvodi (sažeci) članaka:</strong> u samom članku, desna kolona → Izvod.</li>';
	if ( dpp_plugin_active( 'dp-igre' ) ) {
		echo '<li><strong>Igre (Riječ dana, Kontekst, Tramvaj):</strong> <a href="' . esc_url( admin_url( 'admin.php?page=dpig' ) ) . '">Riječ dana</a>.</li>';
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
	.dpp-move { white-space: nowrap; }
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
