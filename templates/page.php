<?php
/**
 * Landing-page template: theme header/footer around the plugin guide.
 *
 * @package TSChargeGuide
 */

defined( 'ABSPATH' ) || exit;

get_header();

echo '<main id="site-main" class="ts-charge-page">';
// GuideView escapes every dynamic value and JSON-hex-encodes its bootstrap;
// a second wp_kses_post() pass would strip the required application/json tag.
echo ts_charge_guide()->landing_page->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</main>';

// Keep the existing global commerce navigation owned by the amazing theme.
if ( defined( 'THEME_TEMPLATE' ) && defined( 'IS_MOBILE' ) ) {
	if ( IS_MOBILE ) {
		foreach ( [ 'menu', 'categories', 'video', 'search', 'cart', 'profile' ] as $part ) {
			$path = THEME_TEMPLATE . 'layout/footer/mobile/' . $part . '.php';
			if ( is_file( $path ) ) {
				require $path;
			}
		}
	} else {
		$path = THEME_TEMPLATE . 'layout/footer/dynamic-iland.php';
		if ( is_file( $path ) ) {
			require $path;
		}
	}
}

get_footer();
