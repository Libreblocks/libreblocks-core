<?php
/**
 * On/off switch, stored as a boolean.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Toggle extends BaseType {

	public function type(): string {
		return 'switch';
	}

	public function label(): string {
		return __( 'Switch', 'libreblocks-core' );
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	// A switch that is off is a valid answer, so "required" only means "must be on".
	public function is_empty( $value ): bool {
		return true !== $value;
	}

	public function schema( Field $field, Registry $registry ): array {
		return [ 'type' => 'boolean' ];
	}
}
