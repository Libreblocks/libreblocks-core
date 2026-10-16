<?php
/**
 * Global helpers.
 *
 * @package LibreBlocks\Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'libreblocks' ) ) {
	/**
	 * Access the LibreBlocks core container.
	 */
	function libreblocks(): LibreBlocks\Core\Plugin {
		return LibreBlocks\Core\Plugin::instance();
	}
}
