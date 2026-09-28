<?php
/**
 * Plugin Name:       DP postavke
 * Description:       Kontrolna ploča za temu Druga perspektiva: glavna priča, redoslijed i podnaslovi rubrika na naslovnici, sitne opcije i čišćenje ostataka stare teme. Meni: Druga perspektiva.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Druga perspektiva
 * License:           GPL-2.0-or-later
 * Text Domain:       dp-postavke
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DPP_VERSION', '1.0.0' );
define( 'DPP_DIR', plugin_dir_path( __FILE__ ) );

require_once DPP_DIR . 'includes/settings.php';
require_once DPP_DIR . 'includes/cleanup.php';
require_once DPP_DIR . 'includes/admin.php';
