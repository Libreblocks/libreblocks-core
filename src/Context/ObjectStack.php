<?php
/**
 * Tracks the "current object" while loops render, including nested loops.
 *
 * The bottom of the stack is the queried object of the page (post, term,
 * author...). Each loop pushes its item before rendering and pops it after;
 * dynamic fields and tokens always read the top.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Context;

defined( 'ABSPATH' ) || exit;

final class ObjectStack {

	/** @var array<int, mixed> */
	private array $stack = [];

	/** @var callable */
	private $root_resolver;

	public function __construct( ?callable $root_resolver = null ) {
		$this->root_resolver = $root_resolver ?? static fn() => get_queried_object();
	}

	/**
	 * @param mixed $object WP_Post, WP_Term, WP_User, WP_Comment or an array row.
	 */
	public function push( $object ): void {
		$this->stack[] = $object;
	}

	/**
	 * @return mixed The removed object, or null when the stack was empty.
	 */
	public function pop() {
		return array_pop( $this->stack );
	}

	/**
	 * Runs $callback with $object as the current object, restoring the stack afterwards
	 * even when the callback throws.
	 *
	 * @param mixed $object
	 * @return mixed Whatever the callback returns.
	 */
	public function with( $object, callable $callback ) {
		$this->push( $object );

		try {
			return $callback( $object );
		} finally {
			$this->pop();
		}
	}

	/**
	 * @return mixed Top of the stack, falling back to the queried object.
	 */
	public function current() {
		return $this->stack ? end( $this->stack ) : $this->root();
	}

	/**
	 * @return mixed The object the page is about.
	 */
	public function root() {
		return ( $this->root_resolver )();
	}

	/**
	 * @return mixed The object one level above the current one (useful in nested loops).
	 */
	public function parent() {
		$count = count( $this->stack );

		return $count > 1 ? $this->stack[ $count - 2 ] : $this->root();
	}

	public function depth(): int {
		return count( $this->stack );
	}

	/**
	 * Identifies an object regardless of its class.
	 *
	 * @param mixed $object
	 * @return array{type: string, id: int|null}
	 */
	public static function describe( $object ): array {
		if ( $object instanceof \WP_Post ) {
			return [ 'type' => 'post', 'id' => (int) $object->ID ];
		}

		if ( $object instanceof \WP_Term ) {
			return [ 'type' => 'term', 'id' => (int) $object->term_id ];
		}

		if ( $object instanceof \WP_User ) {
			return [ 'type' => 'user', 'id' => (int) $object->ID ];
		}

		if ( $object instanceof \WP_Comment ) {
			return [ 'type' => 'comment', 'id' => (int) $object->comment_ID ];
		}

		if ( $object instanceof \WP_Post_Type ) {
			return [ 'type' => 'post_type', 'id' => null ];
		}

		if ( is_array( $object ) || is_object( $object ) ) {
			$row = (array) $object;
			$id  = $row['id'] ?? $row['ID'] ?? $row['_id'] ?? null;

			return [ 'type' => 'row', 'id' => is_numeric( $id ) ? (int) $id : null ];
		}

		return [ 'type' => 'none', 'id' => null ];
	}
}
