<?php
/**
 * WooCommerce shims: a small fake store for the catalog-adapter tests.
 *
 * Mirrors the narrow surface CatalogAdapter reads: wc_get_products (with the
 * category-slug argument WooCommerce documents), wc_get_product,
 * wc_get_price_to_display, plus the WP helpers it calls.
 */

declare(strict_types=1);

$GLOBALS['ts_cg_products'] = [];

/** Fake WC_Product carrying only the getters the adapter uses. */
final class TS_CG_Product_Stub {

	/** @var array<string, mixed> */
	public array $props;

	/** @param array<string, mixed> $props Properties. */
	public function __construct( array $props ) {
		$this->props = $props;
	}

	/** Product ID. */
	public function get_id(): int {
		return (int) $this->props['id'];
	}

	/** Product name. */
	public function get_name(): string {
		return (string) $this->props['name'];
	}

	/** Featured image attachment ID. */
	public function get_image_id( string $context = '' ): int {
		return (int) $this->props['image_id'];
	}

	/** Stock state. */
	public function is_in_stock(): bool {
		return (bool) $this->props['in_stock'];
	}

	/** Display price markup, as WooCommerce renders it. */
	public function get_price_html(): string {
		return (string) $this->props['price_html'];
	}

	/** Canonical product URL. */
	public function get_permalink(): string {
		return (string) $this->props['permalink'];
	}
}

/**
 * Register a fake product in one or more category slugs.
 *
 * @param int                  $id    Product ID.
 * @param array<string, mixed> $props Properties (name, price, cats…).
 */
function ts_cg_product( int $id, array $props ): void {
	$GLOBALS['ts_cg_products'][ $id ] = array_merge(
		[
			'id'         => $id,
			'name'       => 'Product ' . $id,
			'status'     => 'publish',
			'cats'       => [],
			'price'      => 1000.0,
			'price_html' => '<span class="woocommerce-Price-amount">۱٬۰۰۰ تومان</span>',
			'in_stock'   => true,
			'image_id'   => $id,
			'permalink'  => 'https://store.example/product/' . $id . '/',
		],
		$props
	);
}

/**
 * WooCommerce product query. Honors status, category slugs and limit, and
 * records every query so the tests can prove caching.
 *
 * @param array<string, mixed> $args Query args.
 * @return array<int, object>
 */
function wc_get_products( array $args ) {
	$GLOBALS['ts_cg_query_log'][] = $args;

	$slugs  = array_map( 'strval', (array) ( $args['category'] ?? [] ) );
	$status = (array) ( $args['status'] ?? [ 'publish' ] );
	$limit  = isset( $args['limit'] ) ? (int) $args['limit'] : -1;

	$out = [];
	foreach ( $GLOBALS['ts_cg_products'] as $product ) {
		if ( ! in_array( (string) $product['status'], $status, true ) ) {
			continue;
		}
		if ( $slugs && ! array_intersect( $slugs, array_map( 'strval', (array) $product['cats'] ) ) ) {
			continue;
		}
		$out[] = new TS_CG_Product_Stub( $product );
	}

	usort( $out, static fn( $a, $b ): int => strnatcasecmp( $a->get_name(), $b->get_name() ) );

	if ( $limit > 0 ) {
		$out = array_slice( $out, 0, $limit );
	}
	return $out;
}

/** Single product lookup. */
function wc_get_product( $id ) {
	if ( isset( $GLOBALS['ts_cg_products'][ (int) $id ] ) ) {
		return new TS_CG_Product_Stub( $GLOBALS['ts_cg_products'][ (int) $id ] );
	}
	return false;
}

/** Display price for a product. */
function wc_get_price_to_display( $product, int $qty = 1 ): float {
	return (float) $product->props['price'];
}

/** Attachment URL. */
function wp_get_attachment_image_url( $id, $size = '' ) {
	return $id > 0 ? 'https://store.example/img/' . (int) $id . '.webp' : false;
}

/** Brand taxonomy is present in the fake store. */
function taxonomy_exists( string $taxonomy ): bool {
	return 'product_brand' === $taxonomy;
}
