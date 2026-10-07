<?php
/**
 * Settings service: storage, defaults, Settings API registration.
 *
 * @package TSChargeGuide
 */

namespace TSChargeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Stores one plugin option: the landing page, the two product-category terms
 * the guide may show, and the per-kind card bound.
 *
 * No product, term name, slug or id is written into the source: the terms are
 * chosen by the owner from the store's own category tree on the settings
 * screen, and an unconfigured plugin classifies nothing.
 */
final class Settings {

	/**
	 * Integer fields, in sanitize order.
	 */
	private const INT_FIELDS = [ 'page_id', 'powerbank_term', 'charger_term', 'cards_per_kind' ];

	/**
	 * Highest accepted card count per kind.
	 */
	public const MAX_CARDS = 12;

	/**
	 * Highest accepted number of magazine posts under the battery section.
	 */
	public const MAX_BLOG_IDS = 6;

	/**
	 * In-memory cache of the merged option value.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $cache = null;

	/**
	 * Default settings keyed by field.
	 *
	 * Everything resolution-dependent starts empty on purpose: nothing is shown
	 * until the owner assigns the store's real category terms and the magazine
	 * posts they want. No post, term or product is ever named in the source.
	 *
	 * @return array<string, mixed> Defaults.
	 */
	public function defaults(): array {
		return [
			'page_id'        => 0,
			'powerbank_term' => 0,
			'charger_term'   => 0,
			'cards_per_kind' => 6,
			'blog_ids'       => [],
		];
	}

	/**
	 * Read the plugin option merged with defaults.
	 *
	 * @return array<string, mixed> Current settings.
	 */
	public function all(): array {
		if ( null === $this->cache ) {
			$stored      = get_option( TS_CHARGE_GUIDE_OPTION, [] );
			$stored      = is_array( $stored ) ? $stored : [];
			$clean       = $this->defaults();
			foreach ( self::INT_FIELDS as $key ) {
				if ( isset( $stored[ $key ] ) && is_numeric( $stored[ $key ] ) ) {
					$clean[ $key ] = max( 0, (int) $stored[ $key ] );
				}
			}
			$clean['blog_ids'] = $this->normalize_ids( $stored['blog_ids'] ?? '' );
			$this->cache       = $clean;
		}
		return $this->cache;
	}

	/**
	 * Raw stored option (no default merge) for the settings form.
	 *
	 * @return array<string, mixed>
	 */
	public function stored(): array {
		$stored = get_option( TS_CHARGE_GUIDE_OPTION, [] );
		return is_array( $stored ) ? $stored : [];
	}

	/**
	 * Value for one integer field.
	 *
	 * @param string $key Field key.
	 * @return int
	 */
	public function get( string $key ): int {
		$all = $this->all();
		return isset( $all[ $key ] ) && is_numeric( $all[ $key ] ) ? (int) $all[ $key ] : 0;
	}

	/**
	 * The magazine posts the owner listed, in their order.
	 *
	 * @return array<int, int> Post IDs.
	 */
	public function blog_ids(): array {
		$all = $this->all();
		return array_map( 'intval', (array) ( $all['blog_ids'] ?? [] ) );
	}

	/**
	 * Turn whatever the form submitted into a bounded list of post IDs.
	 *
	 * Accepts a string (comma, space or newline separated, Persian digits
	 * included) or an array. Duplicates collapse, non-numbers are dropped and
	 * the list is capped, so a paste of a whole export cannot bloat a page.
	 *
	 * @param mixed $raw Submitted value.
	 * @return array<int, int>
	 */
	public function normalize_ids( $raw ): array {
		if ( is_array( $raw ) ) {
			$raw = implode( ',', array_map( 'strval', $raw ) );
		}
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return [];
		}
		$latin = strtr(
			$raw,
			[
				'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
				'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
				'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
				'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
			]
		);
		$ids = [];
		foreach ( preg_split( '/[^0-9]+/', $latin ) ?: [] as $part ) {
			if ( '' === $part ) {
				continue;
			}
			$id = (int) $part;
			if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
			if ( count( $ids ) >= self::MAX_BLOG_IDS ) {
				break;
			}
		}
		return $ids;
	}

	/**
	 * Sanitize callback for the Settings API. Numeric fields only; a missing or
	 * non-numeric field keeps its current value, so a partial save can never
	 * wipe another setting. The magazine list is normalised instead of kept,
	 * because an emptied field means "no magazine section".
	 *
	 * @param mixed $input Raw submitted settings.
	 * @return array<string, mixed> Sanitized settings.
	 */
	public function sanitize( $input ): array {
		$current = $this->all();
		$clean   = [];
		foreach ( self::INT_FIELDS as $key ) {
			$value = is_array( $input ) ? ( $input[ $key ] ?? null ) : null;
			if ( is_numeric( $value ) ) {
				$clean[ $key ] = max( 0, (int) $value );
			} else {
				$clean[ $key ] = (int) ( $current[ $key ] ?? 0 );
			}
		}
		$clean['cards_per_kind'] = $this->clamp_cards( (int) $clean['cards_per_kind'] );
		$clean['blog_ids']       = $this->normalize_ids( is_array( $input ) ? ( $input['blog_ids'] ?? null ) : null );
		$this->cache             = null;
		return $clean;
	}

	/**
	 * Clamp the card bound into its documented domain (1–MAX_CARDS); an
	 * out-of-range or zero value falls back to the default instead of
	 * emptying the grid.
	 *
	 * @param int $value Candidate value.
	 * @return int
	 */
	public function clamp_cards( int $value ): int {
		if ( $value < 1 || $value > self::MAX_CARDS ) {
			return (int) $this->defaults()['cards_per_kind'];
		}
		return $value;
	}

	/**
	 * Register the option with the Settings API.
	 */
	public function register_settings(): void {
		register_setting(
			'ts_charge_guide',
			TS_CHARGE_GUIDE_OPTION,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize' ],
				'default'           => $this->defaults(),
				'show_in_rest'      => false,
			]
		);
	}

	/**
	 * Register cache-invalidation hooks after option writes.
	 */
	public function register_hooks(): void {
		add_action( 'update_option_' . TS_CHARGE_GUIDE_OPTION, [ $this, 'invalidate' ], 10, 0 );
		add_action( 'add_option_' . TS_CHARGE_GUIDE_OPTION, [ $this, 'invalidate' ], 10, 0 );
	}

	/**
	 * Drop the in-memory cache after a write.
	 */
	public function invalidate(): void {
		$this->cache = null;
	}

	/**
	 * The selected landing page ID when it refers to a public page.
	 *
	 * A deleted or non-public page is ignored (flagged in settings instead).
	 *
	 * @return int Page ID or 0 when unconfigured/invalid.
	 */
	public function landing_page_id(): int {
		$page_id = $this->get( 'page_id' );
		if ( $page_id < 1 ) {
			return 0;
		}
		$page = function_exists( 'get_post' ) ? get_post( $page_id ) : null;
		if ( ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
			return 0;
		}
		return $page_id;
	}

	/**
	 * Whether the settings screen should warn about a broken page selection.
	 *
	 * @return bool True when a page is selected but not usable.
	 */
	public function has_broken_page(): bool {
		return $this->get( 'page_id' ) > 0 && $this->landing_page_id() === 0;
	}

	/**
	 * Product-category term IDs as [kind => term_id].
	 *
	 * @return array<string, int>
	 */
	public function category_terms(): array {
		return [
			'powerbank' => $this->get( 'powerbank_term' ),
			'charger'   => $this->get( 'charger_term' ),
		];
	}

	/**
	 * Card bound per kind (1–MAX_CARDS).
	 *
	 * @return int
	 */
	public function cards_per_kind(): int {
		return $this->clamp_cards( $this->get( 'cards_per_kind' ) );
	}

	/**
	 * Whether any product category is assigned.
	 *
	 * @return bool
	 */
	public function has_product_scope(): bool {
		foreach ( $this->category_terms() as $term_id ) {
			if ( $term_id > 0 ) {
				return true;
			}
		}
		return false;
	}
}
