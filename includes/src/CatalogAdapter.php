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
 * settings screen, and every field of a card (name, permalink, image, price,
 * stock) is read live. An unconfigured plugin returns no rows at all, so the
 * product grid simply does not render.
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
	 * Settings service.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Request-level cache.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private array $memory = [];

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
			$this->memory[ $kind ] = [];
			return [];
		}

		$limit = $this->settings->cards_per_kind();
		$key   = TS_CHARGE_GUIDE_CACHE_PREFIX . md5( TS_CHARGE_GUIDE_VERSION . '|' . $kind . '|' . $term_id . '|' . $limit );
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			$this->memory[ $kind ] = $cached;
			return $cached;
		}

		try {
			$rows = $this->query( $kind, $term_id, $limit );
			$this->remember( $key, $rows, TS_CHARGE_GUIDE_TRANSIENT_EXPIRY );
		} catch ( \Throwable $e ) {
			// A failing catalog must neither break the page nor be retried on
			// every render.
			$rows = [];
			$this->remember( $key, $rows, self::NEGATIVE_TTL );
		}

		$this->memory[ $kind ] = $rows;
		return $rows;
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
	 * @return array<int, array<string, mixed>>
	 */
	private function query( string $kind, int $term_id, int $limit ): array {
		if ( ! $this->woo_available() ) {
			return [];
		}
		$term = get_term( $term_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) || empty( $term->slug ) ) {
			return [];
		}

		$args = [
			'status'   => 'publish',
			'limit'    => $limit,
			'category' => [ (string) $term->slug ],
			'orderby'  => 'title',
			'order'    => 'ASC',
			'return'   => 'objects',
		];

		/**
		 * Filter the WooCommerce query the guide uses for one category.
		 *
		 * @param array<string, mixed> $args Query args.
		 * @param string               $kind 'powerbank' or 'charger'.
		 */
		$args = apply_filters( 'ts_charge_guide_catalog_args', $args, $kind );

		$products = wc_get_products( is_array( $args ) ? $args : [] );
		if ( ! is_array( $products ) ) {
			return [];
		}

		$rows = [];
		foreach ( $products as $product ) {
			if ( ! is_object( $product ) ) {
				continue;
			}
			$rows[] = $this->normalize( $product, $kind );
		}
		return $rows;
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
			'inStock'   => method_exists( $product, 'is_in_stock' ) ? (bool) $product->is_in_stock() : true,
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
	 * @param string                          $key  Transient key.
	 * @param array<int, array<string,mixed>> $rows Rows.
	 * @param int                             $ttl  Seconds.
	 */
	private function remember( string $key, array $rows, int $ttl ): void {
		set_transient( $key, $rows, $ttl );
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
		$this->memory = [];
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
