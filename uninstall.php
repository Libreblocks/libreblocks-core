<?php
/**
 * Removes core-owned data. Product tables are removed by each product's own uninstall.
 *
 * @package LibreBlocks\Core
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'lbcore_schema_versions' );
