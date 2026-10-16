<?php
/**
 * Object cache wrapper with group invalidation.
 *
 * Each group carries a "generation" salt; flushing a group bumps the salt so
 * every old key becomes unreachable, which works with any persistent object
 * cache backend (none of them support deleting by group reliably).
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Support;

defined( 'ABSPATH' ) || exit;

final class Cache {

	public function __construct( private string $namespace ) {}

	/**
	 * @return mixed|null Null on miss.
	 */
	public function get( string $key, string $group ) {
		$found = false;
		$value = wp_cache_get( $this->key( $key, $group ), $this->namespace, false, $found );

		return $found ? $value : null;
	}

	/**
	 * @param mixed $value
	 */
	public function set( string $key, string $group, $value, int $ttl = 0 ): void {
		wp_cache_set( $this->key( $key, $group ), $value, $this->namespace, $ttl );
	}

	/**
	 * Returns the cached value or computes, stores and returns it.
	 *
	 * @return mixed
	 */
	public function remember( string $key, string $group, callable $compute, int $ttl = 0 ) {
		$value = $this->get( $key, $group );

		if ( null === $value ) {
			$value = $compute();
			$this->set( $key, $group, $value, $ttl );
		}

		return $value;
	}

	public function flush_group( string $group ): void {
		wp_cache_set( 'gen:' . $group, $this->new_generation(), $this->namespace );
	}

	/**
	 * Stable cache key for an array of arguments (e.g. query args).
	 *
	 * @param mixed $args
	 */
	public static function hash( $args ): string {
		return md5( (string) wp_json_encode( $args ) );
	}

	private function key( string $key, string $group ): string {
		return $group . ':' . $this->generation( $group ) . ':' . $key;
	}

	private function generation( string $group ): string {
		$generation = wp_cache_get( 'gen:' . $group, $this->namespace );

		if ( false === $generation ) {
			$generation = $this->new_generation();
			wp_cache_set( 'gen:' . $group, $generation, $this->namespace );
		}

		return (string) $generation;
	}

	private function new_generation(): string {
		return str_replace( '.', '', (string) microtime( true ) );
	}
}
