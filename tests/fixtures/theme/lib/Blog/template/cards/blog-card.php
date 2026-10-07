<?php
/**
 * TEST DOUBLE for the theme's blog card partial
 * (lib/Blog/template/cards/blog-card.php).
 *
 * Same markup shape as the live card, read through the fake post loop.
 *
 * @package TSChargeGuide
 */

$post_id   = (int) get_the_ID();
$permalink = (string) get_the_permalink( $post_id );
$date      = (string) ( $GLOBALS['ts_cg_posts'][ $post_id ]['date'] ?? '2026-01-01 00:00:00' );
$persian   = explode( '-', wbsDate( 'Y-F-d', $date ) );
$level     = isset( $blogCardHeadingLevel ) && 2 === (int) $blogCardHeadingLevel ? 2 : 3;
?>
<article class="blog-row-post-card full-card shadow-bottom centered-flex justify-content-start column-flex">
	<div class="image">
		<time class="post-date column-flex" datetime="<?php echo esc_attr( $date ); ?>">
			<span class="day"><?php echo esc_html( $persian[2] ?? '' ); ?></span>
			<span class="month">
				<span><?php echo esc_html( $persian[1] ?? '' ); ?></span>
				<span><?php echo esc_html( $persian[0] ?? '' ); ?></span>
			</span>
		</time>
		<a href="<?php echo esc_url( $permalink ); ?>" target="_blank">
			<picture><?php the_post_thumbnail( 'medium' ); ?></picture>
		</a>
	</div>
	<div class="details">
		<h<?php echo (int) $level; ?> class="blog-card-title"><a class="two-line-string" href="<?php echo esc_url( $permalink ); ?>" target="_blank"><?php the_title(); ?></a></h<?php echo (int) $level; ?>>
		<p class="excerpt two-line-string"><?php echo esc_html( mb_substr( (string) get_the_excerpt(), 0, 100 ) ); ?>...</p>
		<a class="more-btn" href="<?php echo esc_url( $permalink ); ?>">ادامه مطلب</a>
	</div>
</article>
