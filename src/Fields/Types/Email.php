<?php
/**
 * E-mail address.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Email extends BaseType {

	public function type(): string {
		return 'email';
	}

	public function label(): string {
		return __( 'E-mail', 'libreblocks-core' );
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		return is_scalar( $value ) ? sanitize_email( (string) $value ) : '';
	}

	public function validate( $value, Field $field, Registry $registry ): ?string {
		/* translators: %s: field label */
		return is_email( $value ) ? null : sprintf( __( '%s must be a valid e-mail address.', 'libreblocks-core' ), $field->label );
	}
}
