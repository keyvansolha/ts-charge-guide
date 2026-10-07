<?php
/**
 * WordPress function shims for pure-PHP tests.
 *
 * Unit tests run without WordPress; these shims provide the API surface the
 * plugin's classes and bootstrap reference. They are never loaded by the
 * shipped plugin.
 *
 * Usage in a test entry point:
 *   define( 'ABSPATH', ... ); require tests/unit/wp-shims.php;
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

$GLOBALS['ts_cg_options']    = [];
$GLOBALS['ts_cg_transients'] = [];
$GLOBALS['ts_cg_hooks']      = [];
$GLOBALS['ts_cg_query_log']  = [];

/** Plugin directory path (real filesystem). */
function plugin_dir_path( string $file ): string {
	return rtrim( dirname( $file ), '/\\' ) . '/';
}

/** Plugin directory URL (parity with WordPress; unused by the tests). */
function plugin_dir_url( string $file ): string {
	return 'https://store.example/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}

/** Activation hook (no-op outside WordPress). */
function register_activation_hook( string $file, callable $callback ): void {
}

/** Record a hook registration. */
function add_action( string $hook, $callback, int $priority = 10, int $args = 1 ): void {
	$GLOBALS['ts_cg_hooks'][ $hook ][] = [ $callback, $priority ];
}

/** add_filter alias in the shim environment. */
function add_filter( string $hook, $callback, int $priority = 10, int $args = 1 ): void {
	add_action( $hook, $callback, $priority, $args );
}

/** Filter passthrough: no filter is registered in the pure-PHP tests. */
function apply_filters( string $hook, $value, ...$args ) {
	return $value;
}

/** Record a shortcode registration. */
function add_shortcode( string $tag, $callback ): void {
	$GLOBALS['ts_cg_shortcodes'][ $tag ] = $callback;
}

/** Options API backed by a global array. */
function get_option( string $name, $default = false ) {
	return $GLOBALS['ts_cg_options'][ $name ] ?? $default;
}

/** Options API backed by a global array. */
function update_option( string $name, $value, $autoload = null ): bool {
	$GLOBALS['ts_cg_options'][ $name ] = $value;
	return true;
}

/** Options API backed by a global array. */
function add_option( string $name, $value = '', $deprecated = '', $autoload = 'yes' ): bool {
	$GLOBALS['ts_cg_options'][ $name ] = $value;
	return true;
}

/** Options API backed by a global array. */
function delete_option( string $name ): bool {
	unset( $GLOBALS['ts_cg_options'][ $name ] );
	return true;
}

/** Transients backed by a global array (no expiry simulation needed). */
function get_transient( string $key ) {
	return $GLOBALS['ts_cg_transients'][ $key ] ?? false;
}

/** Transients backed by a global array. */
function set_transient( string $key, $value, int $expiration = 0 ): bool {
	$GLOBALS['ts_cg_transients'][ $key ] = $value;
	return true;
}

/** Transients backed by a global array. */
function delete_transient( string $key ): bool {
	unset( $GLOBALS['ts_cg_transients'][ $key ] );
	return true;
}

/** Escape passthrough: the tests assert the call sites, not the encoding. */
function esc_html( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

/** Escape passthrough. */
function esc_attr( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

/** Escape passthrough. */
function esc_url( string $url ): string {
	return $url;
}

/** Strip everything a post-context sink would refuse, keeping the allowed tags. */
function wp_kses_post( string $html ): string {
	$html = preg_replace( '#<(script|iframe|object|embed|style|link|meta)\b[^>]*>.*?</\1>#is', '', $html );
	$html = preg_replace( '#<(script|iframe|object|embed|style|link|meta)\b[^>]*/?>#is', '', (string) $html );
	return (string) preg_replace( '#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', (string) $html );
}

/** JSON encode with WordPress defaults for tests. */
function wp_json_encode( $data, int $flags = 0 ) {
	return json_encode( $data, $flags | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

/** Translation passthrough. */
function __( string $text, string $domain = 'ts-charge-guide' ): string {
	return $text;
}

/** Shortcode attribute defaults. */
function shortcode_atts( array $pairs, $atts, string $shortcode = '' ): array {
	$atts = is_array( $atts ) ? $atts : [];
	return array_merge( $pairs, $atts );
}

/** Site home URL. */
function home_url( string $path = '/' ): string {
	return rtrim( (string) ( $GLOBALS['ts_cg_home'] ?? 'https://store.example' ), '/' ) . $path;
}

/** REST base URL. */
function rest_url( string $path = '' ): string {
	return home_url( '/wp-json/' . ltrim( $path, '/' ) );
}

/** Admin URL. */
function admin_url( string $path = '' ): string {
	return home_url( '/wp-admin/' . ltrim( $path, '/' ) );
}

/** Template directory URL (the token layer's own home). */
function get_template_directory_uri(): string {
	return home_url( '/wp-content/themes/amazing' );
}

/** Site name. */
function get_bloginfo( string $key = 'name' ): string {
	return 'Tehran Speaker';
}

/** Query-string helper. */
function add_query_arg( $args, string $url = '' ): string {
	if ( ! is_array( $args ) ) {
		return $url;
	}
	$parts = [];
	foreach ( $args as $key => $value ) {
		$parts[] = rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
	}
	return $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . implode( '&', $parts );
}

/** Nonce URL helper. */
function wp_nonce_url( string $url, string $action = '' ): string {
	return $url . '&_wpnonce=test-nonce';
}

/** Escaped textarea content (core escapes it like text). */
function esc_textarea( $text ): string {
	return esc_html( $text );
}

/** Nonce factory. */
function wp_create_nonce( string $action = '' ): string {
	return 'test-nonce';
}

/** Nonce check (always valid in tests). */
function check_admin_referer( string $action = '', string $query_arg = '_wpnonce' ): bool {
	return true;
}

/** Capability check. */
function current_user_can( string $capability ): bool {
	return true;
}

/** Style registry check: the theme's token layer is registered in tests. */
function wp_style_is( string $handle, string $list = 'enqueued' ): bool {
	return true;
}

/** Style registration recorder. */
function wp_register_style( string $handle, string $src = '', array $deps = [], $ver = false, string $media = 'all' ): void {
	$GLOBALS['ts_cg_styles'][] = [ 'register', $handle, $src, $deps, $ver ];
}

/** Style enqueue recorder. */
function wp_enqueue_style( string $handle, string $src = '', array $deps = [], $ver = false, string $media = 'all' ): void {
	$GLOBALS['ts_cg_styles'][] = [ 'enqueue', $handle, $src, $deps, $ver ];
}

/** Script-module registration recorder. */
function wp_register_script_module( string $id, string $src, array $deps = [], $version = null ): void {
	$GLOBALS['ts_cg_modules'][] = [ 'register', $id, $src, $deps, $version ];
}

/** Script-module enqueue recorder. */
function wp_enqueue_script_module( string $id, string $src = '', array $deps = [], $version = null ): void {
	$GLOBALS['ts_cg_modules'][] = [ 'enqueue', $id, $src, $deps, $version ];
}

/** Page-level query state used by the landing-page tests. */
function is_page( $page = '' ): bool {
	$current = (int) ( $GLOBALS['ts_cg_queried_page'] ?? 0 );
	if ( '' === $page || null === $page ) {
		return $current > 0;
	}
	return $current === (int) $page;
}

/** Queried object (a page). */
function get_queried_object(): ?object {
	$id = (int) ( $GLOBALS['ts_cg_queried_page'] ?? 0 );
	return $id > 0 ? (object) [ 'ID' => $id, 'post_type' => 'page', 'post_status' => 'publish', 'post_content' => (string) ( $GLOBALS['ts_cg_page_content'] ?? '' ) ] : null;
}

/** Post lookup. */
function get_post( $id ) {
	$id = (int) $id;
	// Fake posts registered by the theme-side fixtures (blog cards read them).
	if ( isset( $GLOBALS['ts_cg_posts'][ $id ] ) ) {
		$post              = new stdClass();
		$post->ID          = $id;
		$post->post_title  = (string) ( $GLOBALS['ts_cg_posts'][ $id ]['title'] ?? '' );
		$post->post_type   = (string) ( $GLOBALS['ts_cg_posts'][ $id ]['post_type'] ?? 'post' );
		$post->post_status = (string) ( $GLOBALS['ts_cg_posts'][ $id ]['post_status'] ?? 'publish' );
		return $post;
	}
	if ( $id === (int) ( $GLOBALS['ts_cg_queried_page'] ?? 0 ) || isset( $GLOBALS['ts_cg_pages'][ $id ] ) ) {
		return (object) [ 'ID' => $id, 'post_type' => 'page', 'post_status' => 'publish' ];
	}
	return null;
}

/** Page list for the settings screen. */
function get_pages( array $args = [] ): array {
	return array_map(
		static fn( int $id ): object => (object) [ 'ID' => $id ],
		array_keys( (array) ( $GLOBALS['ts_cg_pages'] ?? [] ) )
	);
}

/** Page title. */
function get_the_title( $post ): string {
	$id = is_object( $post ) ? (int) $post->ID : (int) $post;
	if ( isset( $GLOBALS['ts_cg_posts'][ $id ]['title'] ) ) {
		return (string) $GLOBALS['ts_cg_posts'][ $id ]['title'];
	}
	return (string) ( $GLOBALS['ts_cg_pages'][ $id ] ?? 'Page' );
}

/** Shortcode presence in content. */
function has_shortcode( string $content, string $tag ): bool {
	return str_contains( $content, '[' . $tag );
}

/** selected() helper output. */
function selected( $selected, $current = true, bool $display = true ): string {
	$out = (string) $selected === (string) $current ? " selected='selected'" : '';
	if ( $display ) {
		echo $out;
	}
	return $out;
}

/** checked() helper output. */
function checked( $checked, $current = true, bool $display = true ): string {
	$out = (string) $checked === (string) $current ? " checked='checked'" : '';
	if ( $display ) {
		echo $out;
	}
	return $out;
}

/** Settings API registration recorder. */
function register_setting( string $group, string $name, array $args = [] ): void {
	$GLOBALS['ts_cg_settings'][ $group ] = [ $name, $args ];
}

/** Admin menu recorder. */
function add_options_page( string $page_title, string $menu_title, string $capability, string $slug, $callback = null ): string {
	$GLOBALS['ts_cg_admin_pages'][] = [ $slug, $capability, $callback ];
	return $slug;
}

/** Settings fields renderer (nonce + action fields). */
function settings_fields( string $group ): void {
	echo '<input type="hidden" name="option_page" value="' . $group . '">';
}

/** Submit button renderer. */
function submit_button( string $text = 'Save Changes' ): void {
	echo '<button type="submit">' . $text . '</button>';
}

/** Term list lookup. */
function get_terms( array $args = [] ) {
	$taxonomy = (string) ( $args['taxonomy'] ?? 'product_cat' );
	$terms    = $GLOBALS['ts_cg_term_objects'] ?? [];
	return array_values( array_filter( $terms, static fn( $term ): bool => ( $term->taxonomy ?? '' ) === $taxonomy ) );
}

/** Single term lookup by ID. */
function get_term( int $term_id, string $taxonomy = 'product_cat' ) {
	foreach ( (array) ( $GLOBALS['ts_cg_term_objects'] ?? [] ) as $term ) {
		if ( (int) $term->term_id === $term_id ) {
			return $term;
		}
	}
	return null;
}

/** Relationship terms (brand taxonomy). */
function get_the_terms( $post_id, string $taxonomy ) {
	$brands = (array) ( $GLOBALS['ts_cg_brands'] ?? [] );
	$id     = (int) $post_id;
	if ( ! isset( $brands[ $id ] ) ) {
		return false;
	}
	return [ (object) [ 'term_id' => 1, 'name' => (string) $brands[ $id ] ] ];
}

/** WP_Error stand-in. */
class TS_CG_WP_Error {}

/** WP_Error test shim (fixtures never return errors). */
function is_wp_error( $thing ): bool {
	return $thing instanceof TS_CG_WP_Error;
}

/** Register a product-category term for the settings/catalog tests. */
function ts_cg_term( int $id, string $name, string $slug, int $count = 0, string $taxonomy = 'product_cat' ): void {
	$GLOBALS['ts_cg_term_objects'][] = (object) [
		'term_id'  => $id,
		'name'     => $name,
		'slug'     => $slug,
		'count'    => $count,
		'taxonomy' => $taxonomy,
	];
}

/** Redirect recorder (the flush action must not exit inside tests). */
function wp_safe_redirect( string $url ): void {
	$GLOBALS['ts_cg_redirect'] = $url;
}

/** wp_die recorder. */
function wp_die( $message = '' ): void {
	$GLOBALS['ts_cg_died'] = (string) $message;
	throw new RuntimeException( 'wp_die: ' . (string) $message );
}

/** Admin edit link. */
function get_edit_post_link( $id, string $context = 'display' ): string {
	return home_url( '/wp-admin/post.php?post=' . (int) $id . '&action=edit' );
}

/** Reset every shim store between test files. */
function ts_cg_reset(): void {
	if ( function_exists( 'ts_cg_theme_reset' ) ) {
		ts_cg_theme_reset();
	}
	$GLOBALS['ts_cg_options']      = [];
	$GLOBALS['ts_cg_transients']   = [];
	$GLOBALS['ts_cg_query_log']    = [];
	$GLOBALS['ts_cg_styles']       = [];
	$GLOBALS['ts_cg_modules']      = [];
	$GLOBALS['ts_cg_products']     = [];
	$GLOBALS['ts_cg_redirect']     = '';
	$GLOBALS['ts_cg_queried_page'] = 0;
	$GLOBALS['ts_cg_page_content'] = '';
	$GLOBALS['ts_cg_cache_buster'] = 0;
}
