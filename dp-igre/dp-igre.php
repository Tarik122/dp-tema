<?php
/**
 * Plugin Name:       Druga Perspektiva – Igre
 * Description:       Dnevna bosanska Wordle igra (Riječ dana) s Google prijavom za školske e-mailove, ljestvicom i nizovima. Ubaci na stranicu kratkim kodom [dp_wordle].
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Druga Perspektiva
 * License:           GPL-2.0-or-later
 * Text Domain:       dp-igre
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DPIG_VERSION', '1.0.0' );
define( 'DPIG_DB_VERSION', '1' );
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
require_once DPIG_DIR . 'includes/admin.php';

register_activation_hook( __FILE__, 'dpig_activate' );
add_action( 'plugins_loaded', 'dpig_maybe_upgrade' );
