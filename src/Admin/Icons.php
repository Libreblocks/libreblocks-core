<?php
/**
 * Menu icons of the LibreBlocks family, as SVG data URIs. WordPress repaints
 * them in the admin color scheme (svg-painter), so they use a single black fill.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Admin;

defined( 'ABSPATH' ) || exit;

final class Icons {

	private const SVG = [
		// Three stacked blocks.
		'libreblocks' => '<path d="M3 11h6v6H3zM11 11h6v6h-6zM7 3h6v6H7z"/>',
		// Anvil with a spark: the content engine.
		'libreforge'  => '<path d="M2 6h11.5c0 2.2 1.8 4 4 4H18v1.5h-3.2L13 13.5V15h2.5v2h-11v-2H7v-1.5L5.2 11.5C3.4 11 2 9.1 2 7z"/><path d="M15.5 2.5l.9 1.8 1.8.9-1.8.9-.9 1.8-.9-1.8-1.8-.9 1.8-.9z"/>',
		// Funnel: filters.
		'libresift'   => '<path d="M2 3h16l-6 7.5V16l-4 2v-7.5z"/>',
		// Form with lines.
		'libreforms'  => '<path d="M4 2h12a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1zm2 4v2h8V6zm0 4v2h8v-2zm0 4v2h5v-2z"/>',
		// Bed: nightly stays.
		'librestay'   => '<path d="M2 5h2v6h5V7h7a2 2 0 0 1 2 2v7h-2v-2H4v2H2zm4 1.5a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"/>',
		// Calendar with clock hand: appointments.
		'libreagenda' => '<path d="M5 1h2v2h6V1h2v2h2a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h2zm-1 6v9h12V7zm5 1.5h2V11h2.5v2H9z"/>',
	];

	public static function data_uri( string $name ): string {
		$paths = self::SVG[ $name ] ?? self::SVG['libreblocks'];
		$svg   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="black">' . $paths . '</svg>';

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- WordPress expects base64 SVG for menu icons.
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}
}
