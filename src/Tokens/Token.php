<?php
/**
 * A dynamic value usable as {{name}} or {{name|argument}}.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Tokens;

use LibreBlocks\Core\Context\ObjectStack;

defined( 'ABSPATH' ) || exit;

final class Token {

	/** @var callable(string|null, ObjectStack): mixed */
	private $resolver;

	/**
	 * @param string                                    $name      Dotted name, e.g. "object.meta".
	 * @param callable(string|null, ObjectStack): mixed $resolver  Receives the argument after "|" (or null).
	 * @param string                                    $label     Human label for pickers.
	 * @param string                                    $arg_label Label of the argument; empty when it takes none.
	 */
	public function __construct(
		public string $name,
		callable $resolver,
		public string $label = '',
		public string $arg_label = ''
	) {
		$this->resolver = $resolver;
	}

	/**
	 * @return mixed
	 */
	public function resolve( ?string $arg, ObjectStack $context ) {
		return ( $this->resolver )( $arg, $context );
	}
}
