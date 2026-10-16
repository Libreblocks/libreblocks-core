<?php
/**
 * Color as hex (#rgb, #rrggbb, #rrggbbaa) or rgb()/rgba()/hsl()/hsla().
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Color extends BaseType {

	private const PATTERN = '/^(#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})|(rgb|hsl)a?\(\s*[\d.%\s,\/]+\))$/i';

	public function type(): string {
		return 'color';
	}

	public function label(): string {
		return __( 'Color', 'libreblocks-core' );
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		$value = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';

		return preg_match( self::PATTERN, $value ) ? $value : '';
	}
}
