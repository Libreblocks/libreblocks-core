<?php
/**
 * GET libreblocks/v1/status — environment and installed products, used by the dashboard.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Rest;

use LibreBlocks\Core\Admin\Icons;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

final class StatusController extends Controller {

	public function register_routes(): void {
		$this->route( '/status', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'status' ],
			'permission_callback' => [ $this, 'can_manage' ],
		] );
	}

	public function status(): WP_REST_Response {
		$products = [];

		foreach ( $this->core->products()->all() as $product ) {
			$products[] = [
				'slug'       => $product['slug'],
				'name'       => $product['name'],
				'version'    => $product['version'],
				'pro'        => (bool) $product['pro'],
				'compatible' => (bool) $product['compatible'],
				'url'        => $product['menu'] ? admin_url( 'admin.php?page=' . $product['menu'] ) : '',
				'icon'       => Icons::data_uri( $product['slug'] ),
			];
		}

		return new WP_REST_Response( [
			'core'           => $this->core->version(),
			'php'            => PHP_VERSION,
			'wp'             => get_bloginfo( 'version' ),
			'products'       => $products,
			'pending_tables' => $this->core->schema()->pending(),
			'field_types'    => $this->core->fields()->describe(),
			'tokens'         => $this->core->tokens()->describe(),
		] );
	}
}
