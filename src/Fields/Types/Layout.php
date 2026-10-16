<?php
/**
 * Layout-only elements that never store a value: "html" (static content) and "tab".
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class Layout extends BaseType {

	public function __construct( private string $type ) {}

	public function type(): string {
		return $this->type;
	}

	public function label(): string {
		return 'tab' === $this->type ? __( 'Tab', 'libreblocks-core' ) : __( 'HTML', 'libreblocks-core' );
	}

	public function category(): string {
		return 'layout';
	}

	public function stores_value(): bool {
		return false;
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		return null;
	}
}
