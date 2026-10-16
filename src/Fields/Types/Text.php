<?php
/**
 * Single-line text. Args: max_length.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Text extends BaseType {

	public function type(): string {
		return 'text';
	}

	public function label(): string {
		return __( 'Text', 'libreblocks-core' );
	}

	public function validate( $value, Field $field, Registry $registry ): ?string {
		$max = (int) $field->arg( 'max_length', 0 );

		if ( $max > 0 && mb_strlen( (string) $value ) > $max ) {
			/* translators: 1: field label, 2: maximum number of characters */
			return sprintf( __( '%1$s must have at most %2$d characters.', 'libreblocks-core' ), $field->label, $max );
		}

		return null;
	}
}
