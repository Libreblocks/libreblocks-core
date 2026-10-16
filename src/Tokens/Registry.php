<?php
/**
 * Registry and parser for {{tokens}}.
 *
 * Syntax: {{group.key}} or {{group.key|argument}}. Unknown tokens are left as
 * they are, so text that merely looks like a token is never destroyed.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Tokens;

use LibreBlocks\Core\Context\ObjectStack;

defined( 'ABSPATH' ) || exit;

final class Registry {

	private const PATTERN = '/\{\{\s*([a-z0-9_]+(?:\.[a-z0-9_]+)*)\s*(?:\|((?:(?!\}\}).)*))?\}\}/is';

	/** @var array<string, Token> */
	private array $tokens = [];

	public function __construct( private ObjectStack $context ) {}

	public static function with_defaults( ObjectStack $context ): Registry {
		$registry = new self( $context );
		Defaults::register( $registry );

		return $registry;
	}

	public function add( Token $token ): void {
		$this->tokens[ $token->name ] = $token;
	}

	public function has( string $name ): bool {
		return isset( $this->tokens[ $name ] );
	}

	/**
	 * Resolves a single token by name.
	 *
	 * @return mixed Null when the token is unknown.
	 */
	public function value( string $name, ?string $arg = null ) {
		if ( ! isset( $this->tokens[ $name ] ) ) {
			return null;
		}

		$value = $this->tokens[ $name ]->resolve( $arg, $this->context );

		/**
		 * Filters a resolved token value.
		 *
		 * @param mixed       $value
		 * @param string      $name
		 * @param string|null $arg
		 */
		return apply_filters( 'libreblocks/tokens/value', $value, $name, $arg );
	}

	/**
	 * Replaces every known token in a text. Lists are joined with commas.
	 */
	public function render( string $text ): string {
		if ( false === strpos( $text, '{{' ) ) {
			return $text;
		}

		return (string) preg_replace_callback( self::PATTERN, function ( array $match ) {
			if ( ! $this->has( $match[1] ) ) {
				return $match[0];
			}

			return $this->stringify( $this->value( $match[1], $this->arg( $match ) ) );
		}, $text );
	}

	/**
	 * Resolves tokens inside settings or query arguments, recursively.
	 *
	 * A string that is exactly one token keeps the raw value type (int, array...),
	 * which matters for arguments such as post__in. Mixed text is rendered as a string.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function resolve( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'resolve' ], $value );
		}

		if ( ! is_string( $value ) || false === strpos( $value, '{{' ) ) {
			return $value;
		}

		if ( preg_match( self::PATTERN, $value, $match ) && trim( $value ) === $match[0] && $this->has( $match[1] ) ) {
			return $this->value( $match[1], $this->arg( $match ) );
		}

		return $this->render( $value );
	}

	/**
	 * Token list in the shape the editors need.
	 *
	 * @return array<int, array{name: string, label: string, arg_label: string}>
	 */
	public function describe(): array {
		return array_values( array_map( fn( Token $token ) => [
			'name'      => $token->name,
			'label'     => $token->label,
			'arg_label' => $token->arg_label,
		], $this->tokens ) );
	}

	/**
	 * @param array<int, string> $match
	 */
	private function arg( array $match ): ?string {
		return isset( $match[2] ) ? trim( $match[2] ) : null;
	}

	/**
	 * @param mixed $value
	 */
	private function stringify( $value ): string {
		if ( is_array( $value ) ) {
			return implode( ',', array_map( [ $this, 'stringify' ], $value ) );
		}

		if ( is_bool( $value ) ) {
			return $value ? '1' : '';
		}

		return is_scalar( $value ) ? (string) $value : '';
	}
}
