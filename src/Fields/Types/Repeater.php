<?php
/**
 * List of rows, each with the child fields. Empty rows are dropped.
 * Args: min_rows, max_rows.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Repeater extends BaseType {

	public function type(): string {
		return 'repeater';
	}

	public function label(): string {
		return __( 'Repeater', 'libreblocks-core' );
	}

	public function category(): string {
		return 'structure';
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		$rows = [];

		foreach ( is_array( $value ) ? $value : [] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean = $registry->sanitize_values( $field->fields, $row );

			if ( ! $this->row_is_empty( $clean, $field, $registry ) ) {
				$rows[] = $clean;
			}
		}

		$max = (int) $field->arg( 'max_rows', 0 );

		return $max > 0 ? array_slice( $rows, 0, $max ) : $rows;
	}

	public function validate( $value, Field $field, Registry $registry ): ?string {
		$min = (int) $field->arg( 'min_rows', 0 );

		if ( $min > 0 && count( $value ) < $min ) {
			/* translators: 1: field label, 2: minimum number of rows */
			return sprintf( __( '%1$s needs at least %2$d items.', 'libreblocks-core' ), $field->label, $min );
		}

		foreach ( $value as $index => $row ) {
			$errors = $registry->validate_values( $field->fields, $row );

			if ( $errors ) {
				/* translators: 1: row number, 2: error message */
				return sprintf( __( 'Item %1$d: %2$s', 'libreblocks-core' ), $index + 1, reset( $errors ) );
			}
		}

		return null;
	}

	public function schema( Field $field, Registry $registry ): array {
		$properties = [];

		foreach ( $field->fields as $child ) {
			$type = $registry->get( $child->type );

			if ( $type->stores_value() ) {
				$properties[ $child->name ] = $type->schema( $child, $registry );
			}
		}

		return [
			'type'  => 'array',
			'items' => [ 'type' => 'object', 'properties' => $properties ],
		];
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function row_is_empty( array $row, Field $field, Registry $registry ): bool {
		foreach ( $field->fields as $child ) {
			$type = $registry->get( $child->type );

			// Switches are always set; they alone don't make a row meaningful.
			if ( $type instanceof Toggle || ! $type->stores_value() ) {
				continue;
			}

			if ( ! $type->is_empty( $row[ $child->name ] ?? null ) ) {
				return false;
			}
		}

		return true;
	}
}
