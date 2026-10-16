<?php
/**
 * Registry of LibreBlocks products (LibreForge, LibreSift...) running on this site.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core;

defined( 'ABSPATH' ) || exit;

final class Products {

	/** @var array<string, array<string, mixed>> */
	private array $products = [];

	/**
	 * Registers a product. Returns false (and shows an admin notice) when the
	 * installed core is older than the product needs; the product must not boot.
	 *
	 * @param array{name: string, version: string, file: string, requires_core?: string, pro?: bool, menu?: string} $args
	 */
	public function register( string $slug, array $args ): bool {
		$product = wp_parse_args( $args, [
			'name'          => $slug,
			'version'       => '0.0.0',
			'file'          => '',
			'requires_core' => '0.1.0',
			'pro'           => false,
			'menu'          => '',
		] );

		$product['slug']       = $slug;
		$product['compatible'] = version_compare( LIBREBLOCKS_CORE_VERSION, $product['requires_core'], '>=' );

		$this->products[ $slug ] = $product;

		if ( ! $product['compatible'] ) {
			add_action( 'admin_notices', function () use ( $product ) {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html( sprintf(
						/* translators: 1: product name, 2: required core version */
						__( '%1$s needs LibreBlocks Core %2$s or newer. Please update LibreBlocks Core.', 'libreblocks-core' ),
						$product['name'],
						$product['requires_core']
					) )
				);
			} );
		}

		return $product['compatible'];
	}

	public function is_active( string $slug ): bool {
		return ! empty( $this->products[ $slug ]['compatible'] );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get( string $slug ): ?array {
		return $this->products[ $slug ] ?? null;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function all(): array {
		return $this->products;
	}
}
