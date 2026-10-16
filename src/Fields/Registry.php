<?php
/**
 * Field type registry plus sanitize/validate helpers for sets of fields.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields;

use LibreBlocks\Core\Fields\Types;

defined( 'ABSPATH' ) || exit;

final class Registry {

	/** @var array<string, FieldType> */
	private array $types = [];

	public static function with_defaults(): Registry {
		$registry = new self();

		foreach ( [
			new Types\Text(),
			new Types\Textarea(),
			new Types\Wysiwyg(),
			new Types\Number(),
			new Types\Email(),
			new Types\Url(),
			new Types\DateTime( 'date' ),
			new Types\DateTime( 'time' ),
			new Types\DateTime( 'datetime' ),
			new Types\Choice( 'select' ),
			new Types\Choice( 'radio' ),
			new Types\Choice( 'checkbox' ),
			new Types\Toggle(),
			new Types\Color(),
			new Types\Media(),
			new Types\Gallery(),
			new Types\ObjectPicker( 'post' ),
			new Types\ObjectPicker( 'term' ),
			new Types\ObjectPicker( 'user' ),
			new Types\Icon(),
			new Types\Repeater(),
			new Types\Group(),
			new Types\Layout( 'html' ),
			new Types\Layout( 'tab' ),
		] as $type ) {
			$registry->register( $type );
		}

		return $registry;
	}

	public function register( FieldType $type ): void {
		$this->types[ $type->type() ] = $type;
	}

	public function has( string $type ): bool {
		return isset( $this->types[ $type ] );
	}

	public function get( string $type ): FieldType {
		if ( ! isset( $this->types[ $type ] ) ) {
			throw new \InvalidArgumentException( sprintf( 'Unknown LibreBlocks field type "%s".', $type ) );
		}

		return $this->types[ $type ];
	}

	/**
	 * @return array<string, FieldType>
	 */
	public function types(): array {
		return $this->types;
	}

	/**
	 * Builds Field objects from stored configs, skipping unknown types and nameless fields.
	 *
	 * @param array<int, array<string, mixed>|Field> $configs
	 * @return Field[]
	 */
	public function make_all( array $configs ): array {
		$fields = [];

		foreach ( $configs as $config ) {
			$field = $config instanceof Field ? $config : Field::from_array( (array) $config );

			if ( $this->has( $field->type ) && ( '' !== $field->name || ! $this->get( $field->type )->stores_value() ) ) {
				$fields[] = $this->resolve_options( $field );
			}
		}

		return $fields;
	}

	/**
	 * Lets products fill options from dynamic sources (glossaries, taxonomies...).
	 */
	public function resolve_options( Field $field ): Field {
		/**
		 * Filters the options of a choice field.
		 *
		 * @param array<int, array{value: string, label: string}> $options
		 * @param Field                                           $field
		 */
		$options = apply_filters( 'libreblocks/fields/options', $field->options, $field );

		$field->options = Field::normalize_options( $options );
		$field->fields  = $this->make_all( $field->fields );

		return $field;
	}

	/**
	 * Sanitizes the input for a set of fields. Only storing fields are returned;
	 * missing input falls back to the field default.
	 *
	 * @param Field[]              $fields
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	public function sanitize_values( array $fields, array $input ): array {
		$values = [];

		foreach ( $fields as $field ) {
			$type = $this->get( $field->type );

			if ( ! $type->stores_value() ) {
				continue;
			}

			$raw                     = array_key_exists( $field->name, $input ) ? $input[ $field->name ] : $field->default;
			$values[ $field->name ] = $type->sanitize( $raw, $field, $this );
		}

		return $values;
	}

	/**
	 * Validates sanitized values. Fields hidden by their conditions are skipped.
	 *
	 * @param Field[]              $fields
	 * @param array<string, mixed> $values
	 * @return array<string, string> Field name => error message.
	 */
	public function validate_values( array $fields, array $values ): array {
		$errors = [];

		foreach ( $fields as $field ) {
			$type = $this->get( $field->type );

			if ( ! $type->stores_value() || ! Conditions::matches( $field->conditions, $values ) ) {
				continue;
			}

			$value = $values[ $field->name ] ?? null;

			if ( $type->is_empty( $value ) ) {
				if ( $field->required ) {
					/* translators: %s: field label */
					$errors[ $field->name ] = sprintf( __( '%s is required.', 'libreblocks-core' ), $field->label ?: $field->name );
				}
				continue;
			}

			$error = $type->validate( $value, $field, $this );

			if ( null !== $error ) {
				$errors[ $field->name ] = $error;
			}
		}

		return $errors;
	}

	/**
	 * Field types in the shape the admin UI needs.
	 *
	 * @return array<int, array{type: string, label: string, category: string, stores_value: bool}>
	 */
	public function describe(): array {
		return array_values( array_map( fn( FieldType $type ) => [
			'type'         => $type->type(),
			'label'        => $type->label(),
			'category'     => $type->category(),
			'stores_value' => $type->stores_value(),
		], $this->types ) );
	}
}
