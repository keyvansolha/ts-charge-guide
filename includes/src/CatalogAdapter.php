<?php
/**
 * WooCommerce catalog adapter: the guide's only source of product data.
 *
 * @package TSChargeGuide
 */

namespace TSChargeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the products the owner assigned to the guide from WooCommerce.
 *
 * Nothing about the store is hardcoded: the two category terms come from the
 * settings screen, and every field of a card (name, permalink, image, price)
 * is read live. An unconfigured plugin returns no rows at all, so the product
 * grid simply does not render.
 *
 * Only products the store currently has in stock are offered — the query asks
 * WooCommerce for `stock_status = instock`, and a row is dropped again if the
 * product reports itself out of stock, so neither a stale index nor a product
 * that changed while the request ran can put an unavailable card on the page.
 * What was withheld is reported in the admin live check rather than hidden
 * silently.
 *
 * Every query is bounded (a per-kind card limit) and cached in a transient
 * keyed by the configuration, with a short negative cache so a failing query
 * cannot be re-run on every render.
 */
final class CatalogAdapter {

	/**
	 * Seconds a failed lookup is remembered.
	 */
	private const NEGATIVE_TTL = 60;

	/**
	 * Cache payload shape. Bump when the cached structure changes, so a
	 * transient written by an older revision is never decoded as the new one.
	 */
	private const CACHE_FORMAT = 2;

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Request-level row cache, keyed by kind.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private array $memory = [];

	/**
	 * Request-level withhold report, keyed by kind.
	 *
	 * @var array<string, array<string, int>>
	 */
	private array $withheld = [];

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Whether WooCommerce provides the product API at runtime.
	 *
	 * @return bool
	 */
	public function woo_available(): bool {
		return function_exists( 'wc_get_products' ) && function_exists( 'wc_get_product' );
	}

	/**
	 * Every configured product, both kinds, in a stable order.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function products(): array {
		$rows = [];
		foreach ( [ 'powerbank', 'charger' ] as $kind ) {
			foreach ( $this->by_kind( $kind ) as $row ) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * The configured products of one kind.
	 *
	 * @param string $kind 'powerbank' or 'charger'.
	 * @return array<int, array<string, mixed>>
	 */
	public function by_kind( string $kind ): array {
		if ( isset( $this->memory[ $kind ] ) ) {
			return $this->memory[ $kind ];
		}
		$term_id = (int) ( $this->settings->category_terms()[ $kind ] ?? 0 );
		if ( $term_id < 1 ) {
			$this->memory[ $kind ]   = [];
			$this->withheld[ $kind ] = [ 'stock' => 0 ];
			return [];
		}

		$limit  = $this->settings->cards_per_kind();
		$key    = TS_CHARGE_GUIDE_CACHE_PREFIX . md5( self::CACHE_FORMAT . '|' . TS_CHARGE_GUIDE_VERSION . '|' . $kind . '|' . $term_id . '|' . $limit );
		$cached = get_transient( $key );
		if ( is_array( $cached ) && isset( $cached['rows'] ) && is_array( $cached['rows'] ) ) {
			$this->memory[ $kind ]   = array_values( array_filter( $cached['rows'], 'is_array' ) );
			$this->withheld[ $kind ] = isset( $cached['withheld'] ) && is_array( $cached['withheld'] )
				? array_map( 'intval', $cached['withheld'] )
				: [ 'stock' => 0 ];
			return $this->memory[ $kind ];
		}

		try {
			[ $rows, $withheld ] = $this->query( $kind, $term_id, $limit );
			$this->remember( $key, [ 'rows' => $rows, 'withheld' => $withheld ], TS_CHARGE_GUIDE_TRANSIENT_EXPIRY );
		} catch ( \Throwable $e ) {
			// A failing catalog must neither break the page nor be retried on
			// every render.
			$rows     = [];
			$withheld = [ 'stock' => 0 ];
			$this->remember( $key, [ 'rows' => [], 'withheld' => $withheld ], self::NEGATIVE_TTL );
		}

		$this->memory[ $kind ]   = $rows;
		$this->withheld[ $kind ] = $withheld;
		return $rows;
	}

	/**
	 * How many products the stock rule withheld for one kind.
	 *
	 * @param string $kind 'powerbank' or 'charger'.
	 * @return array<string, int>
	 */
	public function withheld( string $kind ): array {
		if ( ! isset( $this->withheld[ $kind ] ) ) {
			$this->by_kind( $kind );
		}
		return $this->withheld[ $kind ] ?? [ 'stock' => 0 ];
	}

	/**
	 * One product row by WooCommerce product ID.
	 *
	 * @param int $id Product ID.
	 * @return array<string, mixed>|null
	 */
	public function find( int $id ): ?array {
		foreach ( $this->products() as $row ) {
			if ( (int) $row['id'] === $id ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * Query one configured term and normalize the products.
	 *
	 * @param string $kind    Kind label.
	 * @param int    $term_id Product-category term ID.
	 * @param int    $limit   Maximum rows.
	 * @return array{0: array<int, array<string, mixed>>, 1: array<string, int>} Rows and the withhold report.
	 */
	private function query( string $kind, int $term_id, int $limit ): array {
		$withheld = [ 'stock' => 0 ];
		if ( ! $this->woo_available() ) {
			return [ [], $withheld ];
		}
		$term = get_term( $term_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) || empty( $term->slug ) ) {
			return [ [], $withheld ];
		}

		// What the rule hides. Counted separately with an ids-only paged
		// query, because the main query below never returns these products,
		// so the loop could not report them; the count is what makes the
		// admin live check honest instead of silently short.
		$withheld['stock'] = $this->unavailable_count( (string) $term->slug );

		$args = [
			'status'       => 'publish',
			'limit'        => $limit,
			'category'     => [ (string) $term->slug ],
			'stock_status' => 'instock',
			'orderby'      => 'title',
			'order'        => 'ASC',
			'return'       => 'objects',
		];

		/**
		 * Filter the WooCommerce query the guide uses for one category.
		 *
		 * Adding `outofstock` here would undo the guide's in-stock rule; the
		 * withheld count below is what reports the difference.
		 *
		 * @param array<string, mixed> $args Query args.
		 * @param string               $kind 'powerbank' or 'charger'.
		 */
		$args = apply_filters( 'ts_charge_guide_catalog_args', $args, $kind );

		$products = wc_get_products( is_array( $args ) ? $args : [] );
		if ( ! is_array( $products ) ) {
			return [ [], $withheld ];
		}

		$rows = [];
		foreach ( $products as $product ) {
			if ( ! is_object( $product ) ) {
				continue;
			}
			if ( ! $this->in_stock( $product ) ) {
				// A stale index or a product that sold out mid-request never
				// reaches the page.
				$withheld['stock']++;
				continue;
			}
			$rows[] = $this->normalize( $product, $kind );
		}
		return [ $rows, $withheld ];
	}

	/**
	 * How many published products in this term the stock rule hides.
	 *
	 * WooCommerce's own `instock` status is what the guide offers, so anything
	 * marked `outofstock` or `onbackorder` is counted here. The query returns
	 * ids only and asks for a single row purely to get the total, so no
	 * product object is hydrated just to be discarded.
	 *
	 * @param string $slug Product-category slug.
	 * @return int
	 */
	private function unavailable_count( string $slug ): int {
		$args = [
			'status'       => 'publish',
			'category'     => [ $slug ],
			'stock_status' => [ 'outofstock', 'onbackorder' ],
			'limit'        => 1,
			'return'       => 'ids',
			'paginate'     => true,
		];

		$result = wc_get_products( $args );
		if ( is_object( $result ) && isset( $result->total ) ) {
			return max( 0, (int) $result->total );
		}
		return is_array( $result ) ? count( $result ) : 0;
	}

	/**
	 * Whether WooCommerce itself considers this product available.
	 *
	 * @param object $product WooCommerce product object.
	 * @return bool
	 */
	private function in_stock( object $product ): bool {
		return method_exists( $product, 'is_in_stock' ) ? (bool) $product->is_in_stock() : true;
	}

	/**
	 * Normalize one WooCommerce product into the guide's card shape.
	 *
	 * @param object $product WooCommerce product object.
	 * @param string $kind    Kind label.
	 * @return array<string, mixed>
	 */
	private function normalize( object $product, string $kind ): array {
		$id    = (int) $product->get_id();
		$image = null;
		if ( method_exists( $product, 'get_image_id' ) && function_exists( 'wp_get_attachment_image_url' ) ) {
			$image_id = (int) $product->get_image_id();
			if ( $image_id > 0 ) {
				$url = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
				$image = is_string( $url ) && '' !== $url ? $url : null;
			}
		}

		$price = function_exists( 'wc_get_price_to_display' ) ? (float) wc_get_price_to_display( $product ) : 0.0;
		$html  = method_exists( $product, 'get_price_html' ) ? (string) $product->get_price_html() : '';
		$link  = method_exists( $product, 'get_permalink' ) ? (string) $product->get_permalink() : '';

		return [
			'id'        => $id,
			'name'      => (string) $product->get_name(),
			'url'       => $link,
			'image'     => $image,
			'price'     => $price,
			'priceHtml' => $html,
			'inStock'   => $this->in_stock( $product ),
			'kind'      => $kind,
			'brand'     => $this->brand_of( $id ),
		];
	}

	/**
	 * The store's own brand term for a product, when the taxonomy exists.
	 *
	 * @param int $id Product ID.
	 * @return string|null
	 */
	private function brand_of( int $id ): ?string {
		if ( ! function_exists( 'taxonomy_exists' ) || ! function_exists( 'get_the_terms' ) || ! taxonomy_exists( 'product_brand' ) ) {
			return null;
		}
		$terms = get_the_terms( $id, 'product_brand' );
		if ( ! is_array( $terms ) || ! $terms ) {
			return null;
		}
		$first = reset( $terms );
		return is_object( $first ) && isset( $first->name ) ? (string) $first->name : null;
	}

	/**
	 * Store a transient and remember its key for the purge action.
	 *
	 * @param string               $key     Transient key.
	 * @param array<string, mixed> $payload Rows plus the withhold report.
	 * @param int                  $ttl     Seconds.
	 */
	private function remember( string $key, array $payload, int $ttl ): void {
		set_transient( $key, $payload, $ttl );
		if ( ! function_exists( 'get_option' ) ) {
			return;
		}
		$index = get_option( 'ts_charge_guide_cache_index', [] );
		$index = is_array( $index ) ? $index : [];
		if ( ! isset( $index[ $key ] ) ) {
			$index[ $key ] = time();
			// Bounded index: keep the newest entries only.
			if ( count( $index ) > 40 ) {
				$index = array_slice( $index, -40, null, true );
			}
			update_option( 'ts_charge_guide_cache_index', $index, false );
		}
	}

	/**
	 * Drop every catalog transient this plugin created.
	 *
	 * @return int Number of transients deleted.
	 */
	public function flush_cache(): int {
		$this->memory   = [];
		$this->withheld = [];
		if ( ! function_exists( 'get_option' ) ) {
			return 0;
		}
		$index   = get_option( 'ts_charge_guide_cache_index', [] );
		$index   = is_array( $index ) ? $index : [];
		$deleted = 0;
		foreach ( array_keys( $index ) as $key ) {
			if ( is_string( $key ) && str_starts_with( $key, TS_CHARGE_GUIDE_CACHE_PREFIX ) ) {
				delete_transient( $key );
				$deleted++;
			}
		}
		delete_option( 'ts_charge_guide_cache_index' );
		return $deleted;
	}

	/**
	 * Public subset of a card, safe to hand to the browser.
	 *
	 * @param array<string, mixed> $row Catalog row.
	 * @return array<string, mixed>
	 */
	public static function public_row( array $row ): array {
		return [
			'id'        => (int) ( $row['id'] ?? 0 ),
			'name'      => (string) ( $row['name'] ?? '' ),
			'url'       => (string) ( $row['url'] ?? '' ),
			'image'     => isset( $row['image'] ) && is_string( $row['image'] ) ? $row['image'] : null,
			'priceHtml' => (string) ( $row['priceHtml'] ?? '' ),
			'inStock'   => (bool) ( $row['inStock'] ?? false ),
			'brand'     => isset( $row['brand'] ) && is_string( $row['brand'] ) ? $row['brand'] : null,
		];
	}
}
