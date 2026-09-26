<?php
/**
 * Plugin Name:       Druga Perspektiva – Igre
 * Description:       Dnevne igre Druge perspektive s Google prijavom za školske e-mailove, ljestvicama i nizovima: Riječ dana [dp_wordle], Kontekst [dp_kontekst] i Tramvaj [dp_tramvaj]. Svaku igru ubacite na stranicu njenim kratkim kodom.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Druga Perspektiva
 * License:           GPL-2.0-or-later
 * Text Domain:       dp-igre
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DPIG_VERSION', '1.2.0' );
define( 'DPIG_DB_VERSION', '2' );
define( 'DPIG_FILE', __FILE__ );
define( 'DPIG_DIR', plugin_dir_path( __FILE__ ) );
define( 'DPIG_URL', plugin_dir_url( __FILE__ ) );
define( 'DPIG_WORD_LENGTH', 5 );
define( 'DPIG_MAX_GUESSES', 6 );

require_once DPIG_DIR . 'includes/db.php';
require_once DPIG_DIR . 'includes/words.php';
require_once DPIG_DIR . 'includes/auth.php';
require_once DPIG_DIR . 'includes/game.php';
require_once DPIG_DIR . 'includes/rest.php';
require_once DPIG_DIR . 'includes/shortcode.php';
require_once DPIG_DIR . 'includes/results.php';
require_once DPIG_DIR . 'includes/kontekst.php';
require_once DPIG_DIR . 'includes/tramvaj.php';
require_once DPIG_DIR . 'includes/admin.php';

register_activation_hook( __FILE__, 'dpig_activate' );
register_deactivation_hook( __FILE__, 'dpig_deactivate' );
add_action( 'plugins_loaded', 'dpig_maybe_upgrade' );
