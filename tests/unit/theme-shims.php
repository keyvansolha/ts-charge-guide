<?php
/**
 * Theme side of the harness: the constants, the loop functions and the script
 * registry the theme provides, plus where the card partials are read from.
 *
 * The card partials used here live in tests/fixtures/theme/ and mirror the
 * markup the theme's own partials emit (read off the live site). They are test
 * doubles: the real partials need a WordPress runtime, so the harness stands in
 * for them and the real render is verified on the site after deploy.
 *
 * @package TSChargeGuide
 */

declare(strict_types=1);

// Where the theme's card partials are read from.
if ( ! defined( 'THEME_COMPONENTS' ) ) {
	define( 'THEME_COMPONENTS', __DIR__ . '/../fixtures/theme/components/' );
}
if ( ! defined( 'THEME_LIB_DIR' ) ) {
	define( 'THEME_LIB_DIR', __DIR__ . '/../fixtures/theme/lib/' );
}
if ( ! defined( 'THEME_VERSION' ) ) {
	define( 'THEME_VERSION', 'theme-test' );
}
if ( ! defined( 'IS_MOBILE' ) ) {
	define( 'IS_MOBILE', false );
}

/**
 * Fake posts, keyed by id: the blog cards read these through the loop.
 *
 * @return array<int, array<string, string>>
 */
function ts_cg_posts(): array {
	return $GLOBALS['ts_cg_posts'];
}

/**
 * Register a fake post and make it reachable by path.
 *
 * @param int                  $id   Post id.
 * @param array<string, mixed> $post Post fields.
 * @return void
 */
function ts_cg_post( int $id, array $post ): void {
	$GLOBALS['ts_cg_posts'][ $id ]          = $post;
	$GLOBALS['ts_cg_post_paths'][ (string) ( $post['path'] ?? '' ) ] = $id;
}

/** Current post in the fake loop. */
function ts_cg_the_post(): ?array {
	$id = (int) $GLOBALS['ts_cg_current'];
	return isset( $GLOBALS['ts_cg_posts'][ $id ] ) ? $GLOBALS['ts_cg_posts'][ $id ] : null;
}

/** Make a post current, the way setup_postdata() does. */
function setup_postdata( $post ): bool {
	$GLOBALS['ts_cg_current'] = is_object( $post ) ? (int) $post->ID : (int) $post;
	return true;
}

/** Put the previous post back. */
function wp_reset_postdata(): void {
	$GLOBALS['ts_cg_current'] = 0;
}

/** Post object with the fields the harness uses. (See wp-shims for get_post.) */

/** Resolve a site path back to a post id. */
function url_to_postid( $url ) {
	$path = (string) parse_url( (string) $url, PHP_URL_PATH );
	return (int) ( $GLOBALS['ts_cg_post_paths'][ $path ] ?? 0 );
}

/** Current post id in the loop. */
function get_the_ID() {
	return (int) $GLOBALS['ts_cg_current'];
}

/** Permalink of a post. */
function get_the_permalink( $id = 0 ) {
	$post = (int) $id ? get_post( $id ) : null;
	$key  = $post ? (int) $post->ID : (int) $GLOBALS['ts_cg_current'];
	return (string) ( $GLOBALS['ts_cg_posts'][ $key ]['permalink'] ?? '' );
}

/** Echo the permalink. */
function the_permalink(): void {
	echo esc_url( get_the_permalink() );
}

/** Echo the title, as the blog card does. */
function the_title(): void {
	echo esc_html( get_the_title( (int) $GLOBALS['ts_cg_current'] ) );
}

/** Post excerpt. */
function get_the_excerpt( $post = null ) {
	$key = (int) $GLOBALS['ts_cg_current'];
	return (string) ( $GLOBALS['ts_cg_posts'][ $key ]['excerpt'] ?? '' );
}

/** Featured image markup. */
function the_post_thumbnail( $size = 'post-thumbnail', $attr = [] ): void {
	$key = (int) $GLOBALS['ts_cg_current'];
	$src = (string) ( $GLOBALS['ts_cg_posts'][ $key ]['thumb'] ?? '' );
	if ( '' !== $src ) {
		echo '<img src="' . esc_url( $src ) . '" alt="" width="300" height="200">';
	}
}

/** Attachment markup for the product cards, resolved the way core does. */
function wp_get_attachment_image( $id, $size = 'thumbnail', $icon = false, $attr = [] ) {
	$url = function_exists( 'wp_get_attachment_image_url' ) ? wp_get_attachment_image_url( $id, $size ) : false;
	if ( ! is_string( $url ) || '' === $url ) {
		return '';
	}
	$alt = isset( $attr['alt'] ) ? (string) $attr['alt'] : '';
	return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" width="300" height="300">';
}

/** Post meta; the harness stores it in the fake product or post. */
function get_post_meta( $id, $key = '', $single = false ) {
	$id = (int) $id;
	if ( isset( $GLOBALS['ts_cg_products'][ $id ]['meta'][ $key ] ) ) {
		return $GLOBALS['ts_cg_products'][ $id ]['meta'][ $key ];
	}
	if ( isset( $GLOBALS['ts_cg_posts'][ $id ]['meta'][ $key ] ) ) {
		return $GLOBALS['ts_cg_posts'][ $id ]['meta'][ $key ];
	}
	return '';
}

/** Persian date helper the blog card calls. */
function wbsDate( $format = 'Y-m-d', $timestamp = null ) {
	$time = $timestamp ? strtotime( (string) $timestamp ) : time();
	return date( 'Y-F-d', $time );
}

/* ---------------- script/style registry ---------------- */

/** Is a script registered/enqueued? Mirrors wp_script_is(). */
function wp_script_is( $handle, $list = 'enqueued' ) {
	$store = 'registered' === $list ? $GLOBALS['ts_cg_scripts_registered'] : $GLOBALS['ts_cg_scripts'];
	return in_array( (string) $handle, (array) $store, true );
}

/** Record a script enqueue. */
function wp_enqueue_script( $handle, $src = '', $deps = [], $ver = false, $args = false ): bool {
	$GLOBALS['ts_cg_scripts'][] = (string) $handle;
	return true;
}

/** Record a script registration. */
function wp_register_script( $handle, $src = '', $deps = [], $ver = false, $args = false ): bool {
	$GLOBALS['ts_cg_scripts_registered'][] = (string) $handle;
	return true;
}

/** Reset the theme-side stores. */
function ts_cg_theme_reset(): void {
	$GLOBALS['ts_cg_posts']              = [];
	$GLOBALS['ts_cg_post_paths']         = [];
	$GLOBALS['ts_cg_current']            = 0;
	$GLOBALS['ts_cg_attachments']        = [];
	$GLOBALS['ts_cg_scripts']            = [];
	$GLOBALS['ts_cg_scripts_registered'] = [];
	$GLOBALS['ts_cg_styles_registered']  = [];
}
