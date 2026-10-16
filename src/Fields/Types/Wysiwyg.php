<?php
/**
 * Rich text, filtered with the same rules as post content.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Wysiwyg extends BaseType {

	public function type(): string {
		return 'wysiwyg';
	}

	public function label(): string {
		return __( 'Rich text', 'libreblocks-core' );
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		return is_scalar( $value ) ? wp_kses_post( (string) $value ) : '';
	}
}
