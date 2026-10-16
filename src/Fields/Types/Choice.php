<?php
/**
 * Select, radio and checkboxes. Only values present in the options are kept
 * (unless arg "allow_custom" is true). Select is multiple when arg "multiple" is true.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Choice extends BaseType {

	public function __construct( private string $type ) {}

	public function type(): string {
		return $this->type;
	}

	public function label(): string {
		switch ( $this->type ) {
			case 'radio':
				return __( 'Radio', 'libreblocks-core' );
			case 'checkbox':
				return __( 'Checkboxes', 'libreblocks-core' );
			default:
				return __( 'Select', 'libreblocks-core' );
		}
	}

	public function category(): string {
		return 'choice';
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		$values = array_map(
			fn( $item ) => sanitize_text_field( (string) $item ),
			array_filter( $this->to_list( $value ), 'is_scalar' )
		);

		if ( ! $field->arg( 'allow_custom' ) && $field->options ) {
			$values = array_intersect( $values, $field->option_values() );
		}

		$values = array_values( array_unique( array_filter( $values, 'strlen' ) ) );

		if ( $this->is_multiple( $field ) ) {
			return $values;
		}

		return $values[0] ?? '';
	}

	public function schema( Field $field, Registry $registry ): array {
		return $this->is_multiple( $field )
			? [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ]
			: [ 'type' => 'string' ];
	}

	public function is_multiple( Field $field ): bool {
		return 'checkbox' === $this->type || ( 'select' === $this->type && $field->arg( 'multiple' ) );
	}
}
