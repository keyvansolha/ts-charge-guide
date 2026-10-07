<?php
/**
 * REST API shims for the recommend-route tests.
 */

declare(strict_types=1);

/** Minimal WP_REST_Request stand-in. */
final class WP_REST_Request {

	/** @var array<string, mixed> */
	private array $params;

	/** Raw body. */
	private string $body;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $params JSON-decoded parameters.
	 * @param string               $body   Raw body.
	 */
	public function __construct( array $params = [], string $body = '' ) {
		$this->params = $params;
		$this->body   = '' === $body ? (string) json_encode( $params ) : $body;
	}

	/** Raw request body. */
	public function get_body(): string {
		return $this->body;
	}

	/** Decoded JSON body. */
	public function get_json_params() {
		$decoded = json_decode( $this->body, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	/** Parameter lookup. */
	public function get_param( string $key ) {
		return $this->params[ $key ] ?? null;
	}
}

/** Minimal WP_REST_Response stand-in. */
final class WP_REST_Response {

	/** @var mixed */
	private $data;

	/** HTTP status. */
	private int $status;

	/** @var array<string, string> */
	private array $headers = [];

	/**
	 * Constructor.
	 *
	 * @param mixed $data   Payload.
	 * @param int   $status HTTP status.
	 */
	public function __construct( $data = null, int $status = 200 ) {
		$this->data   = $data;
		$this->status = $status;
	}

	/** Add a header. */
	public function header( string $key, string $value ): void {
		$this->headers[ $key ] = $value;
	}

	/** Payload. */
	public function get_data() {
		return $this->data;
	}

	/** Status code. */
	public function get_status(): int {
		return $this->status;
	}

	/** Header lookup. */
	public function get_headers(): array {
		return $this->headers;
	}
}

/** Route registry recorder. */
function register_rest_route( string $namespace, string $route, array $args = [] ): void {
	$GLOBALS['ts_cg_routes'][] = [ $namespace, $route, $args ];
}

/** Open permission callback. */
function __return_true(): bool {
	return true;
}
