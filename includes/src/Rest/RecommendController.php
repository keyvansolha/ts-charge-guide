<?php
/**
 * Recommend route: the wizard result.
 *
 * @package TSChargeGuide
 */

namespace TSChargeGuide\Rest;

use TSChargeGuide\CatalogAdapter;
use TSChargeGuide\Recommendation;
use TSChargeGuide\Settings;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * POST /wp-json/ts-charge/v1/recommend — { need, device, priority }.
 *
 * The endpoint maps answers to the guide's own copy plus cards read from the
 * live catalog. It never certifies compatibility and never returns a product
 * the shopper cannot see on the page.
 */
final class RecommendController extends Controller {

	/**
	 * Accepted top-level fields.
	 */
	private const ALLOWED_KEYS = [ 'need', 'device', 'priority' ];

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
	 * Register the route.
	 */
	public function register_routes(): void {
		register_rest_route(
			TS_CHARGE_GUIDE_REST_BASE,
			'/recommend',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Handle a recommendation request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$body = $this->parse_body( $request );
		if ( $body instanceof WP_REST_Response ) {
			return $body;
		}
		if ( ! $this->valid_body_keys( $body, self::ALLOWED_KEYS ) || ! Recommendation::valid( $body ) ) {
			return $this->error( 'invalid_answers', 400 );
		}

		try {
			$answers = [
				'need'     => (string) $body['need'],
				'device'   => (string) $body['device'],
				'priority' => (string) $body['priority'],
			];
			$result = Recommendation::build( $answers, $this->catalog->products() );
		} catch ( \Throwable $e ) {
			$this->log_failure( $e );
			return $this->error( 'unavailable', 503 );
		}

		return $this->respond( $result );
	}
}
