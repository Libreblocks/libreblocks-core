<?php
/**
 * URL.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Url extends BaseType {

	public function type(): string {
		return 'url';
	}

	public function label(): string {
		return __( 'URL', 'libreblocks-core' );
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		return is_scalar( $value ) ? esc_url_raw( trim( (string) $value ) ) : '';
	}
}
