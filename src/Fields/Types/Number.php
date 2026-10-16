<?php
/**
 * Numeric value. Args: min, max, step (decimals are allowed when step is fractional).
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Number extends BaseType {

	public function type(): string {
		return 'number';
	}

	public function label(): string {
		return __( 'Number', 'libreblocks-core' );
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		if ( is_string( $value ) ) {
			$value = str_replace( ',', '.', trim( $value ) );
		}

		if ( ! is_numeric( $value ) ) {
			return '';
		}

		return $this->allows_decimals( $field ) ? (float) $value : (int) round( (float) $value );
	}

	public function validate( $value, Field $field, Registry $registry ): ?string {
		$min = $field->arg( 'min' );
		$max = $field->arg( 'max' );

		if ( is_numeric( $min ) && $value < (float) $min ) {
			/* translators: 1: field label, 2: minimum value */
			return sprintf( __( '%1$s must be at least %2$s.', 'libreblocks-core' ), $field->label, $min );
		}

		if ( is_numeric( $max ) && $value > (float) $max ) {
			/* translators: 1: field label, 2: maximum value */
			return sprintf( __( '%1$s must be at most %2$s.', 'libreblocks-core' ), $field->label, $max );
		}

		return null;
	}

	public function schema( Field $field, Registry $registry ): array {
		return [ 'type' => $this->allows_decimals( $field ) ? 'number' : 'integer' ];
	}

	private function allows_decimals( Field $field ): bool {
		$step = $field->arg( 'step', 1 );

		return is_numeric( $step ) && floor( (float) $step ) !== (float) $step;
	}
}
