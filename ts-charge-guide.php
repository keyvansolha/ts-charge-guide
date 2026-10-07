<?php
/**
 * Plugin Name: TehranSpeaker Charge Guide
 * Description: راهنمای انتخاب پاوربانک و شارژر و مراقبت از باتری، بر پایه محصولات زنده ووکامرس و توکن‌های قالب.
 * Version: 0.7.2
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Text Domain: ts-charge-guide
 * Author: Parsa Dana, Keyvan Havestin
 *
 * Plugin bootstrap and composition root.
 *
 * Only environment checks, class loading, service construction, and hook
 * registration happen here. The guide copy lives in Content, the catalog in
 * CatalogAdapter, and the markup in GuideView.
 *
 * @package TSChargeGuide
 */

defined( 'ABSPATH' ) || exit;

define( 'TS_CHARGE_GUIDE_VERSION', '0.7.2' );
define( 'TS_CHARGE_GUIDE_FILE', __FILE__ );
define( 'TS_CHARGE_GUIDE_DIR', plugin_dir_path( __FILE__ ) );
define( 'TS_CHARGE_GUIDE_URL', plugin_dir_url( __FILE__ ) );
define( 'TS_CHARGE_GUIDE_OPTION', 'ts_charge_guide_settings' );
define( 'TS_CHARGE_GUIDE_REST_BASE', 'ts-charge/v1' );
define( 'TS_CHARGE_GUIDE_CACHE_PREFIX', 'ts_charge_guide_catalog_' );
define( 'TS_CHARGE_GUIDE_TRANSIENT_EXPIRY', 15 * MINUTE_IN_SECONDS );

/**
 * Minimal PSR-4 class autoloader for the TSChargeGuide namespace.
 *
 * @param string $class Fully qualified class name.
 */
function ts_charge_guide_autoload( string $class ): void {
	if ( ! str_starts_with( $class, 'TSChargeGuide\\' ) ) {
		return;
	}
	$relative = substr( $class, strlen( 'TSChargeGuide\\' ) );
	$file     = __DIR__ . '/includes/src/' . str_replace( '\\', '/', $relative ) . '.php';
	if ( is_file( $file ) ) {
		require_once $file;
	}
}
spl_autoload_register( 'ts_charge_guide_autoload' );

register_activation_hook( __FILE__, static function (): void {
	// Activation writes nothing: no pages, no products, no options row beyond
	// defaults written on first read (get_option default path).
	ts_charge_guide_environment_ready();
} );

/**
 * Whether the runtime supports the plugin (PHP and WooCommerce presence).
 *
 * @return bool True when WooCommerce is active with a compatible runtime.
 */
function ts_charge_guide_environment_ready(): bool {
	$php_ok = version_compare( PHP_VERSION, '8.0', '>=' );
	$wc_ok  = class_exists( 'WooCommerce' ) || ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) );
	return $php_ok && $wc_ok;
}

/**
 * Compose and register services.
 *
 * @return TSChargeGuide\App The application container.
 */
function ts_charge_guide(): TSChargeGuide\App {
	static $app = null;
	if ( null === $app ) {
		$app = new TSChargeGuide\App();
		$app->register();
	}
	return $app;
}

add_action( 'plugins_loaded', 'ts_charge_guide', 5 );
