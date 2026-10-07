<?php
/**
 * Shared REST plumbing for the guide controllers.
 *
 * @package TSChargeGuide
 */

namespace TSChargeGuide\Rest;

use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Common request/response behavior: JSON-only input, a bounded body, no-store
 * cache headers and a controlled error envelope.
 */
abstract class Controller {

	/**
	 * Maximum accepted request body size in bytes.
	 */
	protected const MAX_BODY_BYTES = 2048;

	/**
	 * Respond with data and the no-store header set.
	 *
	 * @param mixed $data   Response data.
	 * @param int   $status HTTP status.
	 * @return WP_REST_Response
	 */
	protected function respond( $data, int $status = 200 ): WP_REST_Response {
		$response = new WP_REST_Response( $data, $status );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'X-Robots-Tag', 'noindex, nofollow' );
		return $response;
	}

	/**
	 * Controlled error envelope. Internal exception messages never reach
	 * visitors; only the message key the browser expects.
	 *
	 * @param string $message Public message key.
	 * @param int    $status  HTTP status.
	 * @return WP_REST_Response
	 */
	protected function error( string $message, int $status ): WP_REST_Response {
		return $this->respond( [ 'message' => $message ], $status );
	}

	/**
	 * Validate the request shape: size limit and JSON object body.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<string, mixed>|WP_REST_Response
	 */
	protected function parse_body( WP_REST_Request $request ) {
		if ( strlen( (string) $request->get_body() ) > self::MAX_BODY_BYTES ) {
			return $this->error( 'invalid_request', 400 );
		}
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			return $this->error( 'invalid_answers', 400 );
		}
		return $body;
	}

	/**
	 * Require an exact allow-list of top-level request fields.
	 *
	 * @param array<string, mixed> $body    Decoded body.
	 * @param array<int, string>   $allowed Allowed field names.
	 * @return bool
	 */
	protected function valid_body_keys( array $body, array $allowed ): bool {
		return [] === array_diff( array_keys( $body ), $allowed );
	}

	/**
	 * Log an exception class without exposing the message to visitors.
	 *
	 * @param \Throwable $e Caught exception.
	 */
	protected function log_failure( \Throwable $e ): void {
		if ( function_exists( 'error_log' ) ) {
			error_log( '[TS Charge Guide] request failed: ' . get_class( $e ) );
		}
	}

	/**
	 * Register routes for this controller.
	 *
	 * @return void
	 */
	abstract public function register_routes(): void;
}
