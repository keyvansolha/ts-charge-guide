<?php
/**
 * Application container and composition root.
 *
 * @package TSChargeGuide
 */

namespace TSChargeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Constructs services and wires them into WordPress hooks.
 *
 * Business logic never lives in a hook callback; each callback delegates to a
 * focused service.
 */
final class App {

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	public Settings $settings;

	/**
	 * WooCommerce catalog adapter.
	 *
	 * @var CatalogAdapter
	 */
	public CatalogAdapter $catalog;

	/**
	 * Landing-page service.
	 *
	 * @var LandingPage
	 */
	public LandingPage $landing_page;

	/**
	 * Assets service.
	 *
	 * @var Assets
	 */
	public Assets $assets;

	/**
	 * Admin screens.
	 *
	 * @var AdminScreens
	 */
	public AdminScreens $admin;

	/**
	 * Recommendation route.
	 *
	 * @var Rest\RecommendController
	 */
	public Rest\RecommendController $recommend;

	/**
	 * Construct services. Dependencies are explicit and narrow.
	 */
	public function __construct() {
		$this->settings     = new Settings();
		$this->catalog      = new CatalogAdapter( $this->settings );
		$this->landing_page = new LandingPage( $this->settings, $this->catalog );
		$this->assets       = new Assets( $this->landing_page, $this->settings );
		$this->recommend    = new Rest\RecommendController( $this->settings, $this->catalog );
		$this->admin        = new AdminScreens( $this->settings, $this->catalog );
	}

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this->recommend, 'register_routes' ] );

		add_action( 'admin_menu', [ $this->admin, 'register_menu' ] );
		add_action( 'admin_init', [ $this->settings, 'register_settings' ] );
		$this->settings->register_hooks();
		add_action( 'admin_post_ts_charge_guide_flush', [ $this->admin, 'handle_flush_cache' ] );
		add_action( 'admin_notices', [ $this, 'render_environment_notice' ] );

		add_action( 'wp_enqueue_scripts', [ $this->assets, 'enqueue' ], 1001 );

		add_filter( 'template_include', [ $this->landing_page, 'template' ], 99 );
		add_shortcode( 'ts_charge_guide', [ $this->landing_page, 'shortcode' ] );
	}

	/**
	 * Explain a missing runtime dependency to administrators without affecting
	 * public requests.
	 */
	public function render_environment_notice(): void {
		if ( ! current_user_can( 'manage_options' ) || ts_charge_guide_environment_ready() ) {
			return;
		}
		$message = version_compare( PHP_VERSION, '8.0', '<' )
			? 'TehranSpeaker Charge Guide requires PHP 8.0 or newer.'
			: 'TehranSpeaker Charge Guide requires WooCommerce to be active.';
		echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
	}
}
