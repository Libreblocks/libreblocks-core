<?php
/**
 * Multi-line plain text. Args: max_length, rows.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Textarea extends Text {

	public function type(): string {
		return 'textarea';
	}

	public function label(): string {
		return __( 'Textarea', 'libreblocks-core' );
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		return is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';
	}
}
