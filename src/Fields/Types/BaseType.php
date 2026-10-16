<?php
/**
 * Defaults shared by most field types.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\FieldType;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

abstract class BaseType implements FieldType {

	public function category(): string {
		return 'basic';
	}

	public function stores_value(): bool {
		return true;
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	public function validate( $value, Field $field, Registry $registry ): ?string {
		return null;
	}

	public function is_empty( $value ): bool {
		return null === $value || '' === $value || [] === $value;
	}

	public function schema( Field $field, Registry $registry ): array {
		return [ 'type' => 'string' ];
	}

	/**
	 * @param mixed $value
	 * @return array<int, mixed>
	 */
	protected function to_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = '' === trim( $value ) ? [] : explode( ',', $value );
		}

		return array_values( (array) $value );
	}

	/**
	 * @param mixed $value
	 * @return int[]
	 */
	protected function to_id_list( $value ): array {
		return array_values( array_unique( array_filter( array_map( 'absint', $this->to_list( $value ) ) ) ) );
	}
}
