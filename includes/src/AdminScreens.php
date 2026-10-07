<?php
/**
 * Admin screens: settings page and the live catalog check.
 *
 * @package TSChargeGuide
 */

namespace TSChargeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the settings screen (Settings API form, capability-gated) with a
 * read-only check of what the guide's catalog currently returns.
 *
 * The screen is the only place the store's terms are named: the selects are
 * built from the shop's own product categories with their product counts.
 */
final class AdminScreens {

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Catalog adapter.
	 *
	 * @var CatalogAdapter
	 */
	private CatalogAdapter $catalog;

	/**
	 * Constructor.
	 *
	 * @param Settings       $settings Settings.
	 * @param CatalogAdapter $catalog  Catalog adapter.
	 */
	public function __construct( Settings $settings, CatalogAdapter $catalog ) {
		$this->settings = $settings;
		$this->catalog  = $catalog;
	}

	/**
	 * Register the menu under Settings.
	 */
	public function register_menu(): void {
		add_options_page(
			'راهنمای شارژ',
			'راهنمای شارژ',
			'manage_options',
			'ts-charge-guide',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Capability gate.
	 */
	private function guard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
	}

	/**
	 * Render the settings + live-check page.
	 */
	public function render_page(): void {
		$this->guard();
		$stored   = $this->settings->stored();
		$page_id  = (int) ( $stored['page_id'] ?? 0 );
		$terms    = $this->settings->category_terms();
		$cards    = $this->settings->cards_per_kind();
		$blog_ids = $this->settings->blog_ids();
		$broken   = $this->settings->has_broken_page();
		$woo      = $this->catalog->woo_available();
		$scoped   = $this->settings->has_product_scope();
		$pages    = get_pages( [ 'sort_column' => 'post_title', 'hierarchical' => 0 ] );
		$flushed  = isset( $_GET['ts_charge_flushed'] ) ? max( 0, (int) $_GET['ts_charge_flushed'] ) : -1;
		$flush_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=ts_charge_guide_flush' ),
			'ts_charge_guide_flush'
		);
		?>
<div class="wrap">
	<h1>راهنمای شارژ</h1>
	<?php if ( -1 !== $flushed ) : ?>
		<div class="notice notice-success is-dismissible"><p>کش محصولات راهنما پاک شد (<?php echo (int) $flushed; ?> رکورد).</p></div>
	<?php endif; ?>
	<?php if ( ! $woo ) : ?>
		<div class="notice notice-error"><p>WooCommerce فعال نیست؛ بخش محصولات راهنما نمایش داده نمی‌شود.</p></div>
	<?php endif; ?>
	<?php if ( $woo && ! $scoped ) : ?>
		<div class="notice notice-warning"><p>هنوز هیچ دسته‌بندی محصولی به راهنما اختصاص نداده‌ای؛ تا زمانی که دسته‌ها را انتخاب نکنی، بخش «آشنایی با چند انتخاب» روی سایت رندر نمی‌شود.</p></div>
	<?php endif; ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'ts_charge_guide' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="ts-charge-page">صفحه راهنما</label></th>
				<td>
					<select id="ts-charge-page" name="<?php echo esc_attr( TS_CHARGE_GUIDE_OPTION ); ?>[page_id]">
						<option value="0">— انتخاب نشده —</option>
						<?php foreach ( $pages as $page ) : ?>
							<option value="<?php echo esc_attr( (string) $page->ID ); ?>" <?php selected( $page_id, (int) $page->ID ); ?>><?php echo esc_html( get_the_title( $page ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description">محتوای این صفحه با راهنمای شارژ جایگزین می‌شود؛ سرصفحه و پاصفحه قالب باقی می‌مانند. در هر صفحه دیگری می‌توانی از شورت‌کد <code>[ts_charge_guide]</code> استفاده کنی.</p>
					<?php if ( $broken ) : ?>
						<p class="description" style="color:#c92b55;">صفحه انتخاب‌شده حذف شده یا منتشر نیست؛ در نمایش عمومی نادیده گرفته می‌شود.</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ts-charge-powerbank">دسته‌بندی پاوربانک</label></th>
				<td>
					<?php $this->term_select( 'powerbank_term', (int) $terms['powerbank'] ); ?>
					<p class="description">فقط محصولات <strong>منتشرشده و موجود</strong> همین دسته در راهنما نمایش داده می‌شوند.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ts-charge-charger">دسته‌بندی شارژر</label></th>
				<td>
					<?php $this->term_select( 'charger_term', (int) $terms['charger'] ); ?>
					<p class="description">پیشنهاد نتیجهٔ راهنما هم از همین دو دسته انتخاب می‌شود و همیشه موجود است؛ عدد یا محصولی در کد نوشته نشده است.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ts-charge-cards">تعداد کارت هر دسته</label></th>
				<td>
					<input id="ts-charge-cards" type="number" min="1" max="<?php echo (int) Settings::MAX_CARDS; ?>" step="1" name="<?php echo esc_attr( TS_CHARGE_GUIDE_OPTION ); ?>[cards_per_kind]" value="<?php echo esc_attr( (string) $cards ); ?>">
					<p class="description">سقف تعداد کارت هر دسته‌بندی (۱ تا <?php echo (int) Settings::MAX_CARDS; ?>)؛ برای کندی سایت عدد را کم کن.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ts-charge-blog-ids">نوشته‌های مجله</label></th>
				<td>
					<textarea id="ts-charge-blog-ids" name="<?php echo esc_attr( TS_CHARGE_GUIDE_OPTION ); ?>[blog_ids]" rows="3" class="large-text code" placeholder="288405, 292114"><?php echo esc_textarea( implode( ', ', $blog_ids ) ); ?></textarea>
					<p class="description">شناسهٔ نوشته‌هایی که زیر بخش باتری به‌شکل کارت مجله (کارت خودِ قالب) نمایش داده می‌شوند — با کاما یا در خط تازه، حداکثر <?php echo (int) Settings::MAX_BLOG_IDS; ?> مورد و به همین ترتیب. خالی بگذاری، آن بخش اصلاً رندر نمی‌شود.</p>
				</td>
			</tr>
		</table>
		<?php submit_button( 'ذخیره تنظیمات' ); ?>
	</form>

	<h2>بررسی لحظه‌ای</h2>
	<table class="widefat striped" role="presentation">
		<tbody>
			<tr><th scope="row">مسیر REST</th><td><code><?php echo esc_html( rest_url( TS_CHARGE_GUIDE_REST_BASE . '/recommend' ) ); ?></code> — بدون کش (<code>no-store</code>)</td></tr>
			<tr><th scope="row">WooCommerce</th><td><?php echo $woo ? 'فعال' : 'غیرفعال'; ?></td></tr>
			<tr><th scope="row">دسته‌های اختصاص‌یافته</th><td><?php echo $scoped ? 'دارد' : 'ندارد (بخش محصولات رندر نمی‌شود)'; ?></td></tr>
		</tbody>
	</table>
	<p><a class="button" href="<?php echo esc_url( $flush_url ); ?>">پاک کردن کش محصولات راهنما</a></p>
	<?php
	$this->catalog_preview();
	$this->reading_preview();
	?>
</div>
		<?php
	}

	/**
	 * Read-only preview of the magazine cards the settings would render now.
	 *
	 * A listed ID that no longer resolves is called out here rather than
	 * silently missing from the page.
	 */
	private function reading_preview(): void {
		$ids = $this->settings->blog_ids();
		?>
	<h2>بخش مجله</h2>
		<?php if ( ! $ids ) : ?>
		<p class="description">هیچ نوشته‌ای انتخاب نشده؛ بخش مجلهٔ راهنما رندر نمی‌شود.</p>
			<?php
			return;
		endif;
		?>
	<table class="widefat striped">
		<thead><tr><th>شناسه</th><th>نوشته</th><th>نوع</th><th>تصویر شاخص</th><th>ویرایش</th></tr></thead>
		<tbody>
		<?php foreach ( $ids as $id ) : ?>
			<?php
			$post   = function_exists( 'get_post' ) ? get_post( (int) $id ) : null;
			$type   = is_object( $post ) && isset( $post->post_type ) ? (string) $post->post_type : '';
			$status = is_object( $post ) && isset( $post->post_status ) ? (string) $post->post_status : 'publish';
			$ok     = is_object( $post ) && in_array( $type, [ 'post', 'page' ], true ) && 'publish' === $status;
			?>
			<tr>
				<td><code><?php echo (int) $id; ?></code></td>
				<td><?php echo $ok ? esc_html( get_the_title( (int) $id ) ) : '<em>یافت نشد</em>'; ?></td>
				<td><?php echo $ok ? esc_html( $type ) : '—'; ?></td>
				<td><?php echo $ok && function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( (int) $id ) ? 'دارد' : 'ندارد'; ?></td>
				<td><?php echo $ok && function_exists( 'get_edit_post_link' ) ? '<a href="' . esc_url( (string) get_edit_post_link( (int) $id ) ) . '">ویرایش</a>' : '—'; ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">هر شناسه‌ای که «یافت نشد» است یا منتشر نشده، در صفحه نمایش داده نمی‌شود.</p>
		<?php
	}

	/**
	 * Read-only preview of what the guide would show right now.
	 */
	private function catalog_preview(): void {
		if ( ! $this->catalog->woo_available() ) {
			return;
		}
		?>
	<h2>محصولاتی که همین حالا نمایش داده می‌شوند</h2>
	<p class="description">قاعده: فقط محصولاتی که ووکامرس «موجود» می‌داند. ناموجودها پنهان می‌شوند و تعدادشان اینجا گزارش می‌شود.</p>
		<?php foreach ( [ 'powerbank' => 'پاوربانک', 'charger' => 'شارژر' ] as $kind => $label ) : ?>
			<?php
			$rows     = $this->catalog->by_kind( $kind );
			$withheld = $this->catalog->withheld( $kind );
			?>
			<h3>
				<?php echo esc_html( $label ); ?>
				<span class="description">
					(<?php echo count( $rows ); ?> موجود
					<?php if ( (int) ( $withheld['stock'] ?? 0 ) > 0 ) : ?>
						· <?php echo (int) $withheld['stock']; ?> ناموجود پنهان شد
					<?php endif; ?>
					)
				</span>
			</h3>
			<?php if ( ! $rows ) : ?>
				<p class="description">دسته‌ای انتخاب نشده، یا هیچ محصول موجودی در آن نیست.</p>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr><th>محصول</th><th>قیمت</th><th>موجودی</th><th>تصویر</th><th>ویرایش</th></tr></thead>
					<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $row['name'] ); ?> <code>#<?php echo (int) $row['id']; ?></code></td>
							<td><?php echo wp_kses_post( (string) $row['priceHtml'] ); ?></td>
							<td><?php echo ! empty( $row['inStock'] ) ? 'موجود' : 'ناموجود'; ?></td>
							<td><?php echo ! empty( $row['image'] ) ? 'دارد' : '<span style="color:#c92b55;">ندارد</span>'; ?></td>
							<td><a href="<?php echo esc_url( (string) get_edit_post_link( (int) $row['id'] ) ); ?>">ویرایش محصول ↗</a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * admin_post handler: drop the catalog transients.
	 */
	public function handle_flush_cache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		check_admin_referer( 'ts_charge_guide_flush' );
		$deleted = $this->catalog->flush_cache();
		wp_safe_redirect(
			add_query_arg(
				[ 'page' => 'ts-charge-guide', 'ts_charge_flushed' => $deleted ],
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Render a product-category term select from the store's own tree.
	 *
	 * @param string $key   Settings key.
	 * @param int    $value Current value.
	 */
	private function term_select( string $key, int $value ): void {
		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
			]
		);
		?>
<select id="ts-charge-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( TS_CHARGE_GUIDE_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]">
	<option value="0" <?php selected( $value, 0 ); ?>>— انتخاب نشده —</option>
		<?php if ( is_array( $terms ) ) : ?>
			<?php foreach ( $terms as $term ) : ?>
				<?php if ( ! is_object( $term ) || ! isset( $term->term_id ) ) : ?>
					<?php continue; ?>
				<?php endif; ?>
<option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( $value, (int) $term->term_id ); ?>><?php echo esc_html( (string) $term->name ); ?> (<?php echo (int) $term->count; ?>)</option>
			<?php endforeach; ?>
		<?php endif; ?>
</select>
		<?php
	}
}
