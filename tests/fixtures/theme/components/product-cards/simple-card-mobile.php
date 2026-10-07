<?php
/**
 * TEST DOUBLE for the theme's mobile product card partial
 * (inc/template/components/product-cards/simple-card-mobile.php).
 *
 * The theme picks this variant with `IS_MOBILE` (a user-agent test). The harness
 * runs as a desktop, so the desktop double is the one exercised; this file exists
 * so the guide's device switch resolves to a real partial in both modes.
 *
 * @package TSChargeGuide
 */

$id        = (int) $product->get_id();
$name      = (string) $product->get_name();
$permalink = (string) $product->get_permalink();
$image_id  = (int) $product->get_image_id();
$image     = '' !== (string) $image_id ? wp_get_attachment_image( $image_id, 'thumbnail', false, [ 'alt' => $name ] ) : '';
$in_stock  = $product->is_in_stock();
?>
<article class="product-simple-mobile-card">
	<div class="product-simple-card-item has-favorite" data-id="<?php echo (int) $id; ?>">
		<div class="image-panel">
			<div class="top-panel centered-flex justify-content-between">
				<button type="button" class="favorite-btn simple-btn add-to-favorite centered-flex" aria-label="افزودن به علاقه‌مندی‌ها"><i class="icon-heart" aria-hidden="true"></i></button>
			</div>
			<div class="image text-center">
				<a class="brand centered-flex" href="<?php echo esc_url( $permalink ); ?>">
					<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped in the shim. ?>
				</a>
			</div>
			<div class="bottom-panel centered-flex justify-content-between">
				<div class="wbs-badge centered-flex"><i class="icon-express"></i>ارسال سریع</div>
			</div>
		</div>
		<div class="details">
			<header>
				<a href="<?php echo esc_url( $permalink ); ?>"><h2 class="one-line-string primary-title"><?php echo esc_html( $name ); ?></h2></a>
			</header>
			<div class="footer centered-flex justify-content-between align-items-end">
				<?php if ( ! $in_stock ) : ?>
					<div class="product-price no-stock centered-flex">ناموجود</div>
				<?php else : ?>
					<div class="product-price centered-flex"><?php echo wp_kses_post( (string) $product->get_price_html() ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</article>
