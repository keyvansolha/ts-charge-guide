<?php
/**
 * Static preview: renders the guide into a standalone page that loads the
 * theme's real stylesheets by absolute path, so the layout can be measured in
 * a browser without a WordPress runtime.
 *
 * Usage: php tests/browser/preview.php
 * Writes tests/browser/out/{light,dark}.html
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/../../' );
define( 'MINUTE_IN_SECONDS', 60 );
require __DIR__ . '/../unit/wp-shims.php';
require __DIR__ . '/../unit/wc-shims.php';
require __DIR__ . '/../../ts-charge-guide.php';

$plugin_dir = realpath( __DIR__ . '/../..' );
$theme_dir  = dirname( $plugin_dir, 2 ) . '/themes/amazing';
$out_dir    = __DIR__ . '/out';

ts_cg_reset();
ts_cg_term( 500, 'پاوربانک', 'powerbank', 6 );
ts_cg_term( 501, 'شارژر', 'charger', 4 );
ts_cg_product( 901, [ 'name' => 'پاوربانک بیسوس مدل Bipow 2 Pro ظرفیت ۳۰۰۰۰', 'cats' => [ 'powerbank' ], 'price' => 4900000.0 ] );
ts_cg_product( 902, [ 'name' => 'پاوربانک بیسوس مدل Blade H1 ۲۰۰۰۰', 'cats' => [ 'powerbank' ], 'price' => 3900000.0 ] );
ts_cg_product( 903, [ 'name' => 'پاوربانک بیسوس مدل Enerfill FP21 ۱۰۰۰۰', 'cats' => [ 'powerbank' ], 'price' => 2900000.0, 'in_stock' => false ] );
ts_cg_product( 904, [ 'name' => 'شارژر دیواری بیسوس مدل Cube Pro 65W', 'cats' => [ 'charger' ], 'price' => 2400000.0 ] );
ts_cg_product( 905, [ 'name' => 'شارژر دیواری بیسوس مدل EnerFill FE11 33W', 'cats' => [ 'charger' ], 'price' => 1400000.0 ] );
$GLOBALS['ts_cg_brands'] = [ 901 => 'Baseus', 902 => 'Baseus', 903 => 'Baseus', 904 => 'Baseus', 905 => 'Baseus' ];
update_option( 'ts_charge_guide_settings', [ 'powerbank_term' => 500, 'charger_term' => 501, 'cards_per_kind' => 4 ] );

$catalog = ( new TSChargeGuide\CatalogAdapter( new TSChargeGuide\Settings() ) )->products();
$config  = [
	'endpoint'    => 'https://store.example/wp-json/ts-charge/v1/recommend',
	'nonce'       => 'preview',
	'hasProducts' => true,
	'error'       => 'الان نتوانستیم نتیجه را بگیریم. یک بار دیگر امتحان کن؛ انتخاب‌هایت باقی می‌ماند.',
];
$markup = ( new TSChargeGuide\GuideView( $config, $catalog ) )->render();

// The preview is a file:// page, so the URLs the shims hand out are rewritten
// to paths that resolve on disk — otherwise every image would 404 and the
// layout would be measured without its pictures.
$markup = str_replace(
	'https://store.example/wp-content/plugins/ts-charge-guide/',
	'../../../',
	$markup
);
$markup = preg_replace_callback(
	'#https://store\.example/img/(\d+)\.webp#',
	static fn( array $m ): string => '../../../assets/images/' . ( 0 === (int) $m[1] % 2 ? 'life-mobile.webp' : 'life-home.webp' ),
	$markup
);

if ( ! is_dir( $out_dir ) ) {
	mkdir( $out_dir, 0777, true );
}

/**
 * Render one preview page.
 *
 * @param string $file      Output file name.
 * @param string $body_themed Extra body attributes (null for light).
 * @param string $plugin_dir Plugin directory.
 * @param string $theme_dir  Theme directory.
 * @param string $markup     Guide markup.
 */
function write_preview( string $file, string $body_themed, string $plugin_dir, string $theme_dir, string $markup ): void {
	$css = [
		$theme_dir . '/style.css',
		$theme_dir . '/assets/fonts/icons/style.css',
		$theme_dir . '/assets/css/theme-system.css',
		$plugin_dir . '/assets/token-bridge.css',
		$plugin_dir . '/assets/style.css',
	];
	$links = '';
	foreach ( $css as $path ) {
		if ( is_file( $path ) ) {
			$links .= '<link rel="stylesheet" href="file://' . $path . '">' . "\n";
		}
	}
	$html = '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">'
		. '<meta name="viewport" content="width=device-width,initial-scale=1">'
		. '<title>Charge guide preview (' . $file . ')</title>' . "\n" . $links
		. '</head><body ' . $body_themed . '>'
		. '<main id="site-main" class="ts-charge-page">' . $markup . '</main>'
		. '</body></html>';
	file_put_contents( __DIR__ . '/out/' . $file, $html );
	echo 'wrote tests/browser/out/' . $file . "\n";
}

write_preview( 'light.html', '', $plugin_dir, $theme_dir, $markup );
write_preview( 'dark.html', 'class="dark" data-theme="dark"', $plugin_dir, $theme_dir, $markup );
