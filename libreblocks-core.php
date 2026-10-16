<?php
/**
 * Plugin Name:       LibreBlocks Core
 * Plugin URI:        https://libreblocks.com
 * Description:       Shared foundation for the LibreBlocks plugin family: fields, tokens, context, REST and admin.
 * Version:           0.1.1
 * Requires at least: 6.6
 * Requires PHP:      8.0
 * Author:            LibreBlocks
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       libreblocks-core
 * Domain Path:       /languages
 *
 * This file must stay parseable by old PHP versions so that the requirements
 * notice can be shown instead of a fatal error. Modern syntax lives in src/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'LIBREBLOCKS_CORE_VERSION' ) ) {
	return;
}

define( 'LIBREBLOCKS_CORE_VERSION', '0.1.1' );
define( 'LIBREBLOCKS_CORE_FILE', __FILE__ );
define( 'LIBREBLOCKS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LIBREBLOCKS_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'LIBREBLOCKS_CORE_MIN_PHP', '8.0' );
define( 'LIBREBLOCKS_CORE_MIN_WP', '6.6' );

/**
 * Returns a list of unmet requirement messages (empty when all good).
 *
 * @return string[]
 */
function libreblocks_core_requirement_errors() {
	$errors = array();

	if ( version_compare( PHP_VERSION, LIBREBLOCKS_CORE_MIN_PHP, '<' ) ) {
		/* translators: 1: required PHP version, 2: current PHP version */
		$errors[] = sprintf( __( 'LibreBlocks requires PHP %1$s or newer. This site runs PHP %2$s.', 'libreblocks-core' ), LIBREBLOCKS_CORE_MIN_PHP, PHP_VERSION );
	}

	if ( version_compare( get_bloginfo( 'version' ), LIBREBLOCKS_CORE_MIN_WP, '<' ) ) {
		/* translators: 1: required WordPress version, 2: current WordPress version */
		$errors[] = sprintf( __( 'LibreBlocks requires WordPress %1$s or newer. This site runs WordPress %2$s.', 'libreblocks-core' ), LIBREBLOCKS_CORE_MIN_WP, get_bloginfo( 'version' ) );
	}

	return $errors;
}

if ( libreblocks_core_requirement_errors() ) {
	add_action( 'admin_notices', function () {
		foreach ( libreblocks_core_requirement_errors() as $message ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
		}
	} );
	return;
}

require_once LIBREBLOCKS_CORE_DIR . 'src/Autoloader.php';

LibreBlocks\Core\Autoloader::register( 'LibreBlocks\\Core\\', LIBREBLOCKS_CORE_DIR . 'src/' );

require_once LIBREBLOCKS_CORE_DIR . 'src/functions.php';

register_activation_hook( __FILE__, array( 'LibreBlocks\\Core\\Plugin', 'activate' ) );

// Priority 5: products (LibreForge, LibreSift...) boot on the default priority 10
// and can rely on the core being ready.
add_action( 'plugins_loaded', array( 'LibreBlocks\\Core\\Plugin', 'boot' ), 5 );
