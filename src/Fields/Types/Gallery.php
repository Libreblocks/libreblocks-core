<?php
/**
 * Ordered list of attachment IDs.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Gallery extends BaseType {

	public function type(): string {
		return 'gallery';
	}

	public function label(): string {
		return __( 'Gallery', 'libreblocks-core' );
	}

	public function category(): string {
		return 'media';
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		$items = array_map(
			fn( $item ) => is_array( $item ) ? ( $item['id'] ?? 0 ) : $item,
			$this->to_list( $value )
		);

		return $this->to_id_list( $items );
	}

	public function schema( Field $field, Registry $registry ): array {
		return [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ];
	}
}
