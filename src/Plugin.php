<?php
/**
 * Core entry point and service container.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core;

use LibreBlocks\Core\Admin\Admin;
use LibreBlocks\Core\Context\ObjectStack;
use LibreBlocks\Core\Fields\Registry as FieldRegistry;
use LibreBlocks\Core\Rest\Registry as RestRegistry;
use LibreBlocks\Core\Storage\Schema;
use LibreBlocks\Core\Support\Cache;
use LibreBlocks\Core\Tokens\Registry as TokenRegistry;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static ?Plugin $instance = null;

	/** @var array<string, callable> */
	private array $factories = [];

	/** @var array<string, mixed> */
	private array $services = [];

	private bool $booted = false;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooked on plugins_loaded (priority 5).
	 */
	public static function boot(): void {
		$core = self::instance();

		if ( $core->booted ) {
			return;
		}

		$core->booted = true;
		$core->register_services();

		$core->schema()->hook();
		$core->rest()->hook();

		if ( is_admin() ) {
			$core->get( 'admin' )->hook();
		}

		add_action( 'init', [ $core, 'on_init' ], 0 );

		/**
		 * Fires when the core is ready. Products register themselves here.
		 *
		 * @param Plugin $core
		 */
		do_action( 'libreblocks/core/loaded', $core );
	}

	/**
	 * Activation hook: create tables registered by the core itself.
	 */
	public static function activate(): void {
		self::boot();
		self::instance()->schema()->install();
	}

	public function on_init(): void {
		load_plugin_textdomain( 'libreblocks-core', false, dirname( plugin_basename( LIBREBLOCKS_CORE_FILE ) ) . '/languages' );

		/**
		 * Register field types.
		 *
		 * @param FieldRegistry $fields
		 */
		do_action( 'libreblocks/fields/register', $this->fields() );

		/**
		 * Register dynamic tokens.
		 *
		 * @param TokenRegistry $tokens
		 */
		do_action( 'libreblocks/tokens/register', $this->tokens() );

		do_action( 'libreblocks/core/init', $this );
	}

	/**
	 * Adds (or replaces) a lazily-created service.
	 */
	public function set( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
		unset( $this->services[ $id ] );
	}

	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] );
	}

	/**
	 * @return mixed
	 */
	public function get( string $id ) {
		if ( ! array_key_exists( $id, $this->services ) ) {
			if ( ! isset( $this->factories[ $id ] ) ) {
				throw new \InvalidArgumentException( sprintf( 'LibreBlocks service "%s" is not registered.', $id ) );
			}

			$this->services[ $id ] = ( $this->factories[ $id ] )( $this );
		}

		return $this->services[ $id ];
	}

	public function version(): string {
		return LIBREBLOCKS_CORE_VERSION;
	}

	public function products(): Products {
		return $this->get( 'products' );
	}

	public function schema(): Schema {
		return $this->get( 'schema' );
	}

	public function rest(): RestRegistry {
		return $this->get( 'rest' );
	}

	public function fields(): FieldRegistry {
		return $this->get( 'fields' );
	}

	public function tokens(): TokenRegistry {
		return $this->get( 'tokens' );
	}

	public function context(): ObjectStack {
		return $this->get( 'context' );
	}

	public function cache(): Cache {
		return $this->get( 'cache' );
	}

	private function register_services(): void {
		$this->set( 'products', fn() => new Products() );
		$this->set( 'schema', fn() => new Schema() );
		$this->set( 'cache', fn() => new Cache( 'libreblocks' ) );
		$this->set( 'context', fn() => new ObjectStack() );
		$this->set( 'fields', fn() => FieldRegistry::with_defaults() );
		$this->set( 'tokens', fn( Plugin $core ) => TokenRegistry::with_defaults( $core->context() ) );
		$this->set( 'rest', fn( Plugin $core ) => new RestRegistry( $core ) );
		$this->set( 'admin', fn( Plugin $core ) => new Admin( $core ) );
	}

	private function __construct() {}
}
