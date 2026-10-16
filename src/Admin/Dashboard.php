<?php
/**
 * Dashboard screen: a React app mounted on #libreblocks-dashboard.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Admin;

use LibreBlocks\Core\Plugin;

defined( 'ABSPATH' ) || exit;

final class Dashboard {

	public const HANDLE = 'libreblocks-dashboard';

	public function __construct( private Plugin $core ) {}

	public function render(): void {
		// No .wrap: the app draws its own full-width bar.
		echo '<div id="libreblocks-dashboard"></div>';

		if ( ! is_file( LIBREBLOCKS_CORE_DIR . 'build/dashboard.asset.php' ) ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'LibreBlocks admin assets are missing. Run "npm install && npm run build" inside libreblocks-core.', 'libreblocks-core' )
			);
		}
	}

	/**
	 * Registers (without enqueuing) the dashboard script. Its translation file also
	 * carries the strings of the shared UI kit, see Admin::share_ui_translations().
	 */
	public static function register_script(): bool {
		$asset_file = LIBREBLOCKS_CORE_DIR . 'build/dashboard.asset.php';

		if ( wp_script_is( self::HANDLE, 'registered' ) ) {
			return true;
		}

		if ( ! is_file( $asset_file ) ) {
			return false;
		}

		$asset = require $asset_file;

		wp_register_script( self::HANDLE, LIBREBLOCKS_CORE_URL . 'build/dashboard.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( self::HANDLE, 'libreblocks-core', LIBREBLOCKS_CORE_DIR . 'languages' );

		return true;
	}

	public function enqueue(): void {
		if ( ! self::register_script() ) {
			return;
		}

		$asset = require LIBREBLOCKS_CORE_DIR . 'build/dashboard.asset.php';

		wp_enqueue_script( self::HANDLE );
		wp_add_inline_script( self::HANDLE, 'window.libreblocksDashboard = ' . wp_json_encode( [
			'icon' => Icons::data_uri( 'libreblocks' ),
			'user' => wp_get_current_user()->display_name,
		] ) . ';', 'before' );
		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style( 'wp-components' );

		if ( is_file( LIBREBLOCKS_CORE_DIR . 'build/dashboard.css' ) ) {
			wp_enqueue_style( self::HANDLE, LIBREBLOCKS_CORE_URL . 'build/dashboard.css', [ 'wp-components' ], $asset['version'] );
		}
	}
}
