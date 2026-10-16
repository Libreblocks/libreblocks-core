<?php
/**
 * Single attachment, stored as its ID.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Media extends BaseType {

	public function type(): string {
		return 'media';
	}

	public function label(): string {
		return __( 'Media', 'libreblocks-core' );
	}

	public function category(): string {
		return 'media';
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		// Media pickers may send { id, url } objects.
		if ( is_array( $value ) && isset( $value['id'] ) ) {
			$value = $value['id'];
		}

		$id = is_scalar( $value ) ? absint( $value ) : 0;

		return $id > 0 ? $id : '';
	}

	public function schema( Field $field, Registry $registry ): array {
		return [ 'type' => [ 'integer', 'string' ] ];
	}
}
