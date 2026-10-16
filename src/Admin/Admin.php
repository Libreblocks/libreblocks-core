<?php
/**
 * Admin menus of the LibreBlocks family.
 *
 * The core owns the "LibreBlocks" menu (dashboard). Each product gets its own
 * top-level menu right below it, declared with add_menu():
 *
 *     libreblocks()->get( 'admin' )->add_menu( [
 *         'slug'  => 'libreforge',
 *         'title' => 'LibreForge',
 *         'icon'  => Icons::data_uri( 'libreforge' ),
 *         'pages' => [
 *             [ 'slug' => 'libreforge', 'title' => __( 'Dashboard', 'libreforge' ), 'render' => $render ],
 *             [ 'slug' => 'libreforge-post-types', 'title' => __( 'Post types', 'libreforge' ), 'render' => $render ],
 *         ],
 *     ] );
 *
 * The first page of a menu must use the menu slug. add_page() adds a page under any menu
 * (the LibreBlocks menu by default).
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Admin;

use LibreBlocks\Core\Plugin;
use LibreBlocks\Core\Rest\Controller;

defined( 'ABSPATH' ) || exit;

final class Admin {

	public const SLUG = 'libreblocks';

	/** Menu position of LibreBlocks; product menus follow at +0.01 steps. */
	private const POSITION = 58.5;

	/** @var array<string, array{slug: string, title: string, icon: string, order: int, pages: array<int, array<string, mixed>>}> */
	private array $menus = [];

	/** @var array<string, array{parent: string, slug: string, title: string, render: callable, position: int, capability: string}> */
	private array $pages = [];

	/** @var array<string, string> Page slug => hook suffix. */
	private array $hooks = [];

	public function __construct( private Plugin $core ) {}

	public function hook(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_filter( 'admin_body_class', [ $this, 'body_class' ] );
	}

	/**
	 * @param array{slug: string, title: string, icon?: string, order?: int, pages: array<int, array<string, mixed>>} $menu
	 */
	public function add_menu( array $menu ): void {
		$this->menus[ $menu['slug'] ] = [
			'slug'  => $menu['slug'],
			'title' => $menu['title'],
			'icon'  => $menu['icon'] ?? Icons::data_uri( 'libreblocks' ),
			'order' => (int) ( $menu['order'] ?? 10 ),
			'pages' => $menu['pages'],
		];
	}

	/**
	 * @param array{slug: string, title: string, render: callable, parent?: string, position?: int, capability?: string} $page
	 */
	public function add_page( array $page ): void {
		$this->pages[ $page['slug'] ] = [
			'parent'     => $page['parent'] ?? self::SLUG,
			'slug'       => $page['slug'],
			'title'      => $page['title'],
			'render'     => $page['render'],
			'position'   => (int) ( $page['position'] ?? 50 ),
			'capability' => $page['capability'] ?? Controller::capability(),
		];
	}

	public function register_menu(): void {
		$capability = Controller::capability();
		$dashboard  = new Dashboard( $this->core );

		$this->hooks[ self::SLUG ] = (string) add_menu_page(
			__( 'LibreBlocks', 'libreblocks-core' ),
			__( 'LibreBlocks', 'libreblocks-core' ),
			$capability,
			self::SLUG,
			[ $dashboard, 'render' ],
			Icons::data_uri( 'libreblocks' ),
			(string) self::POSITION
		);

		// Rename the auto-created first sub-item from "LibreBlocks" to "Dashboard".
		add_submenu_page( self::SLUG, __( 'Dashboard', 'libreblocks-core' ), __( 'Dashboard', 'libreblocks-core' ), $capability, self::SLUG, [ $dashboard, 'render' ] );

		$menus = $this->menus;
		uasort( $menus, fn( $a, $b ) => $a['order'] <=> $b['order'] );
		$step = 1;

		foreach ( $menus as $menu ) {
			$first = $menu['pages'][0];
			$cap   = $first['capability'] ?? $capability;

			$this->hooks[ $menu['slug'] ] = (string) add_menu_page( $menu['title'], $menu['title'], $cap, $menu['slug'], $first['render'], $menu['icon'], (string) ( self::POSITION + $step++ / 100 ) );

			foreach ( $menu['pages'] as $page ) {
				$this->hooks[ $page['slug'] ] = (string) add_submenu_page( $menu['slug'], $page['title'], $page['title'], $page['capability'] ?? $capability, $page['slug'], $page['render'] );
			}
		}

		$pages = $this->pages;
		uasort( $pages, fn( $a, $b ) => $a['position'] <=> $b['position'] );

		foreach ( $pages as $page ) {
			$this->hooks[ $page['slug'] ] = (string) add_submenu_page( $page['parent'], $page['title'], $page['title'], $page['capability'], $page['slug'], $page['render'] );
		}
	}

	/**
	 * Hook suffix of a page, so products can enqueue assets only on their screens.
	 */
	public function page_hook( string $slug ): string {
		return $this->hooks[ $slug ] ?? '';
	}

	/**
	 * Adds "libreblocks-screen" to the body of every LibreBlocks screen (for full-width layouts).
	 */
	public function body_class( string $classes ): string {
		$screen = get_current_screen();

		if ( $screen && in_array( $screen->id, $this->hooks, true ) ) {
			$classes .= ' libreblocks-screen';
		}

		return $classes;
	}

	/**
	 * Loads the shared field renderer (and the WordPress media and editor
	 * scripts it uses). Products call it on screens that print field forms.
	 */
	public function enqueue_fields(): void {
		$asset_file = LIBREBLOCKS_CORE_DIR . 'build/fields.asset.php';

		if ( wp_script_is( 'libreblocks-fields', 'enqueued' ) || ! is_file( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_media();
		wp_enqueue_editor();

		wp_enqueue_script( 'libreblocks-fields', LIBREBLOCKS_CORE_URL . 'build/fields.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'libreblocks-fields', 'libreblocks-core', LIBREBLOCKS_CORE_DIR . 'languages' );
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'dashicons' );

		if ( is_file( LIBREBLOCKS_CORE_DIR . 'build/fields.css' ) ) {
			wp_enqueue_style( 'libreblocks-fields', LIBREBLOCKS_CORE_URL . 'build/fields.css', [ 'wp-components' ], $asset['version'] );
		}
	}

	/**
	 * Products bundle the shared UI kit (@libreblocks/ui), whose strings use the
	 * "libreblocks-core" text domain. This loads those translations for $handle.
	 */
	public function share_ui_translations( string $handle ): void {
		if ( ! Dashboard::register_script() ) {
			return;
		}

		$json = load_script_textdomain( Dashboard::HANDLE, 'libreblocks-core', LIBREBLOCKS_CORE_DIR . 'languages' );

		if ( $json ) {
			wp_add_inline_script(
				$handle,
				'( function( data ) { wp.i18n.setLocaleData( data.locale_data[ data.domain ] || data.locale_data.messages, "libreblocks-core" ); } )( ' . $json . ' );',
				'before'
			);
		}
	}

	public function enqueue( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->page_hook( self::SLUG ) ) {
			return;
		}

		( new Dashboard( $this->core ) )->enqueue();
	}
}
