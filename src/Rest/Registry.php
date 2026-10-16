<?php
/**
 * Collects REST controllers from the core and products and registers them on rest_api_init.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Rest;

use LibreBlocks\Core\Plugin;

defined( 'ABSPATH' ) || exit;

final class Registry {

	/** @var array<int, class-string<Controller>|Controller> */
	private array $controllers = [];

	public function __construct( private Plugin $core ) {
		$this->add( StatusController::class );
	}

	public function hook(): void {
		add_action( 'rest_api_init', [ $this, 'register' ] );
	}

	/**
	 * @param class-string<Controller>|Controller $controller Class name (instantiated lazily) or instance.
	 */
	public function add( $controller ): void {
		$this->controllers[] = $controller;
	}

	public function register(): void {
		foreach ( $this->controllers as $controller ) {
			if ( is_string( $controller ) ) {
				$controller = new $controller( $this->core );
			}

			$controller->register_routes();
		}
	}
}
