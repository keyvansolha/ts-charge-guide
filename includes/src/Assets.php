<?php
/**
 * Assets service: styles and browser modules for the guide.
 *
 * @package TSChargeGuide
 */

namespace TSChargeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the guide's styles and scripts only on a request that renders the
 * guide, and always on top of the theme's own token layer.
 *
 * The guide ships no font: it names the theme's families and lets the theme's
 * @font-face rules supply the files. It also ships no palette: token-bridge
 * aliases the theme's semantic variables.
 */
final class Assets {

	/**
	 * Landing-page service.
	 *
	 * @var LandingPage
	 */
	private LandingPage $landing_page;

	/**
	 * Constructor.
	 *
	 * @param LandingPage $landing_page Landing-page service.
	 */
	public function __construct( LandingPage $landing_page ) {
		$this->landing_page = $landing_page;
	}

	/**
	 * Enqueue guide assets when the guide is on this page.
	 */
	public function enqueue(): void {
		if ( ! $this->landing_page->is_guide_request() ) {
			return;
		}
		$base = TS_CHARGE_GUIDE_URL . 'assets/';
		$ver  = TS_CHARGE_GUIDE_VERSION;

		// Reuse the theme's semantic token layer; never a parallel palette.
		if ( ! wp_style_is( 'amazing-theme-system', 'registered' ) && function_exists( 'get_template_directory_uri' ) ) {
			wp_register_style( 'amazing-theme-system', get_template_directory_uri() . '/assets/css/theme-system.css', [], null );
		}

		wp_enqueue_style( 'ts-charge-tokens', $base . 'token-bridge.css', [ 'amazing-theme-system' ], $ver );
		wp_enqueue_style( 'ts-charge-guide', $base . 'style.css', [ 'ts-charge-tokens' ], $ver );

		$this->register_modules( $base, $ver );
		wp_enqueue_script_module( 'ts-charge-guide/entry' );
	}

	/**
	 * Register the split ES modules with the Script Modules API.
	 *
	 * @param string $base Assets URL base.
	 * @param string $ver  Version.
	 */
	private function register_modules( string $base, string $ver ): void {
		$modules = [
			'ts-charge-guide/rest'   => [ 'src' => 'js/rest.js',   'deps' => [] ],
			'ts-charge-guide/dom'    => [ 'src' => 'js/dom.js',    'deps' => [] ],
			'ts-charge-guide/wizard' => [ 'src' => 'js/wizard.js', 'deps' => [ 'ts-charge-guide/rest', 'ts-charge-guide/dom' ] ],
			'ts-charge-guide/panels' => [ 'src' => 'js/panels.js', 'deps' => [] ],
			'ts-charge-guide/entry'  => [ 'src' => 'js/entry.js',  'deps' => [ 'ts-charge-guide/wizard', 'ts-charge-guide/panels' ] ],
		];
		foreach ( $modules as $id => $module ) {
			wp_register_script_module( $id, $base . $module['src'], $module['deps'], $ver );
		}
	}
}
