<?php
/**
 * JSON helpers for configuration columns.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Support;

defined( 'ABSPATH' ) || exit;

final class Json {

	/**
	 * @param mixed $data
	 */
	public static function encode( $data ): string {
		$json = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		return false === $json ? '{}' : $json;
	}

	/**
	 * Decodes to an associative array; invalid or empty input gives [].
	 *
	 * @return array<mixed>
	 */
	public static function decode( ?string $json ): array {
		if ( null === $json || '' === $json ) {
			return [];
		}

		$data = json_decode( $json, true );

		return is_array( $data ) ? $data : [];
	}
}
