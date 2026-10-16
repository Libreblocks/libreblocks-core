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
		echo '<div class="wrap"><div id="libreblocks-dashboard"></div></div>';

		if ( ! is_file( LIBREBLOCKS_CORE_DIR . 'build/dashboard.asset.php' ) ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'LibreBlocks admin assets are missing. Run "npm install && npm run build" inside libreblocks-core.', 'libreblocks-core' )
			);
		}
	}

	public function enqueue(): void {
		$asset_file = LIBREBLOCKS_CORE_DIR . 'build/dashboard.asset.php';

		if ( ! is_file( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script( self::HANDLE, LIBREBLOCKS_CORE_URL . 'build/dashboard.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( self::HANDLE, 'libreblocks-core', LIBREBLOCKS_CORE_DIR . 'languages' );

		wp_enqueue_style( 'wp-components' );

		if ( is_file( LIBREBLOCKS_CORE_DIR . 'build/dashboard.css' ) ) {
			wp_enqueue_style( self::HANDLE, LIBREBLOCKS_CORE_URL . 'build/dashboard.css', [ 'wp-components' ], $asset['version'] );
		}
	}
}
