<?php
/**
 * Unit-test bootstrap: loads the core classes with lightweight stand-ins for
 * the WordPress functions they use, so logic can be tested without a database.
 * Integration tests that need a real WordPress run under wp-env instead.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'LIBREBLOCKS_CORE_VERSION', '0.1.0' );
define( 'LIBREBLOCKS_CORE_DIR', dirname( __DIR__ ) . '/' );

require dirname( __DIR__ ) . '/src/Autoloader.php';
LibreBlocks\Core\Autoloader::register( 'LibreBlocks\\Core\\', dirname( __DIR__ ) . '/src/' );

/** Test state shared by the stubs below. */
final class WPStub {
	public static array $filters = [];
	public static array $meta    = [];
	public static array $options = [];
	public static array $cache   = [];
	public static array $registered = [];
	public static int $user_id   = 0;
	public static $queried       = null;

	public static function reset(): void {
		self::$filters = self::$meta = self::$options = self::$cache = self::$registered = [];
		self::$user_id = 0;
		self::$queried = null;
		$_GET          = [];
	}
}

class WP_Post {
	public $ID;
	public $post_title = '';
	public $post_password = '';
	public function __construct( array $data = [] ) {
		foreach ( $data as $key => $value ) {
			$this->$key = $value;
		}
	}
}

class WP_Term {
	public $term_id;
	public $name = '';
	public function __construct( array $data = [] ) {
		foreach ( $data as $key => $value ) {
			$this->$key = $value;
		}
	}
}

class WP_User {
	public $ID    = 0;
	public $roles = [];
	public $data;
	public function __construct( int $id = 0, array $roles = [] ) {
		$this->ID    = $id;
		$this->roles = $roles;
		$this->data  = (object) [ 'ID' => $id, 'user_pass' => 'secret', 'display_name' => 'User ' . $id ];
	}
}

function __( $text, $domain = null ) { return $text; }
function apply_filters( $hook, $value, ...$args ) {
	foreach ( WPStub::$filters[ $hook ] ?? [] as $callback ) {
		$value = $callback( $value, ...$args );
	}
	return $value;
}
function add_filter( $hook, $callback ) { WPStub::$filters[ $hook ][] = $callback; }
function sanitize_text_field( $str ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $str ) ) ); }
function sanitize_textarea_field( $str ) { return trim( strip_tags( (string) $str ) ); }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); }
function sanitize_email( $email ) { return filter_var( trim( (string) $email ), FILTER_SANITIZE_EMAIL ); }
function is_email( $email ) { return false !== filter_var( $email, FILTER_VALIDATE_EMAIL ); }
function esc_url_raw( $url ) { return preg_match( '#^https?://#i', $url ) ? $url : ''; }
function wp_kses_post( $html ) { return preg_replace( '#<script\b[^>]*>.*?</script>#is', '', (string) $html ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, (array) $args ); }
function wp_json_encode( $data, $flags = 0 ) { return json_encode( $data, $flags ); }
function wp_timezone() { return new DateTimeZone( 'America/Sao_Paulo' ); }
function wp_date( $format, $timestamp = null ) { return ( null === $timestamp ? new DateTimeImmutable( 'now', wp_timezone() ) : ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() ) )->format( $format ); }
function wp_unslash( $value ) { return $value; }
function get_option( $name, $default = false ) { return WPStub::$options[ $name ] ?? $default; }
function get_metadata( $type, $id, $key, $single ) { return WPStub::$meta[ $type ][ $id ][ $key ] ?? ''; }
function get_current_user_id() { return WPStub::$user_id; }
function wp_get_current_user() { return new WP_User( WPStub::$user_id, WPStub::$user_id ? [ 'editor' ] : [] ); }
function get_queried_object() { return WPStub::$queried; }
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	$found = isset( WPStub::$cache[ $group ] ) && array_key_exists( $key, WPStub::$cache[ $group ] );
	return $found ? WPStub::$cache[ $group ][ $key ] : false;
}
function wp_cache_set( $key, $value, $group = '', $ttl = 0 ) { WPStub::$cache[ $group ][ $key ] = $value; return true; }
