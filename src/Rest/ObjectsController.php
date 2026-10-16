<?php
/**
 * GET libreblocks/v1/objects — search posts, terms or users for picker fields.
 *
 *   ?object=post&search=casa&post_type=house,page
 *   ?object=term&taxonomy=city
 *   ?object=user&role=editor
 *   ?object=post&include=4,8      (labels of already selected items)
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

final class ObjectsController extends Controller {

	private const LIMIT = 20;

	public function register_routes(): void {
		$this->route( '/objects', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'search' ],
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			'args'                => [
				'object'    => [ 'type' => 'string', 'enum' => [ 'post', 'term', 'user' ], 'default' => 'post' ],
				'search'    => [ 'type' => 'string', 'default' => '' ],
				'include'   => [ 'type' => 'string', 'default' => '' ],
				'post_type' => [ 'type' => 'string', 'default' => '' ],
				'taxonomy'  => [ 'type' => 'string', 'default' => '' ],
				'role'      => [ 'type' => 'string', 'default' => '' ],
			],
		] );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function search( WP_REST_Request $request ) {
		$search  = sanitize_text_field( (string) $request['search'] );
		$include = array_values( array_filter( array_map( 'absint', explode( ',', (string) $request['include'] ) ) ) );

		switch ( $request['object'] ) {
			case 'term':
				$items = $this->terms( $search, $include, $this->list( $request['taxonomy'] ) );
				break;
			case 'user':
				if ( ! current_user_can( 'list_users' ) ) {
					return $this->error( 'forbidden', __( 'You are not allowed to list users.', 'libreblocks-core' ), 403 );
				}
				$items = $this->users( $search, $include, $this->list( $request['role'] ) );
				break;
			default:
				$items = $this->posts( $search, $include, $this->list( $request['post_type'] ) );
		}

		return new WP_REST_Response( $items );
	}

	/**
	 * @param int[]    $include
	 * @param string[] $types
	 * @return array<int, array{id: int, label: string}>
	 */
	private function posts( string $search, array $include, array $types ): array {
		$public = get_post_types( [ 'show_ui' => true ] );
		$types  = $types ? array_values( array_intersect( $types, $public ) ) : array_values( array_diff( $public, [ 'attachment', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation' ] ) );

		$query = new \WP_Query( [
			'post_type'              => $types ?: 'post',
			'post_status'            => [ 'publish', 'private', 'draft', 'future', 'pending' ],
			's'                      => $include ? '' : $search,
			'post__in'               => $include,
			'orderby'                => $include ? 'post__in' : 'relevance',
			'posts_per_page'         => $include ? count( $include ) : self::LIMIT,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );

		$items = [];

		foreach ( $query->posts as $post ) {
			if ( current_user_can( 'read_post', $post->ID ) ) {
				$items[] = [ 'id' => (int) $post->ID, 'label' => get_the_title( $post ) ?: sprintf( '#%d', $post->ID ) ];
			}
		}

		return $items;
	}

	/**
	 * @param int[]    $include
	 * @param string[] $taxonomies
	 * @return array<int, array{id: int, label: string}>
	 */
	private function terms( string $search, array $include, array $taxonomies ): array {
		$terms = get_terms( [
			'taxonomy'   => $taxonomies ?: get_taxonomies( [ 'show_ui' => true ] ),
			'hide_empty' => false,
			'search'     => $include ? '' : $search,
			'include'    => $include,
			'number'     => $include ? 0 : self::LIMIT,
		] );

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		return array_map( fn( \WP_Term $term ) => [ 'id' => (int) $term->term_id, 'label' => $term->name ], array_values( $terms ) );
	}

	/**
	 * @param int[]    $include
	 * @param string[] $roles
	 * @return array<int, array{id: int, label: string}>
	 */
	private function users( string $search, array $include, array $roles ): array {
		$args = [
			'number'  => $include ? count( $include ) : self::LIMIT,
			'include' => $include,
			'fields'  => [ 'ID', 'display_name' ],
		];

		if ( $roles ) {
			$args['role__in'] = $roles;
		}

		if ( ! $include && '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
		}

		return array_map(
			fn( $user ) => [ 'id' => (int) $user->ID, 'label' => (string) $user->display_name ],
			get_users( $args )
		);
	}

	/**
	 * @param mixed $value Comma-separated list.
	 * @return string[]
	 */
	private function list( $value ): array {
		return array_values( array_filter( array_map( 'sanitize_key', explode( ',', (string) $value ) ) ) );
	}
}
