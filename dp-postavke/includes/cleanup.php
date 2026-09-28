<?php
/**
 * Čišćenje: finds what the old theme and old plugins left behind and removes
 * what the editor ticks. Nothing is removed without a scan shown first.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Pages that must never be touched: front page, blog page, privacy policy, Igre and its games. */
function dpp_protected_pages() {
	$ids = array_filter(
		array(
			(int) get_option( 'page_on_front' ),
			(int) get_option( 'page_for_posts' ),
			(int) get_option( 'wp_page_for_privacy_policy' ),
		)
	);
	// Always keep one privacy policy page: the one set in Settings → Privacy, or else the oldest published one.
	if ( ! (int) get_option( 'wp_page_for_privacy_policy' ) ) {
		foreach ( get_pages( array( 'sort_column' => 'ID', 'post_status' => 'publish' ) ) as $page ) {
			if ( 0 === strpos( $page->post_name, 'privacy-policy' ) || 0 === strpos( $page->post_name, 'politika-privatnosti' ) ) {
				$ids[] = $page->ID;
				break;
			}
		}
	}
	$hub = get_page_by_path( 'igre' );
	if ( $hub ) {
		$ids[] = $hub->ID;
		foreach ( get_pages( array( 'child_of' => $hub->ID, 'post_status' => 'publish,draft,private,pending' ) ) as $child ) {
			$ids[] = $child->ID;
		}
	}
	return array_map( 'intval', $ids );
}

function dpp_plugin_active( $fragment ) {
	foreach ( (array) get_option( 'active_plugins', array() ) as $plugin ) {
		if ( false !== strpos( $plugin, $fragment ) ) {
			return true;
		}
	}
	return false;
}

/** Leftover post meta: prefix => [who left it, plugin folder that still uses it (or '')]. */
function dpp_meta_prefixes() {
	return array(
		'tdc_'              => array( 'stara tema (tagDiv)', '' ),
		'td_'               => array( 'stara tema (tagDiv)', '' ),
		'_td_'              => array( 'stara tema (tagDiv)', '' ),
		'tds_'              => array( 'stara tema (tagDiv)', '' ),
		'_foxdemo'          => array( 'demo sadržaj stare teme', '' ),
		'_powerkit_'        => array( 'dodatak Powerkit', 'powerkit' ),
		'powerkit_'         => array( 'dodatak Powerkit', 'powerkit' ),
		'post_views_count'  => array( 'brojač pregleda stare teme', 'post-views' ),
		'_molongui_'        => array( 'dodatak Molongui (autori)', 'molongui' ),
		'rank_math_'        => array( 'dodatak Rank Math SEO', 'seo-by-rank-math' ),
		'_yoast_wpseo_'     => array( 'dodatak Yoast SEO', 'wordpress-seo' ),
		'_imagify_'         => array( 'dodatak Imagify', 'imagify' ),
		'litespeed-optimize' => array( 'dodatak LiteSpeed Cache', 'litespeed-cache' ),
	);
}

function dpp_like( $prefix ) {
	global $wpdb;
	return $wpdb->esc_like( $prefix ) . '%';
}

/**
 * Everything that can be cleaned, grouped. Each group: title, what it is,
 * whether deleting is permanent, whether it is ticked by default, and items.
 */
function dpp_cleanup_scan() {
	global $wpdb;
	$groups = array();

	// 1. Old theme pages.
	$protected = dpp_protected_pages();
	$items     = array();
	foreach ( get_pages( array( 'post_status' => 'publish,draft,private,pending' ) ) as $page ) {
		if ( in_array( (int) $page->ID, $protected, true ) ) {
			continue;
		}
		$template = (string) get_post_meta( $page->ID, '_wp_page_template', true );
		$is_old   = preg_match( '/^(homepage(-\d+)?|coming-soon|tds-[a-z-]+|sample-page(-\d+)?|archive|privacy-policy(-\d+)?)$/', $page->post_name )
			|| metadata_exists( 'post', $page->ID, 'tdc_content' )
			|| metadata_exists( 'post', $page->ID, 'tdc_dirty_content' )
			|| 0 === strpos( $template, 'template-canvas' );
		if ( $is_old ) {
			$items[ $page->ID ] = sprintf( '%s (/%s/)', $page->post_title ? $page->post_title : '(bez naslova)', $page->post_name );
		}
	}
	$groups['pages'] = array(
		'title'     => 'Stranice stare teme',
		'desc'      => 'Demo stranice ("Homepage", "Checkout", "My account", "Coming Soon"…), "Sample Page" i duple "Privacy Policy". Idu u Otpad, odakle se mogu vratiti 30 dana. Naslovna stranica, jedna politika privatnosti i stranice Igre se nikad ne diraju.',
		'permanent' => false,
		'default'   => true,
		'pick'      => true,
		'items'     => $items,
	);

	// 2. Content types nothing uses any more (templates of the old page builder etc.).
	$registered = get_post_types();
	$items      = array();
	foreach ( $wpdb->get_results( "SELECT post_type, COUNT(*) AS c FROM {$wpdb->posts} GROUP BY post_type" ) as $row ) {
		if ( ! isset( $registered[ $row->post_type ] ) ) {
			$items[ $row->post_type ] = sprintf( '%s: %d', $row->post_type, $row->c );
		}
	}
	$groups['types'] = array(
		'title'     => 'Sadržaj koji više ništa ne koristi',
		'desc'      => 'Šabloni starog page buildera i drugi tipovi sadržaja čiji dodatak ili tema više nisu instalirani.',
		'permanent' => true,
		'default'   => true,
		'items'     => $items,
	);

	// 3. Demo images of the old theme, if nothing uses them.
	$items = array();
	$ids   = $wpdb->get_col( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('td_demo_attachment', '_foxdemo')" );
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( 'attachment' !== get_post_type( $id ) || dpp_attachment_in_use( $id ) ) {
			continue;
		}
		$items[ $id ] = basename( (string) get_attached_file( $id ) );
	}
	$groups['media'] = array(
		'title'     => 'Demo slike stare teme',
		'desc'      => 'Slike koje je stara tema ubacila kao primjer, a koje nijedan članak ne koristi. Brišu se i fajlovi.',
		'permanent' => true,
		'default'   => true,
		'items'     => $items,
	);

	// 4. Empty categories.
	$items = array();
	$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
	foreach ( is_array( $terms ) ? $terms : array() as $term ) {
		if ( (int) $term->count > 0 || (int) get_option( 'default_category' ) === (int) $term->term_id ) {
			continue;
		}
		if ( get_term_children( $term->term_id, 'category' ) ) {
			continue;
		}
		$items[ $term->term_id ] = sprintf( '%s (%s)', $term->name, $term->slug );
	}
	$groups['categories'] = array(
		'title'     => 'Prazne kategorije',
		'pick'      => true,
		'desc'      => 'Kategorije bez ijednog članka (Movies, Music, News…). Kategorija "Uncategorized" ostaje jer je WordPress traži.',
		'permanent' => true,
		'default'   => true,
		'items'     => $items,
	);

	// 5. Settings of the old theme.
	$items = array();
	$like  = array( 'td\_%', 'tdc\_%', 'tds\_%', 'tdb\_%', 'tdm\_%', 'td-%', '\_transient\_td%', '\_transient\_timeout\_td%', '\_site\_transient\_td%' );
	$where = implode( ' OR ', array_fill( 0, count( $like ), 'option_name LIKE %s' ) );
	foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE $where", $like ) ) as $name ) {
		$items[ $name ] = $name;
	}
	$current = array( get_stylesheet(), get_template() );
	foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", 'theme\_mods\_%' ) ) as $name ) {
		$slug = substr( $name, strlen( 'theme_mods_' ) );
		if ( ! in_array( $slug, $current, true ) && ! wp_get_theme( $slug )->exists() ) {
			$items[ $name ] = $name;
		}
	}
	$groups['options'] = array(
		'title'     => 'Postavke stare teme',
		'desc'      => 'Podešavanja koja je stara tema spremila u bazu. Nova tema ih ne čita.',
		'permanent' => true,
		'default'   => true,
		'items'     => $items,
	);

	// 6. Leftover data on articles from the old theme and removed plugins.
	$items = array();
	foreach ( dpp_meta_prefixes() as $prefix => $info ) {
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", dpp_like( $prefix ) ) );
		if ( ! $count ) {
			continue;
		}
		if ( $info[1] && dpp_plugin_active( $info[1] ) ) {
			continue; // The plugin is still on: leave its data alone.
		}
		$items[ $prefix ] = sprintf( '%s: %d zapisa (%s)', $prefix, $count, $info[0] );
	}
	$groups['meta'] = array(
		'title'     => 'Ostaci na člancima',
		'pick'      => true,
		'desc'      => 'Podaci koje su stara tema i isključeni dodaci ostavili uz članke (npr. brojači, SEO podaci, postavke page buildera). Podaci dodataka koji su još uključeni se ne diraju.',
		'permanent' => true,
		'default'   => true,
		'items'     => $items,
	);

	// 7. Classic menus (the new theme's menu is in the header template).
	$items = array();
	foreach ( wp_get_nav_menus() as $menu ) {
		$items[ $menu->term_id ] = sprintf( '%s (%d stavki)', $menu->name, $menu->count );
	}
	$groups['menus'] = array(
		'title'     => 'Stari meniji',
		'pick'      => true,
		'desc'      => 'Meniji iz stare teme. Nova tema ima svoj meni u zaglavlju (Izgled → Editor → Zaglavlje), pa ovi ne rade ništa.',
		'permanent' => true,
		'default'   => false,
		'items'     => $items,
	);

	// 8. Revisions.
	$count           = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'" );
	$groups['revisions'] = array(
		'title'     => 'Stare verzije članaka',
		'desc'      => 'WordPress čuva svaku spremljenu verziju članka. Brisanjem nestaje mogućnost "vrati staru verziju", ali članci ostaju.',
		'permanent' => true,
		'default'   => false,
		'items'     => $count ? array( 'all' => $count . ' verzija' ) : array(),
		'count'     => $count,
	);

	// 9. Trash.
	$count           = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'" );
	$groups['trash'] = array(
		'title'     => 'Otpad',
		'desc'      => 'Članci i stranice koje su već u Otpadu. Brišu se zauvijek.',
		'permanent' => true,
		'default'   => false,
		'items'     => $count ? array( 'all' => $count . ' u otpadu' ) : array(),
		'count'     => $count,
	);

	return $groups;
}

/** Is an image used as a featured image, logo or site icon, or inside any post? */
function dpp_attachment_in_use( $id ) {
	global $wpdb;
	if ( (int) get_option( 'site_icon' ) === $id || (int) get_theme_mod( 'custom_logo' ) === $id || (int) get_option( 'site_logo' ) === $id ) {
		return true;
	}
	if ( $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %s LIMIT 1", (string) $id ) ) ) {
		return true;
	}
	$file = basename( (string) get_attached_file( $id ) );
	if ( '' === $file ) {
		return false;
	}
	$name = preg_replace( '/(-scaled)?\.[a-z0-9]+$/i', '', $file );
	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT 1 FROM {$wpdb->posts} WHERE post_type NOT IN ('attachment', 'revision') AND post_status <> 'trash' AND post_content LIKE %s LIMIT 1",
			'%' . $wpdb->esc_like( $name ) . '%'
		)
	);
}

/**
 * Remove the ticked groups. In groups with their own checkboxes ("pick"),
 * only the ticked items are removed.
 *
 * @return array group => number removed
 */
function dpp_cleanup_run( array $groups, array $picked ) {
	global $wpdb;
	$scan   = dpp_cleanup_scan();
	$report = array();

	foreach ( $groups as $key ) {
		if ( empty( $scan[ $key ]['items'] ) ) {
			continue;
		}
		$items = $scan[ $key ]['items'];
		if ( ! empty( $scan[ $key ]['pick'] ) ) {
			$chosen = array_map( 'strval', (array) ( $picked[ $key ] ?? array() ) );
			$items  = array_filter( $items, function ( $k ) use ( $chosen ) { return in_array( (string) $k, $chosen, true ); }, ARRAY_FILTER_USE_KEY );
		}
		$done  = 0;
		switch ( $key ) {
			case 'pages':
				foreach ( array_keys( $items ) as $id ) {
					if ( wp_trash_post( $id ) ) {
						$done++;
					}
				}
				break;
			case 'types':
				foreach ( array_keys( $items ) as $type ) {
					foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", $type ) ) as $id ) {
						if ( wp_delete_post( (int) $id, true ) ) {
							$done++;
						}
					}
				}
				break;
			case 'media':
				foreach ( array_keys( $items ) as $id ) {
					if ( wp_delete_attachment( (int) $id, true ) ) {
						$done++;
					}
				}
				break;
			case 'categories':
				foreach ( array_keys( $items ) as $id ) {
					if ( true === wp_delete_term( (int) $id, 'category' ) ) {
						$done++;
					}
				}
				break;
			case 'options':
				foreach ( array_keys( $items ) as $name ) {
					if ( delete_option( $name ) ) {
						$done++;
					}
				}
				break;
			case 'meta':
				foreach ( array_keys( $items ) as $prefix ) {
					$done += (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", dpp_like( $prefix ) ) );
				}
				wp_cache_flush();
				break;
			case 'menus':
				foreach ( array_keys( $items ) as $id ) {
					if ( true === wp_delete_nav_menu( (int) $id ) ) {
						$done++;
					}
				}
				break;
			case 'revisions':
				foreach ( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision'" ) as $id ) {
					if ( wp_delete_post_revision( (int) $id ) ) {
						$done++;
					}
				}
				break;
			case 'trash':
				foreach ( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash'" ) as $id ) {
					if ( wp_delete_post( (int) $id, true ) ) {
						$done++;
					}
				}
				break;
		}
		$report[ $key ] = $done;
	}
	return $report;
}

function dpp_cleanup_handle() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	check_admin_referer( 'dpp_cleanup' );
	$back = add_query_arg( array( 'page' => 'dp-postavke', 'tab' => 'ciscenje' ), admin_url( 'admin.php' ) );
	if ( empty( $_POST['backup'] ) ) {
		wp_safe_redirect( add_query_arg( 'dpp_msg', 'backup', $back ) );
		exit;
	}
	$groups = array_map( 'sanitize_key', (array) wp_unslash( $_POST['groups'] ?? array() ) );
	$picked = array();
	foreach ( (array) wp_unslash( $_POST['pick'] ?? array() ) as $group => $keys ) {
		$picked[ sanitize_key( $group ) ] = array_map( 'sanitize_text_field', (array) $keys );
	}
	$report = dpp_cleanup_run( $groups, $picked );
	set_transient( 'dpp_cleanup_report_' . get_current_user_id(), $report, 10 * MINUTE_IN_SECONDS );
	wp_safe_redirect( add_query_arg( 'dpp_msg', 'cleaned', $back ) );
	exit;
}
add_action( 'admin_post_dpp_cleanup', 'dpp_cleanup_handle' );
