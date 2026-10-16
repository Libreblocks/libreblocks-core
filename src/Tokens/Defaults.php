<?php
/**
 * Built-in tokens. Products add their own (relations, options pages...) on
 * the "libreblocks/tokens/register" action.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Tokens;

use LibreBlocks\Core\Context\ObjectStack;

defined( 'ABSPATH' ) || exit;

final class Defaults {

	public static function register( Registry $registry ): void {
		$key_label = __( 'Key', 'libreblocks-core' );

		// Current object (top of the loop stack).
		$registry->add( new Token( 'object.id', fn( $arg, ObjectStack $ctx ) => ObjectStack::describe( $ctx->current() )['id'], __( 'Current object ID', 'libreblocks-core' ) ) );
		$registry->add( new Token( 'object.type', fn( $arg, ObjectStack $ctx ) => ObjectStack::describe( $ctx->current() )['type'], __( 'Current object type', 'libreblocks-core' ) ) );
		$registry->add( new Token( 'object.meta', fn( $arg, ObjectStack $ctx ) => self::meta( $ctx->current(), $arg ), __( 'Current object meta', 'libreblocks-core' ), $key_label ) );
		$registry->add( new Token( 'object.prop', fn( $arg, ObjectStack $ctx ) => self::prop( $ctx->current(), $arg ), __( 'Current object property', 'libreblocks-core' ), $key_label ) );
		$registry->add( new Token( 'parent.id', fn( $arg, ObjectStack $ctx ) => ObjectStack::describe( $ctx->parent() )['id'], __( 'Parent loop object ID', 'libreblocks-core' ) ) );

		// Queried object (what the page is about).
		$registry->add( new Token( 'queried.id', fn( $arg, ObjectStack $ctx ) => ObjectStack::describe( $ctx->root() )['id'], __( 'Page object ID', 'libreblocks-core' ) ) );
		$registry->add( new Token( 'queried.meta', fn( $arg, ObjectStack $ctx ) => self::meta( $ctx->root(), $arg ), __( 'Page object meta', 'libreblocks-core' ), $key_label ) );

		// Logged-in user.
		$registry->add( new Token( 'user.id', fn() => get_current_user_id(), __( 'Current user ID', 'libreblocks-core' ) ) );
		$registry->add( new Token( 'user.meta', fn( $arg ) => self::meta( wp_get_current_user(), $arg ), __( 'Current user meta', 'libreblocks-core' ), $key_label ) );
		$registry->add( new Token( 'user.roles', fn() => array_values( wp_get_current_user()->roles ), __( 'Current user roles', 'libreblocks-core' ) ) );

		// Request.
		$registry->add( new Token( 'url.param', fn( $arg ) => self::url_param( $arg ), __( 'URL parameter', 'libreblocks-core' ), __( 'Parameter name', 'libreblocks-core' ) ) );

		// Options (only allow-listed names; see self::option()).
		$registry->add( new Token( 'option', fn( $arg ) => self::option( $arg ), __( 'Site option', 'libreblocks-core' ), __( 'option or option:key', 'libreblocks-core' ) ) );

		// Dates, in the site timezone.
		$registry->add( new Token( 'date.today', fn() => wp_date( 'Y-m-d' ), __( 'Today (Y-m-d)', 'libreblocks-core' ) ) );
		$registry->add( new Token( 'date.now', fn() => wp_date( 'Y-m-d\TH:i' ), __( 'Now (Y-m-d\TH:i)', 'libreblocks-core' ) ) );
		$registry->add( new Token( 'date.str', fn( $arg ) => self::relative_date( $arg, 'Y-m-d' ), __( 'Relative date (Y-m-d)', 'libreblocks-core' ), __( 'e.g. +7 days', 'libreblocks-core' ) ) );
		$registry->add( new Token( 'date.timestamp', fn( $arg ) => self::relative_date( $arg, 'U' ), __( 'Relative timestamp', 'libreblocks-core' ), __( 'e.g. +7 days', 'libreblocks-core' ) ) );
	}

	/**
	 * @param mixed $object
	 * @return mixed
	 */
	public static function meta( $object, ?string $key ) {
		// Login sessions must never be printable.
		if ( null === $key || '' === $key || 'session_tokens' === $key ) {
			return null;
		}

		$described = ObjectStack::describe( $object );

		if ( 'row' === $described['type'] ) {
			return ( (array) $object )[ $key ] ?? null;
		}

		if ( ! in_array( $described['type'], [ 'post', 'term', 'user', 'comment' ], true ) || ! $described['id'] ) {
			return null;
		}

		$value = get_metadata( $described['type'], $described['id'], $key, true );

		return '' === $value ? null : $value;
	}

	/**
	 * Public properties only (post_title, name, display_name...). Never exposes user_pass.
	 *
	 * @param mixed $object
	 * @return mixed
	 */
	public static function prop( $object, ?string $key ) {
		if ( null === $key || '' === $key || in_array( $key, [ 'user_pass', 'user_activation_key', 'post_password' ], true ) ) {
			return null;
		}

		if ( $object instanceof \WP_User ) {
			$object = $object->data;
		}

		$data = is_object( $object ) ? get_object_vars( $object ) : (array) $object;

		return $data[ $key ] ?? null;
	}

	/**
	 * @return string|string[]|null
	 */
	public static function url_param( ?string $name ) {
		if ( null === $name || ! isset( $_GET[ $name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return null;
		}

		$value = wp_unslash( $_GET[ $name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return is_array( $value ) ? array_map( 'sanitize_text_field', array_filter( $value, 'is_scalar' ) ) : sanitize_text_field( $value );
	}

	/**
	 * Reads "name" or "name:key" (array option). Only option names that pass the
	 * allow-list are readable, so template editors cannot print secrets stored in
	 * other options (API keys, SMTP passwords...).
	 *
	 * @return mixed
	 */
	public static function option( ?string $arg ) {
		if ( null === $arg || '' === $arg ) {
			return null;
		}

		[ $name, $key ] = array_pad( explode( ':', $arg, 2 ), 2, null );

		/**
		 * Option names readable through {{option|...}}.
		 *
		 * @param string[] $allowed
		 */
		$allowed = (array) apply_filters( 'libreblocks/tokens/allowed_options', [ 'blogname', 'blogdescription', 'home', 'siteurl' ] );

		if ( ! in_array( $name, $allowed, true ) ) {
			return null;
		}

		$value = get_option( $name );

		if ( null === $key ) {
			return $value;
		}

		return is_array( $value ) ? ( $value[ $key ] ?? null ) : null;
	}

	/**
	 * @return string|int|null
	 */
	public static function relative_date( ?string $expression, string $format ) {
		$expression = trim( (string) $expression );

		try {
			$date = new \DateTimeImmutable( '' === $expression ? 'now' : $expression, wp_timezone() );
		} catch ( \Exception $e ) {
			return null;
		}

		return 'U' === $format ? $date->getTimestamp() : $date->format( $format );
	}
}
