<?php
/**
 * Minimal PSR-4 autoloader shared by every LibreBlocks plugin.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core;

defined( 'ABSPATH' ) || exit;

final class Autoloader {

	/**
	 * Namespace prefix => list of base directories.
	 *
	 * @var array<string, string[]>
	 */
	private static array $prefixes = [];

	private static bool $registered = false;

	/**
	 * Maps a namespace prefix to a directory, e.g. ( 'LibreBlocks\\Forge\\', '/path/src/' ).
	 */
	public static function register( string $prefix, string $base_dir ): void {
		$prefix   = trim( $prefix, '\\' ) . '\\';
		$base_dir = rtrim( $base_dir, '/\\' ) . '/';

		self::$prefixes[ $prefix ][] = $base_dir;

		if ( ! self::$registered ) {
			spl_autoload_register( [ self::class, 'load' ] );
			self::$registered = true;
		}
	}

	public static function load( string $class ): void {
		foreach ( self::$prefixes as $prefix => $dirs ) {
			if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
				continue;
			}

			$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';

			foreach ( $dirs as $dir ) {
				if ( is_file( $dir . $relative ) ) {
					require $dir . $relative;
					return;
				}
			}
		}
	}
}
