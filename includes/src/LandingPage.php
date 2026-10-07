<?php
/**
 * Landing-page service: configured-page template and shortcode.
 *
 * @package TSChargeGuide
 */

namespace TSChargeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * On the configured page the plugin template replaces the page content
 * between the theme's header and footer; elsewhere the shortcode renders the
 * guide. The guide renders exactly once per request either way.
 */
final class LandingPage {

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Catalog adapter.
	 *
	 * @var CatalogAdapter
	 */
	private CatalogAdapter $catalog;

	/**
	 * Whether render output has been emitted for this request.
	 *
	 * @var bool
	 */
	private bool $rendered = false;

	/**
	 * Request-level catalog snapshot.
	 *
	 * @var array<int, array<string, mixed>>|null
	 */
	private ?array $snapshot = null;

	/**
	 * Constructor.
	 *
	 * @param Settings       $settings Settings.
	 * @param CatalogAdapter $catalog  Catalog adapter.
	 */
	public function __construct( Settings $settings, CatalogAdapter $catalog ) {
		$this->settings = $settings;
		$this->catalog  = $catalog;
	}

	/**
	 * The configured page ID when the current request is that page.
	 *
	 * @return int
	 */
	private function current_configured_page(): int {
		$page_id = $this->settings->landing_page_id();
		if ( $page_id < 1 || ! function_exists( 'is_page' ) || ! is_page( $page_id ) ) {
			return 0;
		}
		return $page_id;
	}

	/**
	 * template_include filter.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function template( string $template ): string {
		return $this->current_configured_page() > 0 ? TS_CHARGE_GUIDE_DIR . 'templates/page.php' : $template;
	}

	/**
	 * Shortcode handler. On the configured page the template renders the
	 * guide, so the shortcode returns nothing there.
	 *
	 * @return string
	 */
	public function shortcode(): string {
		if ( $this->current_configured_page() > 0 ) {
			return '';
		}
		return $this->render();
	}

	/**
	 * Whether the guide is on this request. Assets are gated on this, so no
	 * other page loads the guide's CSS or JS.
	 *
	 * @return bool
	 */
	public function is_guide_request(): bool {
		if ( $this->current_configured_page() > 0 ) {
			return true;
		}
		if ( ! function_exists( 'is_page' ) || ! is_page() || ! function_exists( 'get_queried_object' ) ) {
			return false;
		}
		$page = get_queried_object();
		return $page && isset( $page->post_content ) && function_exists( 'has_shortcode' ) && has_shortcode( (string) $page->post_content, 'ts_charge_guide' );
	}

	/**
	 * Render the guide.
	 *
	 * @return string HTML.
	 */
	public function render(): string {
		if ( $this->rendered ) {
			return '';
		}
		$this->rendered = true;

		$catalog = $this->snapshot();
		$config  = [
			'endpoint'    => function_exists( 'rest_url' ) ? rest_url( TS_CHARGE_GUIDE_REST_BASE . '/recommend' ) : '',
			'nonce'       => function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'wp_rest' ) : '',
			'hasProducts' => [] !== $catalog,
			'error'       => 'الان نتوانستیم نتیجه را بگیریم. یک بار دیگر امتحان کن؛ انتخاب‌هایت باقی می‌ماند.',
		];

		$view = new GuideView( $config, $catalog );
		return $view->render();
	}

	/**
	 * Live catalog for this request, or an empty array when WooCommerce is
	 * unavailable or the query fails. Queried once per request.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function snapshot(): array {
		if ( null === $this->snapshot ) {
			try {
				$this->snapshot = $this->catalog->products();
			} catch ( \Throwable $e ) {
				$this->snapshot = [];
			}
		}
		return $this->snapshot;
	}
}
