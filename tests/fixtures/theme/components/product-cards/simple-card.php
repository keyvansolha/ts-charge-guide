<?php
/**
 * TEST DOUBLE for the theme's product card partial
 * (inc/template/components/product-cards/simple-card.php).
 *
 * It emits the same markup the live theme card emits — captured from
 * https://www.tehranspeaker.com/shop/ — so the harness and the static preview
 * can render the guide's grid without a WordPress runtime. The real partial
 * reads WooCommerce variations and theme classes; those parts are not needed to
 * verify the guide's markup, escaping, filtering and layout.
 *
 * @package TSChargeGuide
 */

$props        = isset( $product->props ) ? $product->props : [];
$id           = (int) $product->get_id();
$name         = (string) $product->get_name();
$permalink    = (string) $product->get_permalink();
$image_id     = (int) $product->get_image_id();
$image        = '' !== (string) $image_id ? wp_get_attachment_image( $image_id, 'thumbnail', false, [ 'alt' => $name ] ) : '';
$price_html   = (string) $product->get_price_html();
$sub_title    = (string) get_post_meta( $id, 'h2-title', true );
$badge        = (string) get_post_meta( $id, 'badge-title', true );
$brands       = get_the_terms( $id, 'product_brand' );
$brand_name   = is_array( $brands ) && isset( $brands[0] ) ? (string) $brands[0]->name : '';
$brand_slug   = '' === $brand_name ? '' : strtolower( preg_replace( '/[^a-zA-Z0-9]+/', '-', $brand_name ) );
$brand_link   = '' === $brand_slug ? '' : '/product_brand/' . $brand_slug . '/';
$color_list   = isset( $props['colors'] ) ? (array) $props['colors'] : [];
$in_stock     = $product->is_in_stock();
$filter       = isset( $filter ) && is_array( $filter ) ? $filter : [];
$activeCompare = ! empty( $filter['activeCompare'] );
$args          = isset( $args ) && is_array( $args ) ? $args : [];
?>
<article class="product-simple-card">
	<div class="product-simple-card-item has-favorite" data-id="<?php echo (int) $id; ?>">
		<?php if ( $activeCompare ) : ?>
			<button type="button" class="compare-btn add-to-compare simple-btn centered-flex" aria-pressed="false"><span class="compare-icon" aria-hidden="true"><i class="icon-plus compare-icon-add"></i></span><span class="compare-label compare-label-add">افزودن به مقایسه</span></button>
		<?php endif; ?>
		<div class="image-panel">
			<div class="top-panel centered-flex justify-content-between">
				<button type="button" class="favorite-btn simple-btn add-to-favorite centered-flex" data-side="left" data-tooltip="افزودن به علاقه‌مندی‌ها" aria-label="افزودن به علاقه‌مندی‌ها"><i class="icon-heart" aria-hidden="true"></i></button>
				<?php if ( '' !== $brand_link ) : ?>
					<a target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $brand_name ); ?>" data-tooltip="<?php echo esc_attr( $brand_name ); ?>" data-side="bottom" class="brand centered-flex" href="<?php echo esc_url( $brand_link ); ?>"><i class="<?php echo esc_attr( 'icon-' . $brand_slug . '-logo' ); ?>" aria-hidden="true"></i></a>
				<?php endif; ?>
			</div>
			<div class="image text-center">
				<a class="brand centered-flex" href="<?php echo esc_url( $permalink ); ?>">
					<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped in the shim. ?>
				</a>
			</div>
			<div class="bottom-panel centered-flex justify-content-between">
				<div class="wbs-badge centered-flex">
					<?php if ( '' !== $badge ) : ?>
						<i class="icon-star"></i><?php echo esc_html( $badge ); ?>
					<?php else : ?>
						<i class="icon-express"></i>ارسال سریع
					<?php endif; ?>
				</div>
				<div class="colors centered-flex">
					<?php foreach ( array_slice( $color_list, 0, 6 ) as $color ) : ?>
						<span class="<?php echo esc_attr( (string) $color ); ?>"></span>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<div class="details">
			<header>
				<a href="<?php echo esc_url( $permalink ); ?>">
					<h2 class="one-line-string primary-title"><?php echo esc_html( $name ); ?></h2>
					<h3 class="one-line-string sub-title"><?php echo esc_html( $sub_title ); ?></h3>
				</a>
			</header>
			<div class="footer centered-flex justify-content-between align-items-end">
				<a class="centered-flex" aria-label="مشاهده" href="<?php echo esc_url( $permalink ); ?>"><i class="icon-eye wbs-badge"></i>مشاهده</a>
				<?php if ( ! $in_stock ) : ?>
					<div class="product-price no-stock centered-flex align-items-end justify-content-end">ناموجود</div>
				<?php else : ?>
					<div class="product-price centered-flex align-items-end justify-content-end"><?php echo wp_kses_post( $price_html ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</article>
