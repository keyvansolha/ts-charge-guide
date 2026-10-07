<?php
/**
 * Recommendation: builds the wizard result from answers and live products.
 *
 * @package TSChargeGuide
 */

namespace TSChargeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Pure mapping from validated answers plus a catalog snapshot to the result
 * payload. No WordPress API, no fixed product id: the picks are whatever the
 * live catalog returned for the configured categories.
 */
final class Recommendation {

	/**
	 * How many cards a result may show.
	 */
	public const MAX_PICKS = 3;

	/**
	 * Whether an answer set is one the wizard can produce.
	 *
	 * @param array<string, mixed> $answers Answers.
	 * @return bool
	 */
	public static function valid( array $answers ): bool {
		$need     = $answers['need'] ?? null;
		$device   = $answers['device'] ?? null;
		$priority = $answers['priority'] ?? null;
		if ( ! is_string( $need ) || ! isset( Content::needs()[ $need ] ) ) {
			return false;
		}
		if ( ! is_string( $device ) || ! isset( Content::devices()[ $device ] ) ) {
			return false;
		}
		if ( ! is_string( $priority ) ) {
			return false;
		}
		foreach ( Content::priorities( $need ) as $row ) {
			if ( $row['key'] === $priority ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Build the result payload.
	 *
	 * @param array<string, mixed>            $answers  Validated answers.
	 * @param array<int, array<string, mixed>> $catalog Catalog rows.
	 * @return array<string, mixed>
	 */
	public static function build( array $answers, array $catalog ): array {
		$need     = (string) $answers['need'];
		$device   = (string) $answers['device'];
		$priority = (string) $answers['priority'];

		$copy   = Content::priority_copy( $need, $priority );
		$advice = Content::device_advice( $device );

		$checklist = [ $advice['title'] . ' — ' . $advice['body'] ];
		foreach ( Content::checklist() as $line ) {
			$checklist[] = $line;
		}

		$picks = $copy['show_products']
			? self::picks( $need, $priority, $catalog )
			: [];

		return [
			'need'      => $need,
			'device'    => $device,
			'priority'  => $priority,
			'heading'   => $copy['heading'],
			'lead'      => $copy['lead'],
			'checklist' => $checklist,
			'warning'   => Content::result_warning(),
			'products'  => $picks,
			'certifies' => false,
		];
	}

	/**
	 * Pick cards for a result: the configured categories, in stock first, then
	 * cheapest first. Nothing is claimed about compatibility; the warning
	 * above the picks says so.
	 *
	 * @param string                          $need     Need key.
	 * @param string                          $priority Priority key.
	 * @param array<int, array<string, mixed>> $catalog Catalog rows.
	 * @return array<int, array<string, mixed>>
	 */
	private static function picks( string $need, string $priority, array $catalog ): array {
		$wanted = 'charger' === $need ? [ 'charger' ] : ( 'both' === $need ? [ 'powerbank', 'charger' ] : [ 'powerbank' ] );

		$rows = array_values(
			array_filter(
				$catalog,
				static fn( array $row ): bool => in_array( (string) ( $row['kind'] ?? '' ), $wanted, true )
			)
		);

		usort(
			$rows,
			static function ( array $a, array $b ): int {
				$stock = (int) (bool) ( $b['inStock'] ?? false ) <=> (int) (bool) ( $a['inStock'] ?? false );
				if ( 0 !== $stock ) {
					return $stock;
				}
				$price = (float) ( $a['price'] ?? 0.0 ) <=> (float) ( $b['price'] ?? 0.0 );
				if ( 0 !== $price ) {
					return $price;
				}
				return strnatcasecmp( (string) ( $a['name'] ?? '' ), (string) ( $b['name'] ?? '' ) );
			}
		);

		// 'both' shows one of each kind; a single-kind need shows up to MAX_PICKS.
		if ( 'both' === $need ) {
			$per_kind = [];
			foreach ( $rows as $row ) {
				$kind = (string) $row['kind'];
				$per_kind[ $kind ][] = $row;
			}
			$picked = [];
			foreach ( $wanted as $kind ) {
				if ( ! empty( $per_kind[ $kind ][0] ) ) {
					$picked[] = $per_kind[ $kind ][0];
				}
			}
			$rows = $picked;
		}

		$rows = array_slice( $rows, 0, self::MAX_PICKS );
		return array_map( [ CatalogAdapter::class, 'public_row' ], $rows );
	}
}
