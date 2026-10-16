<?php
/**
 * Shared "LibreBlocks" admin menu. Products add their screens as sub-pages.
 *
 * Usage from a product (during "libreblocks/core/loaded"):
 *
 *     libreblocks()->get( 'admin' )->add_page( [
 *         'slug'     => 'libreforge-post-types',
 *         'title'    => __( 'Post Types', 'libreforge' ),
 *         'render'   => [ $screen, 'render' ],
 *         'position' => 20,
 *     ] );
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Admin;

use LibreBlocks\Core\Plugin;
use LibreBlocks\Core\Rest\Controller;

defined( 'ABSPATH' ) || exit;

final class Admin {

	public const SLUG = 'libreblocks';

	/** @var array<string, array{slug: string, title: string, render: callable, position: int, capability: string}> */
	private array $pages = [];

	/** @var array<string, string> Page slug => hook suffix. */
	private array $hooks = [];

	public function __construct( private Plugin $core ) {}

	public function hook(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * @param array{slug: string, title: string, render: callable, position?: int, capability?: string} $page
	 */
	public function add_page( array $page ): void {
		$this->pages[ $page['slug'] ] = [
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
			'dashicons-screenoptions',
			58
		);

		// Rename the auto-created first sub-item from "LibreBlocks" to "Dashboard".
		add_submenu_page( self::SLUG, __( 'Dashboard', 'libreblocks-core' ), __( 'Dashboard', 'libreblocks-core' ), $capability, self::SLUG, [ $dashboard, 'render' ] );

		$pages = $this->pages;
		uasort( $pages, fn( $a, $b ) => $a['position'] <=> $b['position'] );

		foreach ( $pages as $page ) {
			$this->hooks[ $page['slug'] ] = (string) add_submenu_page( self::SLUG, $page['title'], $page['title'], $page['capability'], $page['slug'], $page['render'] );
		}
	}

	/**
	 * Hook suffix of a page, so products can enqueue assets only on their screens.
	 */
	public function page_hook( string $slug ): string {
		return $this->hooks[ $slug ] ?? '';
	}

	public function enqueue( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->page_hook( self::SLUG ) ) {
			return;
		}

		( new Dashboard( $this->core ) )->enqueue();
	}
}
