<?php
/**
 * Normalized field definition, shared by LibreForge field groups and LibreForms.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields;

defined( 'ABSPATH' ) || exit;

final class Field {

	/**
	 * @param array<int, array{value: string, label: string}> $options
	 * @param array<string, mixed>                           $conditions See Conditions::matches().
	 * @param Field[]                                        $fields     Children (repeater/group).
	 * @param array<string, mixed>                           $args       Type-specific settings (min, max, multiple...).
	 * @param mixed                                          $default
	 */
	public function __construct(
		public string $name,
		public string $type,
		public string $label = '',
		public string $description = '',
		public bool $required = false,
		public $default = null,
		public array $options = [],
		public string $glossary = '',
		public array $conditions = [],
		public string $width = '100%',
		public array $fields = [],
		public array $args = []
	) {}

	/**
	 * Builds a field from a stored configuration array.
	 *
	 * @param array<string, mixed> $config
	 */
	public static function from_array( array $config ): Field {
		$known = [ 'name', 'type', 'label', 'description', 'required', 'default', 'options', 'glossary', 'conditions', 'width', 'fields' ];

		return new self(
			sanitize_key( (string) ( $config['name'] ?? '' ) ),
			sanitize_key( (string) ( $config['type'] ?? 'text' ) ),
			(string) ( $config['label'] ?? '' ),
			(string) ( $config['description'] ?? '' ),
			! empty( $config['required'] ),
			$config['default'] ?? null,
			self::normalize_options( $config['options'] ?? [] ),
			sanitize_key( (string) ( $config['glossary'] ?? '' ) ),
			is_array( $config['conditions'] ?? null ) ? $config['conditions'] : [],
			(string) ( $config['width'] ?? '100%' ),
			array_map( [ self::class, 'from_array' ], array_values( array_filter( (array) ( $config['fields'] ?? [] ), 'is_array' ) ) ),
			array_diff_key( $config, array_flip( $known ) )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array_merge( $this->args, [
			'name'        => $this->name,
			'type'        => $this->type,
			'label'       => $this->label,
			'description' => $this->description,
			'required'    => $this->required,
			'default'     => $this->default,
			'options'     => $this->options,
			'glossary'    => $this->glossary,
			'conditions'  => $this->conditions,
			'width'       => $this->width,
			'fields'      => array_map( fn( Field $child ) => $child->to_array(), $this->fields ),
		] );
	}

	/**
	 * @param mixed $fallback
	 * @return mixed
	 */
	public function arg( string $key, $fallback = null ) {
		return $this->args[ $key ] ?? $fallback;
	}

	/**
	 * @return string[]
	 */
	public function option_values(): array {
		return array_map( fn( array $option ) => $option['value'], $this->options );
	}

	/**
	 * Accepts [ 'a' => 'A' ], [ [ 'value' => 'a', 'label' => 'A' ] ] or "a : A" lines.
	 *
	 * @param mixed $options
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function normalize_options( $options ): array {
		if ( is_string( $options ) ) {
			$lines   = preg_split( '/\r\n|\r|\n/', $options ) ?: [];
			$options = [];

			foreach ( array_filter( array_map( 'trim', $lines ), 'strlen' ) as $line ) {
				$parts                          = array_map( 'trim', explode( ':', $line, 2 ) );
				$options[ (string) $parts[0] ] = $parts[1] ?? $parts[0];
			}
		}

		$normalized = [];

		foreach ( (array) $options as $key => $option ) {
			if ( is_array( $option ) ) {
				if ( ! isset( $option['value'] ) ) {
					continue;
				}

				$value = (string) $option['value'];
				$label = (string) ( $option['label'] ?? $value );
			} else {
				$value = (string) $key;
				$label = (string) $option;
			}

			$normalized[] = [ 'value' => $value, 'label' => $label ];
		}

		return $normalized;
	}
}
