<?php
/**
 * Icon reference such as "dashicons-admin-home" or "lb:star".
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Icon extends BaseType {

	public function type(): string {
		return 'icon';
	}

	public function label(): string {
		return __( 'Icon', 'libreblocks-core' );
	}

	public function category(): string {
		return 'media';
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		return is_scalar( $value ) ? (string) preg_replace( '/[^a-z0-9_\-: ]/i', '', (string) $value ) : '';
	}
}
