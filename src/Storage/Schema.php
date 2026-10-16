<?php
/**
 * Versioned custom tables. Products declare their tables here and the core
 * creates or upgrades them through dbDelta when the declared version changes.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Storage;

defined( 'ABSPATH' ) || exit;

final class Schema {

	public const OPTION = 'lbcore_schema_versions';

	/** @var array<string, array{columns: string, version: string, owner: string}> */
	private array $tables = [];

	public function hook(): void {
		add_action( 'admin_init', [ $this, 'maybe_upgrade' ] );
	}

	/**
	 * Declares a table.
	 *
	 * The column definition follows dbDelta rules: one column or key per line,
	 * two spaces after "PRIMARY KEY", and every key must have a name.
	 *
	 * @param string $key     Unprefixed table name, e.g. "lbforge_definitions".
	 * @param string $columns Column and key definitions (the part between the parentheses).
	 * @param string $version Bump it whenever the definition changes.
	 * @param string $owner   Product slug, used for diagnostics.
	 */
	public function register_table( string $key, string $columns, string $version, string $owner = 'core' ): void {
		$this->tables[ $key ] = [
			'columns' => trim( $columns ),
			'version' => $version,
			'owner'   => $owner,
		];
	}

	public function table( string $key ): string {
		global $wpdb;

		return $wpdb->prefix . $key;
	}

	/**
	 * @return string[] Keys of tables whose stored version differs from the declared one.
	 */
	public function pending(): array {
		$installed = (array) get_option( self::OPTION, [] );
		$pending   = [];

		foreach ( $this->tables as $key => $table ) {
			if ( ( $installed[ $key ] ?? null ) !== $table['version'] ) {
				$pending[] = $key;
			}
		}

		return $pending;
	}

	public function maybe_upgrade(): void {
		if ( $this->pending() ) {
			$this->install();
		}
	}

	/**
	 * Creates/updates every pending table. Safe to call repeatedly.
	 */
	public function install(): void {
		global $wpdb;

		$pending = $this->pending();

		if ( ! $pending ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$installed = (array) get_option( self::OPTION, [] );
		$charset   = $wpdb->get_charset_collate();

		foreach ( $pending as $key ) {
			$table = $this->tables[ $key ];

			dbDelta( sprintf( "CREATE TABLE %s (\n%s\n) %s;", $this->table( $key ), $table['columns'], $charset ) );

			$installed[ $key ] = $table['version'];
		}

		update_option( self::OPTION, $installed, false );
	}

	/**
	 * Drops a table. Meant for a product's uninstall routine only.
	 */
	public function drop( string $key ): void {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . esc_sql( $this->table( $key ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$installed = (array) get_option( self::OPTION, [] );
		unset( $installed[ $key ] );
		update_option( self::OPTION, $installed, false );
	}

	/**
	 * @return array<string, array{columns: string, version: string, owner: string}>
	 */
	public function tables(): array {
		return $this->tables;
	}
}
