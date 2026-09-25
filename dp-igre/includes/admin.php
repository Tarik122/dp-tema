<?php
/**
 * Control panel: WP admin → "Riječ dana".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'dpig_admin_menu' );
add_action( 'admin_post_dpig_save_day', 'dpig_admin_save_day' );
add_action( 'admin_post_dpig_delete_day', 'dpig_admin_delete_day' );
add_action( 'admin_post_dpig_save_words', 'dpig_admin_save_words' );
add_action( 'admin_post_dpig_save_settings', 'dpig_admin_save_settings' );
add_action( 'admin_post_dpig_player_action', 'dpig_admin_player_action' );

function dpig_admin_menu() {
	add_menu_page( 'Riječ dana', 'Riječ dana', 'manage_options', 'dpig', 'dpig_admin_page', 'dashicons-games', 26 );
}

function dpig_admin_url( $tab, $message = '' ) {
	$args = array( 'page' => 'dpig', 'tab' => $tab );
	if ( $message ) {
		$args['dpig_msg'] = rawurlencode( $message );
	}
	return add_query_arg( $args, admin_url( 'admin.php' ) );
}

function dpig_admin_check( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	check_admin_referer( $action );
}

function dpig_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tabs = array(
		'schedule' => 'Raspored riječi',
		'words'    => 'Liste riječi',
		'players'  => 'Igrači',
		'settings' => 'Postavke',
	);
	$tab  = isset( $_GET['tab'], $tabs[ $_GET['tab'] ] ) ? sanitize_key( $_GET['tab'] ) : 'schedule';

	echo '<div class="wrap"><h1>Riječ dana</h1>';
	if ( ! get_option( 'dpig_client_id' ) ) {
		echo '<div class="notice notice-warning"><p>Google prijava nije podešena. Otvori <a href="' . esc_url( dpig_admin_url( 'settings' ) ) . '">Postavke</a>. Do tada se može igrati samo anonimno.</p></div>';
	}
	if ( ! empty( $_GET['dpig_msg'] ) ) {
		echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( wp_unslash( $_GET['dpig_msg'] ) ) . '</p></div>';
	}
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $key => $label ) {
		printf( '<a href="%s" class="nav-tab %s">%s</a>', esc_url( dpig_admin_url( $key ) ), $key === $tab ? 'nav-tab-active' : '', esc_html( $label ) );
	}
	echo '</nav>';
	call_user_func( 'dpig_admin_tab_' . $tab );
	echo '</div>';
}

/* ---------- Schedule ---------- */

function dpig_count_games( $date ) {
	global $wpdb;
	return $wpdb->get_row(
		$wpdb->prepare( 'SELECT COUNT(*) AS played, SUM(status = \'won\') AS won FROM ' . dpig_table( 'games' ) . ' WHERE puzzle_date = %s', $date ),
		ARRAY_A
	);
}

function dpig_admin_tab_schedule() {
	global $wpdb;
	$today = dpig_today();
	dpig_day( $today ); // Make sure today's word is picked, so it shows up below.
	$rows = $wpdb->get_results(
		$wpdb->prepare( 'SELECT * FROM ' . dpig_table( 'days' ) . ' WHERE puzzle_date >= %s ORDER BY puzzle_date', gmdate( 'Y-m-d', strtotime( $today . ' -14 days' ) ) ),
		ARRAY_A
	);
	$by_date = array();
	foreach ( $rows as $row ) {
		$by_date[ $row['puzzle_date'] ] = $row;
	}
	$last = $rows ? max( end( $rows )['puzzle_date'], gmdate( 'Y-m-d', strtotime( $today . ' +14 days' ) ) ) : $today;
	?>
	<h2>Dodaj posebnu riječ</h2>
	<p>Za poseban dan (Dan škole, praznik, kraj polugodišta…) odaberi datum i riječ od <?php echo (int) DPIG_WORD_LENGTH; ?> slova. LJ, NJ i DŽ se računaju kao jedno slovo. Poruka (neobavezno) se prikaže igračima kad završe igru.</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="dpig_save_day">
		<?php wp_nonce_field( 'dpig_save_day' ); ?>
		<table class="form-table" role="presentation">
			<tr><th><label for="dpig-date">Datum</label></th><td><input type="date" id="dpig-date" name="date" min="<?php echo esc_attr( $today ); ?>" value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( $today . ' +1 day' ) ) ); ?>" required></td></tr>
			<tr><th><label for="dpig-word">Riječ</label></th><td><input type="text" id="dpig-word" name="word" required autocomplete="off" style="text-transform:uppercase"></td></tr>
			<tr><th><label for="dpig-note">Poruka</label></th><td><input type="text" id="dpig-note" name="note" class="regular-text" maxlength="255" placeholder="npr. Sretan Dan škole!"></td></tr>
		</table>
		<?php submit_button( 'Spremi riječ' ); ?>
	</form>

	<h2>Raspored</h2>
	<p>Automatske riječi se biraju nasumično iz liste riječi na sam dan, bez ponavljanja. Riječ dana za koji je neko već počeo igrati ne može se promijeniti.</p>
	<table class="widefat striped">
		<thead><tr><th>Datum</th><th>#</th><th>Riječ</th><th>Vrsta</th><th>Poruka</th><th>Igralo / pogodilo</th><th></th></tr></thead>
		<tbody>
		<?php
		for ( $date = gmdate( 'Y-m-d', strtotime( $today . ' -14 days' ) ); $date <= $last; $date = gmdate( 'Y-m-d', strtotime( $date . ' +1 day' ) ) ) {
			$row = $by_date[ $date ] ?? null;
			if ( ! $row && $date < $today ) {
				continue;
			}
			$counts = $date <= $today ? dpig_count_games( $date ) : array( 'played' => 0, 'won' => 0 );
			$style  = $date === $today ? ' style="font-weight:600;background:#fff8e5"' : '';
			echo '<tr' . $style . '>';
			echo '<td>' . esc_html( date_i18n( 'D, j.n.Y.', strtotime( $date ) ) ) . ( $date === $today ? ' (danas)' : '' ) . '</td>';
			echo '<td>' . (int) dpig_puzzle_number( $date ) . '</td>';
			echo '<td>' . ( $row ? '<code>' . esc_html( mb_strtoupper( $row['word'], 'UTF-8' ) ) . '</code>' : '<em>automatski</em>' ) . '</td>';
			echo '<td>' . ( $row && 'custom' === $row['source'] ? '⭐ posebna' : 'automatska' ) . '</td>';
			echo '<td>' . esc_html( $row['note'] ?? '' ) . '</td>';
			echo '<td>' . ( $date <= $today ? (int) $counts['played'] . ' / ' . (int) $counts['won'] : '' ) . '</td>';
			echo '<td>';
			if ( $row && 'custom' === $row['source'] && ! (int) $counts['played'] ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
				echo '<input type="hidden" name="action" value="dpig_delete_day"><input type="hidden" name="date" value="' . esc_attr( $date ) . '">';
				wp_nonce_field( 'dpig_delete_day' );
				echo '<button class="button-link-delete" onclick="return confirm(\'Obrisati posebnu riječ?\')">Obriši</button></form>';
			}
			echo '</td></tr>';
		}
		?>
		</tbody>
	</table>
	<?php
}

function dpig_admin_save_day() {
	dpig_admin_check( 'dpig_save_day' );
	global $wpdb;
	$date = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
	$word = dpig_normalize( sanitize_text_field( wp_unslash( $_POST['word'] ?? '' ) ) );
	$note = sanitize_text_field( wp_unslash( $_POST['note'] ?? '' ) );

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || $date < dpig_today() ) {
		wp_safe_redirect( dpig_admin_url( 'schedule', 'Odaberi današnji ili neki budući datum.' ) );
		exit;
	}
	if ( ! dpig_is_playable( $word ) ) {
		wp_safe_redirect( dpig_admin_url( 'schedule', sprintf( 'Riječ mora imati tačno %d slova (LJ, NJ i DŽ su jedno slovo) i samo slova bosanske abecede.', DPIG_WORD_LENGTH ) ) );
		exit;
	}
	if ( (int) dpig_count_games( $date )['played'] ) {
		wp_safe_redirect( dpig_admin_url( 'schedule', 'Za taj dan su igrači već počeli igrati, pa se riječ ne može promijeniti.' ) );
		exit;
	}
	$wpdb->replace(
		dpig_table( 'days' ),
		array( 'puzzle_date' => $date, 'word' => $word, 'source' => 'custom', 'note' => $note )
	);
	$message = sprintf( 'Riječ %s je postavljena za %s', mb_strtoupper( $word, 'UTF-8' ), date_i18n( 'j.n.Y.', strtotime( $date ) ) );
	if ( ! dpig_is_valid_guess( $word ) ) {
		$message .= ' (Riječ nije u rječniku, ali će se prihvatati kao pokušaj.)';
	}
	wp_safe_redirect( dpig_admin_url( 'schedule', $message ) );
	exit;
}

function dpig_admin_delete_day() {
	dpig_admin_check( 'dpig_delete_day' );
	global $wpdb;
	$date = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
	if ( ! (int) dpig_count_games( $date )['played'] ) {
		$wpdb->delete( dpig_table( 'days' ), array( 'puzzle_date' => $date, 'source' => 'custom' ) );
	}
	wp_safe_redirect( dpig_admin_url( 'schedule', 'Posebna riječ je obrisana. Taj dan će dobiti automatsku riječ.' ) );
	exit;
}

/* ---------- Word lists ---------- */

function dpig_admin_tab_words() {
	$answers = get_option( 'dpig_answers' );
	if ( ! is_string( $answers ) || '' === trim( $answers ) ) {
		$answers = dpig_default_answers_text();
	}
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="dpig_save_words">
		<?php wp_nonce_field( 'dpig_save_words' ); ?>
		<h2>Moguće riječi dana (<?php echo count( dpig_answers() ); ?>)</h2>
		<p>Iz ove liste se svaki dan nasumično bira riječ. Jedna riječ po redu (ili odvojene razmakom). Riječi koje nemaju tačno <?php echo (int) DPIG_WORD_LENGTH; ?> slova se preskaču. Dodaj česte, poznate riječi u osnovnom obliku.</p>
		<textarea name="answers" rows="14" class="large-text code"><?php echo esc_textarea( $answers ); ?></textarea>
		<p><label><input type="checkbox" name="reset_answers" value="1"> Vrati početnu listu</label></p>

		<h2>Dodatne dozvoljene riječi</h2>
		<p>Pokušaji se provjeravaju u rječniku od oko 47.000 riječi. Ako neka prava riječ fali, dodaj je ovdje.</p>
		<textarea name="extra" rows="5" class="large-text code"><?php echo esc_textarea( get_option( 'dpig_extra_words', '' ) ); ?></textarea>
		<?php submit_button( 'Spremi liste' ); ?>
	</form>
	<?php
}

function dpig_admin_save_words() {
	dpig_admin_check( 'dpig_save_words' );
	if ( ! empty( $_POST['reset_answers'] ) ) {
		delete_option( 'dpig_answers' );
	} else {
		$answers = implode( "\n", dpig_parse_word_list( wp_unslash( $_POST['answers'] ?? '' ) ) );
		update_option( 'dpig_answers', $answers, false );
	}
	update_option( 'dpig_extra_words', implode( "\n", dpig_parse_word_list( wp_unslash( $_POST['extra'] ?? '' ) ) ), false );
	wp_safe_redirect( dpig_admin_url( 'words', 'Liste su spremljene.' ) );
	exit;
}

/* ---------- Players ---------- */

function dpig_admin_tab_players() {
	global $wpdb;
	$players = $wpdb->get_results( 'SELECT * FROM ' . dpig_table( 'players' ) . ' ORDER BY name', ARRAY_A );
	$today   = dpig_today();
	echo '<p>' . count( $players ) . ' prijavljenih igrača. „Sakrij” uklanja igrača s ljestvice (npr. zbog neprimjerenog imena ili varanja). „Poništi današnju” briše današnju igru pa igrač može ponovo. Ime se pravi iz e-maila bez kvačica, pa ga ovdje možeš ispraviti (npr. Hodzic → Hodžić).</p>';
	echo '<table class="widefat striped"><thead><tr><th>Ime</th><th>E-mail</th><th>Igrao</th><th>Pobjede</th><th>Bodovi</th><th>Niz</th><th>Najduži niz</th><th>Status</th><th></th></tr></thead><tbody>';
	foreach ( $players as $p ) {
		$s = dpig_player_stats( $p['id'] );
		echo '<tr>';
		echo '<td><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:flex;gap:4px">';
		echo '<input type="hidden" name="action" value="dpig_player_action"><input type="hidden" name="player" value="' . (int) $p['id'] . '"><input type="hidden" name="do" value="rename">';
		wp_nonce_field( 'dpig_player_action' );
		echo '<input type="text" name="name" value="' . esc_attr( $p['name'] ) . '" style="width:160px"><button class="button button-small">Spremi</button></form>';
		echo (int) $p['anonymous'] ? '<em>(anonimno na ljestvici)</em>' : '';
		echo '</td>';
		echo '<td>' . esc_html( $p['email'] ) . '</td>';
		echo '<td>' . (int) $s['played'] . '</td><td>' . (int) $s['won'] . '</td><td>' . (int) $s['points'] . '</td>';
		echo '<td>' . (int) $s['currentStreak'] . '</td><td>' . (int) $s['maxStreak'] . '</td>';
		echo '<td>' . ( (int) $p['hidden'] ? 'sakriven' : 'vidljiv' ) . '</td><td>';
		foreach ( array(
			(int) $p['hidden'] ? 'show' : 'hide' => (int) $p['hidden'] ? 'Prikaži' : 'Sakrij',
			'reset'                                => 'Poništi današnju',
			'delete'                               => 'Obriši',
		) as $do => $label ) {
			if ( 'reset' === $do && ! dpig_get_game( $p['id'], $today ) ) {
				continue;
			}
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;margin-right:8px">';
			echo '<input type="hidden" name="action" value="dpig_player_action"><input type="hidden" name="player" value="' . (int) $p['id'] . '"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
			wp_nonce_field( 'dpig_player_action' );
			$confirm = 'delete' === $do ? ' onclick="return confirm(\'Obrisati igrača i sve njegove rezultate?\')"' : '';
			echo '<button class="button-link' . ( 'delete' === $do ? ' button-link-delete' : '' ) . '"' . $confirm . '>' . esc_html( $label ) . '</button></form>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

function dpig_admin_player_action() {
	dpig_admin_check( 'dpig_player_action' );
	global $wpdb;
	$id = (int) ( $_POST['player'] ?? 0 );
	$do = sanitize_key( $_POST['do'] ?? '' );
	if ( 'rename' === $do ) {
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		if ( '' !== $name ) {
			$wpdb->update( dpig_table( 'players' ), array( 'name' => mb_substr( $name, 0, 190 ) ), array( 'id' => $id ) );
		}
	} elseif ( 'hide' === $do || 'show' === $do ) {
		$wpdb->update( dpig_table( 'players' ), array( 'hidden' => 'hide' === $do ? 1 : 0 ), array( 'id' => $id ) );
	} elseif ( 'reset' === $do ) {
		$wpdb->delete( dpig_table( 'games' ), array( 'player_id' => $id, 'puzzle_date' => dpig_today() ) );
	} elseif ( 'delete' === $do ) {
		$wpdb->delete( dpig_table( 'games' ), array( 'player_id' => $id ) );
		$wpdb->delete( dpig_table( 'players' ), array( 'id' => $id ) );
	}
	dpig_flush_leaderboards();
	wp_safe_redirect( dpig_admin_url( 'players', 'Spremljeno.' ) );
	exit;
}

/* ---------- Settings ---------- */

function dpig_admin_tab_settings() {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="dpig_save_settings">
		<?php wp_nonce_field( 'dpig_save_settings' ); ?>
		<table class="form-table" role="presentation">
			<tr><th><label for="dpig-client">Google Client ID</label></th><td>
				<input type="text" id="dpig-client" name="client_id" class="large-text code" value="<?php echo esc_attr( get_option( 'dpig_client_id', '' ) ); ?>" placeholder="123456789-abc….apps.googleusercontent.com">
				<p class="description">Kako ga dobiti (jednom, ~5 minuta):</p>
				<ol class="description">
					<li>Otvori <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">console.cloud.google.com → APIs &amp; Services → Credentials</a> (najbolje sa školskim Google računom) i napravi projekat.</li>
					<li><em>OAuth consent screen</em>: tip <strong>Internal</strong> (ako je račun iz škole) ili External, ime aplikacije „Druga Perspektiva”.</li>
					<li><em>Create credentials → OAuth client ID → Web application</em>.</li>
					<li>Pod <em>Authorized JavaScript origins</em> dodaj <code><?php echo esc_html( untrailingslashit( home_url() ) ); ?></code> (i verziju sa/bez www ako je koristiš).</li>
					<li>Kopiraj „Client ID” ovdje.</li>
				</ol>
			</td></tr>
			<tr><th><label for="dpig-domain">Dozvoljena domena</label></th><td>
				<input type="text" id="dpig-domain" name="domain" class="regular-text" value="<?php echo esc_attr( get_option( 'dpig_domain', '' ) ); ?>">
				<p class="description">Samo e-mailovi s ove domene se mogu prijaviti. Ime na ljestvici se pravi iz e-maila (ime.prezime → Ime Prezime).</p>
			</td></tr>
			<tr><th><label for="dpig-title">Naziv igre</label></th><td>
				<input type="text" id="dpig-title" name="title" class="regular-text" value="<?php echo esc_attr( get_option( 'dpig_title', 'Riječ dana' ) ); ?>">
			</td></tr>
			<tr><th><label for="dpig-start">Prvi dan (igra #1)</label></th><td>
				<input type="date" id="dpig-start" name="start_date" value="<?php echo esc_attr( get_option( 'dpig_start_date' ) ); ?>">
				<p class="description">Od ovog datuma se broje igre (#1, #2, …).</p>
			</td></tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<h2>Kako dodati igru na stranicu</h2>
	<p>Napravi novu stranicu (npr. „Igre”) i u nju ubaci kratki kod <code>[dp_wordle]</code>. Vrijeme promjene riječi se ravna po vremenskoj zoni iz <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">Postavke → Općenito</a> (trenutno: <?php echo esc_html( wp_timezone_string() ); ?>).</p>
	<?php
}

function dpig_admin_save_settings() {
	dpig_admin_check( 'dpig_save_settings' );
	update_option( 'dpig_client_id', sanitize_text_field( wp_unslash( $_POST['client_id'] ?? '' ) ) );
	update_option( 'dpig_domain', strtolower( sanitize_text_field( wp_unslash( $_POST['domain'] ?? '' ) ) ) );
	update_option( 'dpig_title', sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ) ?: 'Riječ dana' );
	$start = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) ) {
		update_option( 'dpig_start_date', $start );
	}
	wp_safe_redirect( dpig_admin_url( 'settings', 'Postavke su spremljene.' ) );
	exit;
}
