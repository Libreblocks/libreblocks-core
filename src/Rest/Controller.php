<?php
/**
 * Base class for LibreBlocks REST controllers (namespace libreblocks/v1).
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Rest;

use LibreBlocks\Core\Plugin;
use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

abstract class Controller {

	public const REST_NAMESPACE = 'libreblocks/v1';

	public function __construct( protected Plugin $core ) {}

	abstract public function register_routes(): void;

	/**
	 * Shortcut for register_rest_route() under the shared namespace.
	 *
	 * @param array<int|string, mixed> $endpoints One endpoint definition or a list of them.
	 */
	protected function route( string $path, array $endpoints ): void {
		register_rest_route( self::REST_NAMESPACE, $path, $endpoints );
	}

	/**
	 * Permission callback for configuration endpoints.
	 */
	public function can_manage( ?WP_REST_Request $request = null ): bool {
		return current_user_can( self::capability() );
	}

	/**
	 * Capability required to configure LibreBlocks. Filterable so agencies can
	 * delegate configuration to a custom role.
	 */
	public static function capability(): string {
		return (string) apply_filters( 'libreblocks/capability', 'manage_options' );
	}

	protected function error( string $code, string $message, int $status = 400 ): WP_Error {
		return new WP_Error( 'libreblocks_' . $code, $message, [ 'status' => $status ] );
	}
}
