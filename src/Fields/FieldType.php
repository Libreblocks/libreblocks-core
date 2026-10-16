<?php
/**
 * Contract for a field type.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields;

defined( 'ABSPATH' ) || exit;

interface FieldType {

	/** Machine name, e.g. "text". */
	public function type(): string;

	/** Human label shown in the field picker. */
	public function label(): string;

	/** One of: basic, choice, media, relational, structure, layout. */
	public function category(): string;

	/** False for layout-only types (html, tab) that never store anything. */
	public function stores_value(): bool;

	/**
	 * Cleans raw input into the value that will be stored.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function sanitize( $value, Field $field, Registry $registry );

	/**
	 * Type-specific validation of an already sanitized, non-empty value.
	 *
	 * @param mixed $value
	 * @return string|null Error message, or null when valid.
	 */
	public function validate( $value, Field $field, Registry $registry ): ?string;

	/**
	 * @param mixed $value
	 */
	public function is_empty( $value ): bool;

	/**
	 * JSON schema used for register_meta( show_in_rest ) and REST validation.
	 *
	 * @return array<string, mixed>
	 */
	public function schema( Field $field, Registry $registry ): array;
}
