<?php
/**
 * Uninstall: remove only what this plugin created.
 *
 * Deletes the plugin's own option, its catalog transients and the cache index.
 * No product, term, page or site configuration constant is ever touched.
 *
 * @package TSChargeGuide
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$ts_charge_option = 'ts_charge_guide_settings';
$ts_charge_index  = 'ts_charge_guide_cache_index';

$ts_charge_cached = get_option( $ts_charge_index, [] );
if ( is_array( $ts_charge_cached ) ) {
	foreach ( array_keys( $ts_charge_cached ) as $ts_charge_key ) {
		if ( is_string( $ts_charge_key ) && str_starts_with( $ts_charge_key, 'ts_charge_guide_catalog_' ) ) {
			delete_transient( $ts_charge_key );
		}
	}
}
delete_option( $ts_charge_index );
delete_option( $ts_charge_option );
