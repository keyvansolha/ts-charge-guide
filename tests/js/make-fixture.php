<?php
/**
 * Regenerate the JS-test fixture from the real view.
 *
 * Usage: php tests/js/make-fixture.php
 *
 * The fixture is the guide markup exactly as the plugin renders it (plus the
 * plugin stylesheets inlined for the browser baseline), so the JS suite runs
 * against the markup that ships rather than a hand-written sample.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/../../' );
define( 'MINUTE_IN_SECONDS', 60 );
require __DIR__ . '/../unit/wp-shims.php';
require __DIR__ . '/../unit/wc-shims.php';
require __DIR__ . '/../../ts-charge-guide.php';

ts_cg_reset();
ts_cg_term( 500, 'پاوربانک', 'powerbank', 3 );
ts_cg_term( 501, 'شارژر', 'charger', 2 );
ts_cg_product( 901, [ 'name' => 'پاوربانک بیسوس مدل A 20000', 'cats' => [ 'powerbank' ], 'price' => 2900000.0 ] );
ts_cg_product( 902, [ 'name' => 'پاوربانک بیسوس مدل B 10000 (ناموجود)', 'cats' => [ 'powerbank' ], 'price' => 1900000.0, 'in_stock' => false ] );
ts_cg_product( 903, [ 'name' => 'پاوربانک بیسوس مدل C 30000', 'cats' => [ 'powerbank' ], 'price' => 3900000.0 ] );
ts_cg_product( 904, [ 'name' => 'شارژر دیواری 65W', 'cats' => [ 'charger' ], 'price' => 2400000.0 ] );
ts_cg_product( 999, [ 'name' => 'شارژر <script>alert(1)</script>', 'cats' => [ 'charger' ], 'price' => 100000.0 ] );
update_option( 'ts_charge_guide_settings', [ 'powerbank_term' => 500, 'charger_term' => 501, 'cards_per_kind' => 4 ] );

$catalog = ( new TSChargeGuide\CatalogAdapter( new TSChargeGuide\Settings() ) )->products();
$config  = [
	'endpoint'    => 'https://store.example/wp-json/ts-charge/v1/recommend',
	'nonce'       => 'fixture-nonce',
	'hasProducts' => true,
	'error'       => 'الان نتوانستیم نتیجه را بگیریم. یک بار دیگر امتحان کن؛ انتخاب‌هایت باقی می‌ماند.',
];
$view = new TSChargeGuide\GuideView( $config, $catalog );
$html = $view->render();

$target = __DIR__ . '/fixtures/guide-markup.html';
if ( ! is_dir( dirname( $target ) ) ) {
	mkdir( dirname( $target ), 0777, true );
}
file_put_contents( $target, $html );
echo 'wrote ' . $target . ' (' . strlen( $html ) . " bytes)\n";
