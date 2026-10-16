<?php
/**
 * A fixed set of child fields stored together as one associative array.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Group extends BaseType {

	public function type(): string {
		return 'group';
	}

	public function label(): string {
		return __( 'Group', 'libreblocks-core' );
	}

	public function category(): string {
		return 'structure';
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		return $registry->sanitize_values( $field->fields, is_array( $value ) ? $value : [] );
	}

	public function validate( $value, Field $field, Registry $registry ): ?string {
		$errors = $registry->validate_values( $field->fields, $value );

		return $errors ? (string) reset( $errors ) : null;
	}

	// Required children are validated individually; the group itself is never "empty".
	public function is_empty( $value ): bool {
		return ! is_array( $value );
	}

	public function schema( Field $field, Registry $registry ): array {
		$properties = [];

		foreach ( $field->fields as $child ) {
			$type = $registry->get( $child->type );

			if ( $type->stores_value() ) {
				$properties[ $child->name ] = $type->schema( $child, $registry );
			}
		}

		return [ 'type' => 'object', 'properties' => $properties ];
	}
}
