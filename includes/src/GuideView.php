<?php
/**
 * Guide view: renders the guide markup.
 *
 * @package TSChargeGuide
 */

namespace TSChargeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Component view. Every dynamic value is escaped for its output context; the
 * only product data on the page comes from the catalog snapshot passed in.
 *
 * The first state of every interactive region is rendered here, so the page
 * carries the full guide content without JavaScript: the browser script only
 * switches between the panels that are already in the document.
 */
final class GuideView {

	/**
	 * Public bootstrap configuration.
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Catalog rows for this request.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $catalog;

	/**
	 * Whether the product section has anything to show.
	 *
	 * @var bool
	 */
	private bool $has_products;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed>            $config  Bootstrap config.
	 * @param array<int, array<string, mixed>> $catalog Catalog rows.
	 */
	public function __construct( array $config, array $catalog ) {
		$this->config       = $config;
		$this->catalog      = $catalog;
		$this->has_products = [] !== $catalog;
	}

	/**
	 * Render the guide.
	 *
	 * @return string Escaped HTML.
	 */
	public function render(): string {
		// Only what the browser needs: the magazine ids are server-side input and
		// have no business in the page.
		$browser_config = $this->config;
		unset( $browser_config['blogIds'] );
		$json = wp_json_encode( $browser_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		$json = false === $json ? '{}' : $json;

		ob_start();
		?>
<div class="ts-charge" id="ts-charge" dir="rtl" lang="fa">
	<a class="cg-skip" href="#cg-journey">رفتن به راهنمای انتخاب</a>
	<?php echo $this->hero(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
	<div class="cg-reassurance cg-wrap"><span>لازم نیست همیشه قوی‌ترین را بخری.</span><span>کابل مناسب هم مهم است.</span><span>اول وسایلی که داری را بررسی کنیم.</span></div>
	<?php
		echo $this->journey(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		echo $this->laptop(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		echo $this->care(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		echo $this->help(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		echo $this->products(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		echo $this->faq(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	?>
	<section class="cg-closing cg-wrap">
		<div><span class="cg-eyebrow">یک انتخاب خوب، از نیاز خودت شروع می‌شود.</span><h2>همراهت باشد. به‌اندازه نیازت.</h2></div>
		<button class="cg-button cg-button--primary" data-cg-restart>انتخابم را پیدا کنم</button>
	</section>
</div>
<script type="application/json" id="ts-charge-config"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-hex encoded. ?></script>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The hero section.
	 *
	 * @return string
	 */
	private function hero(): string {
		$hero = Content::hero();
		ob_start();
		?>
	<section class="cg-hero cg-wrap">
		<div class="cg-hero-copy">
			<span class="cg-eyebrow">پاوربانک و شارژر، به زبان ساده</span>
			<h1>شارژ همراهت.<br><span>خیالت راحت‌تر.</span></h1>
			<p class="cg-lead">برای انتخاب خوب، لازم نیست از وات و آمپر سر دربیاری. از چیزی که نیاز داری شروع کنیم.</p>
			<div class="cg-actions">
				<button class="cg-button cg-button--primary" id="cg-start" data-cg-restart>کمکم کن انتخاب کنم<span aria-hidden="true">＋</span></button>
				<a class="cg-text-button" href="#cg-help">درست شارژ نمی‌کنه</a>
			</div>
			<div class="cg-hero-note"><?php echo $this->icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> چند انتخاب ساده · بدون ثبت‌نام · بدون عجله</div>
		</div>
		<div class="cg-hero-visual">
			<img src="<?php echo esc_url( $this->image_url( $hero['file'] ) ); ?>" alt="<?php echo esc_attr( $hero['alt'] ); ?>" width="<?php echo (int) $hero['width']; ?>" height="<?php echo (int) $hero['height']; ?>" fetchpriority="high" decoding="async">
			<div class="cg-floating-label"><span class="cg-mini-icon" aria-hidden="true">ϟ</span><div>همراه روزهای شلوغ<small>یک انتخاب، متناسب با نیاز تو</small></div></div>
			<span class="cg-image-caption">تصویر فضاسازی</span>
		</div>
	</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The three-step wizard. Steps 2 and 3 live in templates so the browser
	 * script never contains guide copy.
	 *
	 * @return string
	 */
	private function journey(): string {
		$needs = Content::needs();
		ob_start();
		?>
	<section class="cg-section cg-wrap" id="cg-journey" aria-labelledby="cg-journey-title">
		<div class="cg-section-top">
			<div><span class="cg-eyebrow">۰۱ / انتخاب راحت</span><h2 id="cg-journey-title">از اینجا شروع کنیم.</h2></div>
			<p>هر مرحله فقط یک انتخاب.<br>اگر مطمئن نیستی، اشکالی ندارد.</p>
		</div>
		<div class="cg-journey">
			<aside class="cg-rail">
				<span class="cg-rail-brand">همراه شارژ</span>
				<div class="cg-rail-steps" aria-label="مراحل راهنما">
					<span class="cg-rail-step" data-rail="1" aria-current="step">۱ <b>نیازت</b></span>
					<span class="cg-rail-step" data-rail="2">۲ <b>دستگاهت</b></span>
					<span class="cg-rail-step" data-rail="3">۳ <b>اولویتت</b></span>
				</div>
				<p>قرار نیست بیشتر خرج کنی؛<br>قرار است درست‌تر انتخاب کنی.</p>
			</aside>
			<div class="cg-wizard" id="cg-wizard">
				<div class="cg-step" data-cg-step="1">
					<span class="cg-step-label">قدم ۱ از ۳</span>
					<h3 tabindex="-1">چه کمکی از دستم برمیاد؟</h3>
					<div class="cg-choices">
						<?php foreach ( $needs as $key => $need ) : ?>
							<button class="cg-choice" data-cg-need="<?php echo esc_attr( $key ); ?>">
								<img src="<?php echo esc_url( $this->image_url( $need['image'] ) ); ?>" alt="" width="1530" height="930" loading="lazy" decoding="async">
								<b><?php echo esc_html( $need['label'] ); ?></b>
								<span><?php echo esc_html( $need['desc'] ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
					<p class="cg-quiet">انتخابت را هر وقت خواستی می‌توانی عوض کنی.</p>
				</div>
				<template id="cg-step2">
					<span class="cg-step-label">قدم ۲ از ۳</span>
					<h3 tabindex="-1">بیشتر برای چه دستگاهی؟</h3>
					<div class="cg-options">
						<?php foreach ( Content::devices() as $key => $device ) : ?>
							<button class="cg-option" data-cg-device="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $device['label'] ); ?><small><?php echo esc_html( $device['desc'] ); ?></small></button>
						<?php endforeach; ?>
					</div>
					<div class="cg-wizard-actions">
						<button class="cg-back" data-cg-back="1">برگشت</button>
						<span class="cg-quiet">لازم نیست مشخصات فنی را حفظ باشی.</span>
					</div>
				</template>
				<?php foreach ( array_keys( $needs ) as $need_key ) : ?>
					<template id="cg-step3-<?php echo esc_attr( $need_key ); ?>">
						<span class="cg-step-label">قدم ۳ از ۳</span>
						<h3 tabindex="-1">چه چیزی برات مهم‌تره؟</h3>
						<div class="cg-options">
							<?php foreach ( Content::priorities( $need_key ) as $row ) : ?>
								<button class="cg-option" data-cg-priority="<?php echo esc_attr( $row['key'] ); ?>"><?php echo esc_html( $row['label'] ); ?><small><?php echo esc_html( $row['desc'] ); ?></small></button>
							<?php endforeach; ?>
						</div>
						<div class="cg-wizard-actions"><button class="cg-back" data-cg-back="2">برگشت</button></div>
					</template>
				<?php endforeach; ?>
				<div class="cg-step" id="cg-stage" hidden></div>
				<div class="cg-result" id="cg-result" aria-live="polite" hidden></div>
			</div>
		</div>
	</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The laptop use-case section.
	 *
	 * @return string
	 */
	private function laptop(): string {
		$laptop = Content::laptop();
		ob_start();
		?>
	<section class="cg-laptop cg-wrap">
		<img src="<?php echo esc_url( $this->image_url( $laptop['file'] ) ); ?>" alt="<?php echo esc_attr( $laptop['alt'] ); ?>" width="<?php echo (int) $laptop['width']; ?>" height="<?php echo (int) $laptop['height']; ?>" loading="lazy" decoding="async">
		<div>
			<span class="cg-eyebrow">پاوربانک، فراتر از گوشی</span>
			<h2>برای بعضی لپ‌تاپ‌ها هم همراه خوبی است.</h2>
			<p>یک پاوربانک با توان کافی می‌تواند برای شارژ برخی لپ‌تاپ‌ها هم استفاده شود؛ یک کاربرد مفید وقتی به پریز دسترسی نداری.</p>
			<p class="cg-note-inline">قبل از انتخاب: لپ‌تاپ باید از شارژ ورودی USB-C پشتیبانی کند. توان موردنیاز آن و کابل را با مشخصات پاوربانک تطبیق بده؛ داشتن درگاه USB-C به‌تنهایی کافی نیست.</p>
			<a class="cg-text-button" href="#cg-products">دیدن گزینه‌های پاوربانک</a>
		</div>
	</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Battery care: the four facts, the settings walkthrough and the reading
	 * list.
	 *
	 * @return string
	 */
	private function care(): string {
		$guides = Content::care_guides();
		$active = Content::default_care();
		ob_start();
		?>
	<section class="cg-section cg-wrap" id="cg-care" aria-labelledby="cg-care-title">
		<div class="cg-section-top">
			<div><span class="cg-eyebrow">۰۲ / مراقبت، بدون وسواس</span><h2 id="cg-care-title">هوای باتری‌ات را داشته باش.</h2></div>
			<p>چهار نکته که دانستنشان کمک می‌کند.<br>با پشتوانه راهنمای رسمی سازنده.</p>
		</div>
		<div class="cg-facts">
			<?php foreach ( Content::battery_facts() as $fact ) : ?>
				<article class="cg-fact<?php echo '' !== $fact['kind'] ? ' cg-fact-' . esc_attr( $fact['kind'] ) : ''; ?>">
					<div class="cg-fact-top"><span><?php echo esc_html( $fact['eyebrow'] ); ?></span><span class="cg-fact-label"><?php echo esc_html( $fact['label'] ); ?></span></div>
					<?php if ( ! empty( $fact['equation'] ) ) : ?>
						<div class="cg-cycle" aria-label="پنجاه درصد مصرف امروز و پنجاه درصد مصرف فردا، در مجموع یک چرخه">
							<span><b>۵۰٪</b><small>مصرف امروز</small></span><i aria-hidden="true">＋</i>
							<span><b>۵۰٪</b><small>مصرف فردا</small></span><i aria-hidden="true">＝</i>
							<span class="cg-cycle-total"><b>۱</b><small>چرخه شارژ</small></span>
						</div>
					<?php elseif ( '' !== $fact['stat'] ) : ?>
						<div class="cg-fact-stat"><span class="cg-stat-value"><?php echo wp_kses_post( $fact['stat'] ); ?></span><?php if ( ! empty( $fact['caption'] ) ) : ?><span class="cg-stat-caption"><?php echo esc_html( $fact['caption'] ); ?></span><?php endif; ?></div>
					<?php endif; ?>
					<h3><?php echo esc_html( $fact['title'] ); ?></h3>
					<p><?php echo esc_html( $fact['body'] ); ?></p>
					<div class="cg-fact-takeaway"><b><?php echo esc_html( $fact['takeaway']['label'] ); ?></b><span><?php echo esc_html( $fact['takeaway']['text'] ); ?></span></div>
					<div class="cg-fact-sources">
						<?php foreach ( $fact['sources'] as $source ) : ?>
							<a class="cg-source" href="<?php echo esc_url( $source['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $source['label'] ); ?></a>
						<?php endforeach; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>

		<div class="cg-care" id="cg-battery">
			<div>
				<span class="cg-eyebrow">همین حالا می‌توانی بررسی کنی</span>
				<h3>تنظیمات مراقبت از باتری کجاست؟</h3>
				<p>مدل یا نسخه نرم‌افزار گوشی‌ات را انتخاب کن؛ مسیر و گزینه مربوط به آن را ببین.</p>
				<div class="cg-filters cg-care-filters" role="tablist" aria-label="راهنمای تنظیمات باتری">
					<?php foreach ( $guides as $key => $guide ) : ?>
						<button role="tab" id="cg-care-tab-<?php echo esc_attr( $key ); ?>" aria-controls="cg-care-panel-<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo $key === $active ? 'true' : 'false'; ?>" class="cg-filter<?php echo $key === $active ? ' is-selected' : ''; ?>" data-cg-care="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $guide['label'] ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="cg-care-panels">
				<?php foreach ( $guides as $key => $guide ) : ?>
					<div class="cg-care-panel" id="cg-care-panel-<?php echo esc_attr( $key ); ?>" role="tabpanel" aria-labelledby="cg-care-tab-<?php echo esc_attr( $key ); ?>" data-cg-care-panel="<?php echo esc_attr( $key ); ?>" <?php echo $key === $active ? '' : 'hidden'; ?>>
						<?php echo $this->care_panel( $guide ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="cg-gentle">
			<?php echo $this->icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p>قرار نیست تمام روز مراقب عدد درصد باشی. از تنظیمات خود گوشی کمک بگیر و شارژ موردنیاز روزت را در نظر بگیر؛ فرسودگی باتری هم بخشی طبیعی از عمر آن است.</p>
		</div>

		<?php $reading = $this->reading_query(); ?>
		<?php if ( $reading ) : ?>
		<div class="cg-reading-heading">
			<div><span class="cg-eyebrow">از مجله تهران اسپیکر</span><h3>اگر دوست داری بیشتر بدانی.</h3></div>
			<p>چند مطلب مرتبط برای ادامه مطالعه</p>
		</div>
		<div class="cg-reading">
			<?php
			// A real loop, exactly how the theme's own blog pages run this card.
			// `setup_postdata()` alone leaves in_the_loop() false and the card came
			// out rendered for the guide page instead of the article; the_query /
			// the_post() is the context the partial is written against.
			while ( $reading->have_posts() ) :
				$reading->the_post();
				$blogCardHeadingLevel = 3;
				require $this->blog_card_file();
			endwhile;
			wp_reset_postdata();
			?>
		</div>
		<?php endif; ?>
	</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One battery-settings walkthrough panel.
	 *
	 * @param array<string, mixed> $guide Care guide.
	 * @return string
	 */
	private function care_panel( array $guide ): string {
		ob_start();
		?>
		<div class="cg-walkthrough">
			<div class="cg-instructions">
				<span class="cg-eyebrow">راهنمای <?php echo esc_html( $guide['label'] ); ?></span>
				<ol class="cg-care-steps">
					<li><b>تنظیمات گوشی را باز کن.</b><span dir="ltr"><?php echo esc_html( $guide['path'][0] ); ?></span></li>
					<li><b>وارد بخش باتری شو.</b><span dir="ltr"><?php echo esc_html( $guide['path'][1] ); ?></span></li>
					<li><b>این بخش را باز کن.</b><span dir="ltr"><?php echo esc_html( $guide['path'][2] ); ?></span></li>
				</ol>
				<div class="cg-care-action">
					<b>حالا کدام گزینه را بررسی کنم؟</b>
					<h4 dir="ltr"><?php echo esc_html( $guide['option'] ); ?></h4>
					<p><?php echo esc_html( $guide['detail'] ); ?></p>
				</div>
				<p class="cg-note-inline"><?php echo esc_html( $guide['note'] ); ?></p>
				<a class="cg-source" href="<?php echo esc_url( $guide['source'] ); ?>" target="_blank" rel="noopener">دیدن راهنمای رسمی سازنده</a>
			</div>
			<figure class="cg-screen">
				<?php if ( ! empty( $guide['image'] ) ) : ?>
					<img class="cg-official-screen" src="<?php echo esc_url( $this->image_url( $guide['image'] ) ); ?>" alt="<?php echo esc_attr( (string) ( $guide['alt'] ?? '' ) ); ?>" width="320" height="660" loading="lazy" decoding="async">
				<?php else : ?>
					<div class="cg-phone" role="img" aria-label="نمای شماتیک مسیر تنظیمات <?php echo esc_attr( $guide['label'] ); ?>">
						<span class="cg-phone-speaker" aria-hidden="true"></span>
						<small dir="ltr"><?php echo esc_html( $guide['path'][1] ); ?></small>
						<h4 dir="ltr"><?php echo esc_html( $guide['path'][2] ); ?></h4>
						<div class="cg-phone-battery" aria-hidden="true">▰ ▰ ▰ ▱</div>
						<div class="cg-phone-option" dir="ltr"><?php echo esc_html( $guide['option'] ); ?><span aria-hidden="true">✓</span></div>
						<p>گزینه‌ای که باید بررسی کنی</p>
					</div>
				<?php endif; ?>
				<figcaption><?php echo esc_html( (string) ( $guide['caption'] ?? 'نمای شماتیک آموزشی؛ اسکرین‌شات گوشی نیست و جای گزینه‌ها ممکن است متفاوت باشد.' ) ); ?></figcaption>
			</figure>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The troubleshooting section.
	 *
	 * @return string
	 */
	private function help(): string {
		$issues = Content::issues();
		$active = Content::default_issue();
		ob_start();
		?>
	<section class="cg-section cg-wrap" id="cg-help" aria-labelledby="cg-help-title">
		<div class="cg-section-top">
			<div><span class="cg-eyebrow">۰۳ / یک مشکل، چند بررسی ساده</span><h2 id="cg-help-title">گوشی یا پاوربانکت درست شارژ نمی‌شود؟</h2></div>
			<p>اول علت‌های ساده را بررسی کنیم.<br>شاید نیازی به خرید جدید نباشد.</p>
		</div>
		<div class="cg-help-layout">
			<div class="cg-symptoms" role="tablist" aria-label="انتخاب مشکل">
				<?php foreach ( $issues as $key => $issue ) : ?>
					<button role="tab" id="cg-issue-tab-<?php echo esc_attr( $key ); ?>" aria-controls="cg-issue-panel-<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo $key === $active ? 'true' : 'false'; ?>" class="cg-symptom<?php echo $key === $active ? ' is-selected' : ''; ?>" data-cg-issue="<?php echo esc_attr( $key ); ?>">
						<?php echo $this->icon( (string) $issue['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<b><?php echo esc_html( $issue['label'] ); ?></b>
						<small><?php echo esc_html( $issue['hint'] ); ?></small>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="cg-help-panels">
				<?php foreach ( $issues as $key => $issue ) : ?>
					<div class="cg-help-panel" id="cg-issue-panel-<?php echo esc_attr( $key ); ?>" role="tabpanel" aria-labelledby="cg-issue-tab-<?php echo esc_attr( $key ); ?>" data-cg-issue-panel="<?php echo esc_attr( $key ); ?>" <?php echo $key === $active ? '' : 'hidden'; ?>>
						<span class="cg-eyebrow">این سه مورد را به‌ترتیب بررسی کن</span>
						<h3><?php echo esc_html( $issue['title'] ); ?></h3>
						<p class="cg-help-intro"><?php echo esc_html( $issue['intro'] ); ?></p>
						<ol class="cg-help-steps">
							<?php foreach ( $issue['steps'] as $step ) : ?>
								<li><?php echo esc_html( $step ); ?></li>
							<?php endforeach; ?>
						</ol>
						<a class="cg-source" href="<?php echo esc_url( $issue['source'] ); ?>" target="_blank" rel="noopener">راهنمای سازنده</a>
						<p class="cg-help-followup"><?php echo esc_html( Content::issue_followup() ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The product grid, built from the live catalog. Renders nothing at all
	 * when no category is configured.
	 *
	 * @return string
	 */
	private function products(): string {
		if ( ! $this->has_products ) {
			return '';
		}
		ob_start();
		?>
	<section class="cg-section cg-wrap" id="cg-products" aria-labelledby="cg-products-title">
		<div class="cg-section-top">
			<div><span class="cg-eyebrow">۰۴ / از نزدیک ببین</span><h2 id="cg-products-title">آشنایی با چند انتخاب.</h2></div>
			<div class="cg-filters" role="group" aria-label="نوع محصول">
				<button class="cg-filter is-selected" data-cg-filter="all" aria-pressed="true">همه</button>
				<button class="cg-filter" data-cg-filter="powerbank" aria-pressed="false">پاوربانک</button>
				<button class="cg-filter" data-cg-filter="charger" aria-pressed="false">شارژر</button>
			</div>
		</div>
		<div id="cg-grid" class="products cg-products-grid centered-flex justify-content-start flex-wrap">
			<?php foreach ( $this->catalog as $row ) : ?>
				<?php
				$product = $this->product_for( $row );
				if ( ! $product ) :
					continue;
				endif;
				?>
				<div class="cg-cell" data-cg-kind="<?php echo esc_attr( (string) ( $row['kind'] ?? '' ) ); ?>">
					<?php $this->theme_product_card( $product ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The live WooCommerce product behind a catalog row.
	 *
	 * The theme's card component renders a product, not a data row, so the
	 * row's id is resolved back to the product object (WooCommerce serves it
	 * from the object cache filled by the catalog query).
	 *
	 * @param array<string, mixed> $row Catalog row.
	 * @return object|null
	 */
	private function product_for( array $row ): ?object {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}
		$product = wc_get_product( (int) ( $row['id'] ?? 0 ) );
		return is_object( $product ) ? $product : null;
	}

	/**
	 * Render one product with the theme's own card component.
	 *
	 * The theme's card parts read post meta through the loop (get_the_ID(),
	 * `h2-title`, `badge-title`, `product-status`), so the product has to be
	 * the current post while the card renders. The flags keep the card to its
	 * plain form: no gallery swiper, no countdown, no compare button.
	 *
	 * @param object $product WooCommerce product.
	 * @return void
	 */
	private function theme_product_card( object $product ): void {
		$file = $this->theme_card_file();
		if ( '' === $file ) {
			return;
		}
		$post = function_exists( 'get_post' ) ? get_post( (int) $product->get_id() ) : null;
		if ( $post ) {
			setup_postdata( $post );
		}

		$filter       = [
			'activeCompare' => false,
			'compareItems'  => [],
		];
		$args         = [ 'isAmazing' => false ];
		$imageGallery = false;

		require $file;

		if ( $post ) {
			wp_reset_postdata();
		}
	}

	/**
	 * The theme's product card partial for this device, '' when unavailable.
	 *
	 * The theme picks the card by device (`IS_MOBILE`, a user-agent test), not
	 * by viewport width, so the guide does the same.
	 *
	 * @return string
	 */
	private function theme_card_file(): string {
		if ( ! defined( 'THEME_COMPONENTS' ) ) {
			return '';
		}
		$file = THEME_COMPONENTS . ( defined( 'IS_MOBILE' ) && IS_MOBILE ? 'product-cards/simple-card-mobile.php' : 'product-cards/simple-card.php' );
		return is_readable( $file ) ? $file : '';
	}

	/**
	 * The theme's blog card partial, '' when unavailable.
	 *
	 * @return string
	 */
	private function blog_card_file(): string {
		if ( ! defined( 'THEME_LIB_DIR' ) ) {
			return '';
		}
		$file = THEME_LIB_DIR . 'Blog/template/cards/blog-card.php';
		return is_readable( $file ) ? $file : '';
	}

	/**
	 * The magazine posts the owner listed in the settings, as a real loop.
	 *
	 * The IDs are store data, so they come from the option the owner edits — no
	 * post is named in the source, and an empty list means the section is not
	 * rendered at all. The query is ordered by the owner's list and publishes
	 * nothing that is not a published post or page, so a deleted or drafted
	 * article cannot reach the page.
	 *
	 * Why a query rather than setup_postdata(): the theme's card is written for
	 * a loop (have_posts()/the_post()). Rendered outside one it answered with
	 * the document being viewed — on the live page every magazine card showed
	 * the guide page itself. The loop is the context the partial expects.
	 *
	 * @return \WP_Query|null Loop, or null when there is nothing to show.
	 */
	private function reading_query(): ?object {
		$ids = array_values( array_filter( array_map( 'intval', (array) ( $this->config['blogIds'] ?? [] ) ) ) );
		if ( ! $ids || '' === $this->blog_card_file() || ! class_exists( 'WP_Query' ) ) {
			return null;
		}
		$query = new \WP_Query(
			[
				'post__in'            => $ids,
				'post_type'           => [ 'post', 'page' ],
				'post_status'         => 'publish',
				'orderby'             => 'post__in',
				'posts_per_page'      => count( $ids ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			]
		);
		return $query->have_posts() ? $query : null;
	}

	/**
	 * The FAQ section.
	 *
	 * @return string
	 */
	private function faq(): string {
		$laptop = Content::laptop();
		ob_start();
		?>
	<section class="cg-section cg-wrap cg-faq" id="cg-faq" aria-labelledby="cg-faq-title">
		<div>
			<span class="cg-eyebrow">جواب‌های کوتاه برای سؤال‌های مهم</span>
			<h2 id="cg-faq-title">شاید سؤال تو هم باشد.</h2>
			<p>عددهای بزرگ، همه داستان نیستند.</p>
			<img src="<?php echo esc_url( $this->image_url( $laptop['file'] ) ); ?>" alt="پاوربانک کنار لپ‌تاپ و گوشی روی میز فضای باز" width="<?php echo (int) $laptop['width']; ?>" height="<?php echo (int) $laptop['height']; ?>" loading="lazy" decoding="async">
		</div>
		<div>
			<?php foreach ( Content::faq() as $item ) : ?>
				<details>
					<summary><?php echo esc_html( $item['question'] ); ?></summary>
					<p><?php echo esc_html( $item['answer'] ); ?></p>
					<?php if ( '' !== $item['source'] ) : ?>
						<a class="cg-source" href="<?php echo esc_url( $item['source'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $item['source_label'] ); ?></a>
					<?php endif; ?>
				</details>
			<?php endforeach; ?>
		</div>
	</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Plugin asset URL for an image file name.
	 *
	 * @param string $file File name.
	 * @return string
	 */
	private function image_url( string $file ): string {
		return TS_CHARGE_GUIDE_URL . 'assets/images/' . $file;
	}

	/**
	 * Inline icon, drawn on the theme's ink. Kept inline so the guide does not
	 * depend on another handle being enqueued.
	 *
	 * @param string $name Icon name.
	 * @return string SVG markup.
	 */
	private function icon( string $name ): string {
		$paths = [
			'check' => '<path d="M4 12.5l5 5L20 6.5"/>',
			'bolt'  => '<path d="M13 2L5 13.5h5.5L10.5 22 19 10.5h-5.5z"/>',
			'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.5l3.5 2"/>',
			'stack' => '<rect x="3" y="4" width="18" height="5" rx="1.5"/><rect x="3" y="12" width="18" height="5" rx="1.5"/><path d="M3 20h18"/>',
			'sun'   => '<circle cx="12" cy="12" r="4.5"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
			'link'  => '<path d="M8 12h8"/><path d="M11 7H8a5 5 0 000 10h3"/><path d="M13 7h3a5 5 0 010 10h-3"/>',
			'heart' => '<path d="M12 20s-7-4.6-7-9.4A4.1 4.1 0 0112 8.2 4.1 4.1 0 0119 10.6C19 15.4 12 20 12 20z"/>',
		];
		$body = $paths[ $name ] ?? $paths['check'];
		return '<svg class="cg-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
	}
}
