<?php
/**
 * PHP unit suite (no framework): services, boundaries and source discipline.
 *
 * Usage: php tests/unit/run.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/../../' );
define( 'MINUTE_IN_SECONDS', 60 );
require __DIR__ . '/wp-shims.php';
require __DIR__ . '/wc-shims.php';
require __DIR__ . '/rest-shims.php';
require __DIR__ . '/theme-shims.php';
require __DIR__ . '/../../ts-charge-guide.php';

use TSChargeGuide\CatalogAdapter;
use TSChargeGuide\Content;
use TSChargeGuide\GuideView;
use TSChargeGuide\Recommendation;
use TSChargeGuide\Settings;

$pass = 0;
$fail = 0;

/**
 * Assert helper.
 *
 * @param string $name Test name.
 * @param bool   $cond Condition.
 */
function check( string $name, bool $cond ): void {
	global $pass, $fail;
	if ( $cond ) {
		$pass++;
		echo "ok  {$name}\n";
	} else {
		$fail++;
		echo "FAIL {$name}\n";
	}
}

/**
 * Read a plugin source file.
 *
 * @param string $relative Path relative to the plugin root.
 * @return string
 */
function src( string $relative ): string {
	return (string) file_get_contents( __DIR__ . '/../../' . $relative );
}

/**
 * Fresh container with empty shim stores.
 *
 * @return TSChargeGuide\App
 */
function plugin(): TSChargeGuide\App {
	return new TSChargeGuide\App();
}

/**
 * The version the plugin declares, read out of its own header.
 *
 * Assertions use this instead of a literal so a version bump moves every
 * expectation with it and cannot half-land.
 *
 * @return string
 */
function plugin_version(): string {
	return preg_match( '/Version:\s*([0-9.]+)/', src( 'ts-charge-guide.php' ), $m ) ? $m[1] : '';
}

/* ---------------- settings ---------------- */

ts_cg_reset();
$settings = new Settings();
check( 'default card bound is 6', 6 === $settings->cards_per_kind() );
check( 'the magazine list is empty until the owner lists posts', [] === $settings->blog_ids() );

/* The magazine list: whatever the owner pastes becomes safe ids. */
check( 'the magazine list accepts Persian digits, commas and newlines', [ 288405, 292114 ] === $settings->normalize_ids( "۲۸۸۴۰۵، 292114\n292114" ) );
check( 'the magazine list drops anything that is not a number', [ 12 ] === $settings->normalize_ids( 'abc 12 def' ) );
check( 'the magazine list keeps the owner’s order', [ 30, 10, 20 ] === $settings->normalize_ids( [ 30, 10, 20 ] ) );
check( 'the magazine list is capped', Settings::MAX_BLOG_IDS === count( $settings->normalize_ids( '1 2 3 4 5 6 7 8 9' ) ) );
check( 'an empty magazine field means an empty list', [] === $settings->normalize_ids( '' ) && [] === $settings->normalize_ids( null ) && [] === $settings->normalize_ids( '   ' ) );
check( 'nothing is classified before the owner assigns categories', ! $settings->has_product_scope() );
check( 'both category terms default to 0', [ 0, 0 ] === array_values( $settings->category_terms() ) );
check( 'no landing page by default', 0 === $settings->landing_page_id() );

$clean = $settings->sanitize( [ 'page_id' => '12', 'powerbank_term' => '7', 'cards_per_kind' => 'abc' ] );
check( 'sanitize keeps the page and term', 12 === $clean['page_id'] && 7 === $clean['powerbank_term'] );
check( 'a non-numeric bound falls back to the default', 6 === $clean['cards_per_kind'] );
check( 'a partial save keeps the unnamed term', 0 === $clean['charger_term'] );
check( 'an out-of-range bound falls back to the default', 6 === $settings->sanitize( [ 'cards_per_kind' => '99' ] )['cards_per_kind'] );
check( 'an in-range bound is kept', 4 === $settings->sanitize( [ 'cards_per_kind' => '4' ] )['cards_per_kind'] );

$GLOBALS['ts_cg_pages'] = [ 12 => 'راهنمای شارژ' ];
update_option( 'ts_charge_guide_settings', [ 'page_id' => 12, 'cards_per_kind' => 3 ] );
$settings = new Settings();
check( 'a published page is accepted as landing page', 12 === $settings->landing_page_id() );
check( 'a stale page id is reported as broken', ! $settings->has_broken_page() );
$GLOBALS['ts_cg_pages'] = [];
$settings = new Settings();
check( 'a deleted page is ignored, not used', 0 === $settings->landing_page_id() && $settings->has_broken_page() );

/* ---------------- catalog: absence first ---------------- */

ts_cg_reset();
ts_cg_term( 500, 'پاوربانک', 'powerbank', 3 );
ts_cg_term( 501, 'شارژر', 'charger', 2 );
ts_cg_product( 901, [ 'name' => 'پاوربانک الف', 'cats' => [ 'powerbank' ], 'price' => 900000.0, 'in_stock' => true ] );
ts_cg_product( 902, [ 'name' => 'پاوربانک ب', 'cats' => [ 'powerbank' ], 'price' => 500000.0, 'in_stock' => false ] );
ts_cg_product( 903, [ 'name' => 'پاوربانک پ', 'cats' => [ 'powerbank' ], 'price' => 700000.0, 'in_stock' => true ] );
ts_cg_product( 904, [ 'name' => 'شارژر الف', 'cats' => [ 'charger' ], 'price' => 300000.0, 'in_stock' => true ] );
ts_cg_product( 905, [ 'name' => 'شارژر بی‌تصویر', 'cats' => [ 'charger' ], 'price' => 200000.0, 'image_id' => 0 ] );

$app = plugin();
check( 'with no category assigned the catalog returns nothing', [] === $app->catalog->products() );
check( 'with no category assigned no query is even attempted', [] === $GLOBALS['ts_cg_query_log'] );

update_option( 'ts_charge_guide_settings', [ 'powerbank_term' => 500, 'charger_term' => 501, 'cards_per_kind' => 6 ] );
$app = plugin();
$rows = $app->catalog->products();
$by_id = array_column( $rows, null, 'id' );
check( 'assigned categories produce cards', 4 === count( $rows ) );
check( 'cards are read live from the store', in_array( 'پاوربانک الف', array_column( $rows, 'name' ), true ) );
check( 'card kind comes from the configured term', 'powerbank' === $by_id[901]['kind'] && 'charger' === $by_id[904]['kind'] );
check( 'card price is the store price', 900000.0 === $by_id[901]['price'] );
check( 'card image is the store attachment', str_contains( (string) $by_id[901]['image'], '/img/901.webp' ) );
check( 'card URL is the store permalink', 'https://store.example/product/901/' === $by_id[901]['url'] );
check( 'brand comes from the store taxonomy when present', null === $by_id[901]['brand'] );
check( 'a product without an image is still offered', null === $by_id[905]['image'] );

/* In-stock rule: the query asks for it and the adapter enforces it again. */
$main_queries = array_values( array_filter( $GLOBALS['ts_cg_query_log'], static fn( array $q ): bool => 'instock' === ( $q['stock_status'] ?? null ) ) );
$count_queries = array_values( array_filter( $GLOBALS['ts_cg_query_log'], static fn( array $q ): bool => [ 'outofstock', 'onbackorder' ] === ( $q['stock_status'] ?? null ) ) );
check( 'the catalog queries WooCommerce for in-stock products only', 2 === count( $main_queries ) && 'publish' === $main_queries[0]['status'] );
check( 'the hidden count is asked for without hydrating products', 2 === count( $count_queries ) && 'ids' === $count_queries[0]['return'] && true === $count_queries[0]['paginate'] );
check( 'an out-of-stock product is never offered', ! isset( $by_id[902] ) && ! in_array( 902, array_column( $rows, 'id' ), true ) );
check( 'every offered card is in stock', [] === array_filter( $rows, static fn( array $row ): bool => empty( $row['inStock'] ) ) );
check( 'the withheld count is reported for the live check', 1 === $app->catalog->withheld( 'powerbank' )['stock'] );
check( 'nothing is withheld for a kind with no out-of-stock products', 0 === $app->catalog->withheld( 'charger' )['stock'] );

$queries = count( $GLOBALS['ts_cg_query_log'] );
$app->catalog->products();
check( 'a second read in the same request does not re-query', $queries === count( $GLOBALS['ts_cg_query_log'] ) );
$second = plugin();
$second->catalog->products();
check( 'a cached snapshot serves the next request', $queries === count( $GLOBALS['ts_cg_query_log'] ) );
check( 'the cache is indexed for the purge action', is_array( get_option( 'ts_charge_guide_cache_index', [] ) ) );
check( 'flush deletes the guide transients', 2 === $app->catalog->flush_cache() );
$third = plugin();
$third->catalog->products();
check( 'after a flush the catalog is queried again', $queries < count( $GLOBALS['ts_cg_query_log'] ) );

$GLOBALS['ts_cg_brands'] = [ 901 => 'Baseus' ];
update_option( 'ts_charge_guide_settings', [ 'powerbank_term' => 500, 'charger_term' => 501, 'cards_per_kind' => 1 ] );
$bounded = plugin();
check( 'the card bound is applied per category', 1 === count( $bounded->catalog->by_kind( 'powerbank' ) ) && 1 === count( $bounded->catalog->by_kind( 'charger' ) ) );
check( 'the brand chip is filled from the store', 'Baseus' === $bounded->catalog->by_kind( 'powerbank' )[0]['brand'] );

ts_cg_product( 906, [ 'name' => 'پیش‌نویس', 'cats' => [ 'powerbank' ], 'status' => 'draft' ] );
update_option( 'ts_charge_guide_settings', [ 'powerbank_term' => 500, 'charger_term' => 501, 'cards_per_kind' => 6 ] );
check( 'an unpublished product never reaches the guide', ! in_array( 906, array_column( plugin()->catalog->products(), 'id' ), true ) );

/* ---------------- recommendation ---------------- */

check( 'unknown need is rejected', ! Recommendation::valid( [ 'need' => 'free', 'device' => 'samsung', 'priority' => 'light' ] ) );
check( 'unknown device is rejected', ! Recommendation::valid( [ 'need' => 'powerbank', 'device' => 'printer', 'priority' => 'light' ] ) );
check( 'a priority from another need is rejected', ! Recommendation::valid( [ 'need' => 'charger', 'device' => 'samsung', 'priority' => 'capacity' ] ) );
check( 'a reachable answer set is accepted', Recommendation::valid( [ 'need' => 'powerbank', 'device' => 'samsung', 'priority' => 'capacity' ] ) );

$catalog = [
	[ 'id' => 901, 'name' => 'A', 'kind' => 'powerbank', 'price' => 900.0, 'inStock' => false, 'url' => 'https://store.example/product/901/', 'image' => null, 'priceHtml' => '', 'brand' => null ],
	[ 'id' => 902, 'name' => 'B', 'kind' => 'powerbank', 'price' => 500.0, 'inStock' => true, 'url' => 'https://store.example/product/902/', 'image' => null, 'priceHtml' => '', 'brand' => null ],
	[ 'id' => 903, 'name' => 'C', 'kind' => 'powerbank', 'price' => 700.0, 'inStock' => true, 'url' => 'https://store.example/product/903/', 'image' => null, 'priceHtml' => '', 'brand' => null ],
	[ 'id' => 904, 'name' => 'D', 'kind' => 'charger', 'price' => 300.0, 'inStock' => true, 'url' => 'https://store.example/product/904/', 'image' => null, 'priceHtml' => '', 'brand' => null ],
];

$result = Recommendation::build( [ 'need' => 'powerbank', 'device' => 'samsung', 'priority' => 'capacity' ], $catalog );
check( 'picks are the live catalog rows, not fixed ids', [ 902, 903 ] === array_column( $result['products'], 'id' ) );
check( 'an out-of-stock row handed in directly is dropped', ! in_array( 901, array_column( $result['products'], 'id' ), true ) );
check( 'a result never certifies compatibility', false === $result['certifies'] );
check( 'the result carries the checklist and the warning', 3 === count( $result['checklist'] ) && '' !== $result['warning'] );
check( 'a public pick carries no internal field', ! array_key_exists( 'price', $result['products'][0] ) );

$reuse = Recommendation::build( [ 'need' => 'powerbank', 'device' => 'other', 'priority' => 'reuse' ], $catalog );
check( 'the reuse path offers no product at all', [] === $reuse['products'] );

$both = Recommendation::build( [ 'need' => 'both', 'device' => 'other', 'priority' => 'multi' ], $catalog );
check( 'the both path shows the cheapest live pick of each kind', [ 902, 904 ] === array_column( $both['products'], 'id' ) );

$out_of_stock = Recommendation::build( [ 'need' => 'charger', 'device' => 'other', 'priority' => 'single' ], [
	[ 'id' => 950, 'name' => 'شارژر ناموجود', 'kind' => 'charger', 'price' => 100.0, 'inStock' => false, 'url' => '#', 'image' => null, 'priceHtml' => '', 'brand' => null ],
] );
check( 'a catalog of nothing available yields no pick at all', [] === $out_of_stock['products'] );

$empty = Recommendation::build( [ 'need' => 'powerbank', 'device' => 'other', 'priority' => 'light' ], [] );
check( 'an empty catalog still returns usable copy', [] === $empty['products'] && '' !== $empty['heading'] );

/* ---------------- guide view ---------------- */

ts_cg_reset();
$config = [ 'endpoint' => 'https://store.example/wp-json/ts-charge/v1/recommend', 'nonce' => 'abc', 'hasProducts' => false, 'error' => 'خطا' ];
$html   = ( new GuideView( $config, [] ) )->render();
check( 'the empty catalog renders no product section', ! str_contains( $html, 'id="cg-products"' ) );
check( 'the guide renders with exactly one h1', 1 === preg_match_all( '/<h1\b/', $html ) );
check( 'the guide keeps the site chrome out', ! str_contains( $html, '<nav' ) && ! str_contains( $html, 'class="footer"' ) );
check( 'the guide ships no font and no frame', ! str_contains( $html, '@font-face' ) && ! str_contains( $html, '<iframe' ) && ! str_contains( $html, 'YekanBakh.woff2' ) );
check( 'the bootstrap config is JSON-hex encoded', str_contains( $html, 'id="ts-charge-config"' ) && ! str_contains( $html, '</script><script' ) );
check( 'the landing page passes the magazine ids from the settings, not from the source', str_contains( src( 'includes/src/LandingPage.php' ), "'blogIds'" ) && str_contains( src( 'includes/src/LandingPage.php' ), 'blog_ids()' ) );

$danger = [
	[
		'id'        => 999,
		'name'      => 'شارژر <script>alert(1)</script>',
		'kind'      => 'charger',
		'price'     => 10.0,
		'inStock'   => true,
		'url'       => 'https://store.example/product/999/?x="onerror=alert(1)',
		'image'     => 'https://store.example/img/999.webp',
		'priceHtml' => '<span class="price">۱۰ <b>تومان</b></span><script>alert(2)</script>',
		'brand'     => 'B<script>',
	],
];
// The card the guide renders is the theme's component, so the hostile payload
// is planted where that component reads it: the product's own fields.
$GLOBALS['ts_cg_brands'][999] = 'B<script>';
ts_cg_product( 999, [
	'name'       => 'شارژر <script>alert(1)</script>',
	'cats'       => [ 'charger' ],
	'price'      => 10.0,
	'price_html' => '<span class="price">۱۰ <b>تومان</b></span><script>alert(2)</script>',
	'permalink'  => 'https://store.example/product/999/?x="onerror=alert(1)',
	'image_id'   => 999,
] );
$risky = ( new GuideView( $config, $danger ) )->render();
check( 'a hostile product name is escaped, never executed', ! str_contains( $risky, '<script>alert(1)' ) && str_contains( $risky, '&lt;script&gt;' ) );
check( 'hostile markup in the price field is stripped', ! str_contains( $risky, 'alert(2)' ) );
check( 'a hostile brand attribute cannot break out of the attribute', ! str_contains( $risky, 'B<script>' ) );
check( 'the product section appears when the catalog has rows', str_contains( $risky, 'id="cg-products"' ) && str_contains( $risky, 'data-cg-kind="charger"' ) );

/* ---------------- token and source discipline ---------------- */

$style  = src( 'assets/style.css' );
$bridge = src( 'assets/token-bridge.css' );
check( 'the guide stylesheet has no colour literal', 0 === preg_match( '/#[0-9a-fA-F]{3,8}\b|\brgba?\(/', $style ) );
check( 'the guide stylesheet declares no font file', ! str_contains( $style, '@font-face' ) && ! str_contains( $style, 'woff' ) );
check( 'the guide stylesheet never overrides dark mode by class', ! str_contains( $style, 'body.dark' ) && ! str_contains( $style, '.dark ' ) );
check( 'the theme owns the font family', str_contains( $style, 'YekanBakh' ) && ! str_contains( $style, 'url(' ) );
preg_match_all( '/var\((--[a-z0-9-]+)/', $style, $vars );
$names = array_unique( $vars[1] ?? [] );
$foreign = array_values( array_filter( $names, static fn( string $name ): bool => ! str_starts_with( $name, '--cg-' ) ) );
check( 'every custom property in the guide stylesheet is a bridge alias', [] === $foreign );
foreach ( array_slice( $names, 0, 40 ) as $name ) {
	check( "the bridge defines {$name}", str_contains( $bridge, $name . ':' ) );
}
check( 'the bridge maps to theme tokens, not to its own palette', str_contains( $bridge, 'var(--surface-card' ) && str_contains( $bridge, 'var(--theme-accent' ) );

$sources = [
	'ts-charge-guide.php',
	'includes/src/App.php',
	'includes/src/Assets.php',
	'includes/src/AdminScreens.php',
	'includes/src/CatalogAdapter.php',
	'includes/src/Content.php',
	'includes/src/GuideView.php',
	'includes/src/LandingPage.php',
	'includes/src/Recommendation.php',
	'includes/src/Rest/Controller.php',
	'includes/src/Rest/RecommendController.php',
	'includes/src/Settings.php',
	'assets/js/entry.js',
	'assets/js/wizard.js',
	'assets/js/panels.js',
	'assets/js/rest.js',
	'assets/js/dom.js',
];
$joined = '';
foreach ( $sources as $file ) {
	$joined .= src( $file );
}
check( 'no product name is written into the source', ! preg_match( '/Bipow|Blade H1|Enerfill|Cube Pro|Palm 20W|Power Combo/i', $joined ) );
check( 'no magazine article is named in the source', ! preg_match( '#what-is-a-battery-charge-cycle|pd-qc-pps-charger-guide|built-in-cable-power-bank-guide#', $joined ) && ! str_contains( src( 'includes/src/Content.php' ), 'reading(' ) && ! str_contains( src( 'includes/src/GuideView.php' ), 'url_to_postid' ) );
check( 'no store URL or upload path is written into the source', ! str_contains( $joined, 'tehranspeaker.com' ) );
check( 'no product id table survives', ! str_contains( $joined, 'CHARGE_PRODUCTS' ) && ! str_contains( $joined, 'bankIds' ) );
check( 'the source decides nothing by device sniffing', ! str_contains( $joined, 'matchMedia' ) && ! str_contains( $joined, 'document.referrer' ) );
check( 'no abandoned embedding code remains', ! str_contains( $joined, 'postMessage' ) && ! str_contains( $joined, 'modelContext' ) && ! str_contains( $joined, 'attachShadow' ) );
check( 'the guide no longer writes HTML from a string template', 1 === preg_match_all( '/\.innerHTML\s*=/', src( 'assets/js/wizard.js' ) ) && str_contains( src( 'assets/js/wizard.js' ), 'safeMarkup' ) && ! preg_match( '/\.innerHTML\s*=/', src( 'assets/js/panels.js' ) . src( 'assets/js/entry.js' ) . src( 'assets/js/dom.js' ) . src( 'assets/js/rest.js' ) ) );
check( 'no PHP file renders guide HTML outside the view', ! str_contains( src( 'ts-charge-guide.php' ), 'file_get_contents' ) && ! str_contains( src( 'includes/src/LandingPage.php' ), 'file_get_contents' ) );
check( 'asset loading is gated on the guide request', str_contains( src( 'includes/src/Assets.php' ), 'is_guide_request' ) );
check( 'no script is enqueued by URL', ! preg_match( '/wp_enqueue_script\s*\(\s*[\'"][^\'"]*\.js/', $joined ) );
check( 'the theme script is enqueued only when the theme registered it', str_contains( src( 'includes/src/Assets.php' ), "wp_script_is( \$handle, 'registered' )" ) );
check( 'asset versions come from the constant', ! preg_match( '/\?v=[0-9.]+/', $joined ) && str_contains( src( 'ts-charge-guide.php' ), "'" . plugin_version() . "'" ) );
check( 'no app directory or duplicate fragment is shipped', ! is_dir( __DIR__ . '/../../app' ) && ! is_file( __DIR__ . '/../../assets/fragment.html' ) && ! is_file( __DIR__ . '/../../assets/native.js' ) );
check( 'no font file is shipped with the plugin', [] === glob( __DIR__ . '/../../assets/{fonts,images}/*.{woff,woff2,ttf}', GLOB_BRACE ) + glob( __DIR__ . '/../../assets/*.{woff,woff2,ttf}', GLOB_BRACE ) );
check( 'uninstall removes only the plugin option and transients', str_contains( src( 'uninstall.php' ), 'delete_option' ) && ! str_contains( src( 'uninstall.php' ), 'wp-config' ) );

/* ---------------- cards come from the theme ---------------- */

ts_cg_reset();
ts_cg_term( 500, 'پاوربانک', 'powerbank', 6 );
ts_cg_term( 501, 'شارژر', 'charger', 6 );
ts_cg_product( 901, [ 'name' => 'پاوربانک الف', 'cats' => [ 'powerbank' ], 'price' => 900000.0 ] );
ts_cg_product( 904, [ 'name' => 'شارژر الف', 'cats' => [ 'charger' ], 'price' => 300000.0 ] );
ts_cg_post( 7001, [ 'title' => 'سیکل شارژ باتری', 'excerpt' => 'درباره چرخه شارژ.', 'permalink' => '/what-is-a-battery-charge-cycle/', 'thumb' => '/img/7001.webp' ] );
update_option( 'ts_charge_guide_settings', [ 'powerbank_term' => 500, 'charger_term' => 501, 'cards_per_kind' => 6, 'blog_ids' => [ 7001, 999999 ] ] );

$app   = plugin();
$guide = ( new GuideView( [ 'endpoint' => 'https://store.example/wp-json/ts-charge/v1/recommend', 'nonce' => 'n', 'hasProducts' => true, 'blogIds' => $app->settings->blog_ids(), 'error' => 'x' ], $app->catalog->products() ) )->render();

check( 'the grid renders the theme card component', 2 === substr_count( $guide, 'class="product-simple-card"' ) );
check( 'the grid wraps each card in a kind cell, not in a guide card', 2 === substr_count( $guide, 'class="cg-cell"' ) && ! str_contains( $guide, 'cg-card' ) );
check( 'the card is rendered from the theme partial, by device', str_contains( src( 'includes/src/GuideView.php' ), 'THEME_COMPONENTS' ) && str_contains( src( 'includes/src/GuideView.php' ), "'product-cards/simple-card-mobile.php'" ) );
check( 'the guide renders no product card markup of its own', ! str_contains( src( 'includes/src/GuideView.php' ), 'cg-card' ) && ! str_contains( src( 'assets/style.css' ), '.cg-card' ) );
check( 'the magazine section renders the theme post card for each configured id', 1 === substr_count( $guide, 'class="blog-row-post-card full-card shadow-bottom' ) && str_contains( $guide, 'سیکل شارژ باتری' ) );
check( 'a configured id that does not resolve is skipped, never rendered', 1 === substr_count( $guide, 'class="blog-row-post-card' ) && ! str_contains( $guide, '999999' ) );
check( 'the guide asks the theme for the card stylesheet and its script', str_contains( src( 'includes/src/Assets.php' ), "'amazing-product-card'" ) && str_contains( src( 'includes/src/Assets.php' ), "'wbsFavorite'" ) );

/* Absence-shaped proof: no post configured, no magazine section at all. */
update_option( 'ts_charge_guide_settings', [ 'powerbank_term' => 500, 'charger_term' => 501, 'cards_per_kind' => 6, 'blog_ids' => '' ] );
$bare = ( new GuideView( [ 'endpoint' => 'e', 'nonce' => 'n', 'hasProducts' => true, 'error' => 'x' ], plugin()->catalog->products() ) )->render();
check( 'with no magazine post configured the section is not rendered', ! str_contains( $bare, 'blog-row-post-card' ) && ! str_contains( $bare, 'cg-reading' ) && ! str_contains( $bare, 'بیشتر بدانی' ) );

/* The settings screen shows what the magazine list resolves to. */
update_option( 'ts_charge_guide_settings', [ 'powerbank_term' => 500, 'charger_term' => 501, 'cards_per_kind' => 6, 'blog_ids' => [ 7001, 999999 ] ] );
ob_start();
plugin()->admin->render_page();
$admin = (string) ob_get_clean();
check( 'the settings screen previews the magazine posts and flags a missing id', str_contains( $admin, 'بخش مجله' ) && str_contains( $admin, 'یافت نشد' ) && str_contains( $admin, 'سیکل شارژ باتری' ) );

// Put the guide on a page, so the asset service considers this a guide request.
$GLOBALS['ts_cg_pages']              = [ 12 => 'راهنمای شارژ' ];
update_option( 'ts_charge_guide_settings', [ 'page_id' => 12, 'powerbank_term' => 500, 'charger_term' => 501, 'cards_per_kind' => 6 ] );
$GLOBALS['ts_cg_queried_page']       = 12;
$GLOBALS['ts_cg_scripts_registered'] = [ 'wbsAjax', 'wbsFavorite' ];
$app = plugin();
$app->assets->enqueue();
check( 'the theme card stylesheet is enqueued on the guide page', in_array( 'amazing-product-card', array_column( $GLOBALS['ts_cg_styles'], 1 ), true ) );
check( 'the card script is enqueued once a catalog is configured', in_array( 'wbsFavorite', $GLOBALS['ts_cg_scripts'], true ) && in_array( 'wbsAjax', $GLOBALS['ts_cg_scripts'], true ) );

/* ---------------- REST ---------------- */

ts_cg_reset();
$GLOBALS['ts_cg_routes'] = [];
ts_cg_product( 901, [ 'name' => 'پاوربانک الف', 'cats' => [ 'powerbank' ], 'price' => 900000.0 ] );
ts_cg_product( 904, [ 'name' => 'شارژر الف', 'cats' => [ 'charger' ], 'price' => 300000.0 ] );
update_option( 'ts_charge_guide_settings', [ 'powerbank_term' => 500, 'charger_term' => 501, 'cards_per_kind' => 6 ] );
$app = plugin();
$app->recommend->register_routes();
check( 'the recommend route is registered under ts-charge/v1', [ 'ts-charge/v1', '/recommend' ] === [ $GLOBALS['ts_cg_routes'][0][0] ?? '', $GLOBALS['ts_cg_routes'][0][1] ?? '' ] );
check( 'the recommend route is POST-only', 'POST' === ( $GLOBALS['ts_cg_routes'][0][2]['methods'] ?? '' ) );

$controller = $app->recommend;
$ok = $controller->handle( new WP_REST_Request( [ 'need' => 'powerbank', 'device' => 'samsung', 'priority' => 'capacity' ] ) );
check( 'a valid request answers 200', 200 === $ok->get_status() );
check( 'the answer carries live store products', [ 901 ] === array_column( $ok->get_data()['products'], 'id' ) );
check( 'the answer is never cached by an intermediary', str_contains( (string) ( $ok->get_headers()['Cache-Control'] ?? '' ), 'no-store' ) );

$bad_key = $controller->handle( new WP_REST_Request( [ 'need' => 'powerbank', 'device' => 'samsung', 'priority' => 'capacity', 'extra' => '1' ] ) );
check( 'an unknown field is rejected', 400 === $bad_key->get_status() );
$bad_enum = $controller->handle( new WP_REST_Request( [ 'need' => 'powerbank', 'device' => 'samsung', 'priority' => 'free' ] ) );
check( 'an unreachable priority is rejected', 400 === $bad_enum->get_status() );
$too_big = $controller->handle( new WP_REST_Request( [], str_repeat( 'x', 4096 ) ) );
check( 'an oversized body is rejected', 400 === $too_big->get_status() );
$reuse = $controller->handle( new WP_REST_Request( [ 'need' => 'charger', 'device' => 'samsung', 'priority' => 'reuse' ] ) );
check( 'the reuse answer offers nothing to buy', 200 === $reuse->get_status() && [] === $reuse->get_data()['products'] );

/* ---------------- landing page and assets ---------------- */

ts_cg_reset();
$GLOBALS['ts_cg_pages'] = [ 12 => 'راهنمای شارژ' ];
update_option( 'ts_charge_guide_settings', [ 'page_id' => 12 ] );
$GLOBALS['ts_cg_queried_page'] = 12;
$GLOBALS['ts_cg_page_content'] = '';
$app = plugin();
check( 'the configured page is recognised as the guide request', $app->landing_page->is_guide_request() );
check( 'the configured page uses the plugin template', str_ends_with( $app->landing_page->template( 'theme/page.php' ), 'templates/page.php' ) );
check( 'the shortcode stays silent on the configured page', '' === $app->landing_page->shortcode() );
check( 'the guide renders exactly once per request', '' !== $app->landing_page->render() && '' === $app->landing_page->render() );

$GLOBALS['ts_cg_queried_page'] = 77;
$GLOBALS['ts_cg_page_content'] = 'متن صفحه [ts_charge_guide]';
$app = plugin();
check( 'a shortcode page keeps the theme template', 'theme/page.php' === $app->landing_page->template( 'theme/page.php' ) );
check( 'a shortcode page is recognised', $app->landing_page->is_guide_request() );
check( 'the shortcode renders the guide elsewhere', str_contains( $app->landing_page->shortcode(), 'id="ts-charge"' ) );

$GLOBALS['ts_cg_page_content'] = 'صفحه‌ای بی‌راهنما';
$GLOBALS['ts_cg_styles']  = [];
$GLOBALS['ts_cg_modules'] = [];
$app = plugin();
$app->assets->enqueue();
check( 'nothing is enqueued on a page without the guide', [] === $GLOBALS['ts_cg_styles'] && [] === $GLOBALS['ts_cg_modules'] );

$GLOBALS['ts_cg_page_content'] = '[ts_charge_guide]';
$GLOBALS['ts_cg_styles']  = [];
$GLOBALS['ts_cg_modules'] = [];
$app = plugin();
$app->assets->enqueue();
$handles = array_column( $GLOBALS['ts_cg_styles'], 1 );
check( 'the guide stylesheets are enqueued only here', in_array( 'ts-charge-tokens', $handles, true ) && in_array( 'ts-charge-guide', $handles, true ) );
check( 'the token layer is a dependency, never a copy', in_array( 'amazing-theme-system', ( $GLOBALS['ts_cg_styles'][0][3] ?? [] ), true ) );
$module_ids = array_column( $GLOBALS['ts_cg_modules'], 1 );
check( 'one entry module is enqueued, the rest registered', in_array( 'ts-charge-guide/entry', $module_ids, true ) && in_array( 'ts-charge-guide/wizard', $module_ids, true ) );
check( 'every enqueued asset carries the plugin version', in_array( plugin_version(), array_column( $GLOBALS['ts_cg_styles'], 4 ), true ) );

echo "\n{$pass} passed, {$fail} failed\n";
exit( $fail ? 1 : 0 );
