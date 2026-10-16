<?php
/**
 * Picks posts, terms or users by ID. Args: multiple, post_type / taxonomy / role (UI filters).
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class ObjectPicker extends BaseType {

	private const OBJECTS = [ 'post', 'term', 'user' ];

	public function __construct( private string $object ) {
		if ( ! in_array( $object, self::OBJECTS, true ) ) {
			throw new \InvalidArgumentException( sprintf( 'Unsupported object "%s".', $object ) );
		}
	}

	public function type(): string {
		return $this->object . '_picker';
	}

	public function object(): string {
		return $this->object;
	}

	public function label(): string {
		switch ( $this->object ) {
			case 'term':
				return __( 'Terms', 'libreblocks-core' );
			case 'user':
				return __( 'Users', 'libreblocks-core' );
			default:
				return __( 'Posts', 'libreblocks-core' );
		}
	}

	public function category(): string {
		return 'relational';
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		$ids = $this->to_id_list( $value );

		if ( $field->arg( 'multiple' ) ) {
			return $ids;
		}

		return $ids[0] ?? '';
	}

	public function schema( Field $field, Registry $registry ): array {
		return $field->arg( 'multiple' )
			? [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ]
			: [ 'type' => 'integer' ];
	}
}
