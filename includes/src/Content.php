<?php
/**
 * Guide content: the editorial copy of the guide, in one place.
 *
 * @package TSChargeGuide
 */

namespace TSChargeGuide;

defined( 'ABSPATH' ) || exit;

/**
 * Every sentence, label and reference the guide shows.
 *
 * This is interface copy, not store data: no product name, product id, term
 * name, term slug, price or catalog URL appears here. The products the guide
 * talks about are read from WooCommerce by CatalogAdapter. The article card
 * paths are site-relative so a domain or protocol change cannot break them.
 */
final class Content {

	/**
	 * The three ways into the guide.
	 *
	 * @return array<string, array{label:string,desc:string,image:string}>
	 */
	public static function needs(): array {
		return [
			'powerbank' => [
				'label' => 'شارژ همراه می‌خوام',
				'desc'  => 'پاوربانک برای بیرون از خانه',
				'image' => 'life-mobile.webp',
			],
			'charger'   => [
				'label' => 'شارژر می‌خوام',
				'desc'  => 'برای خانه، محل کار یا پاوربانک',
				'image' => 'life-home.webp',
			],
			'both'      => [
				'label' => 'هر دو را با هم',
				'desc'  => 'یک ترکیب هماهنگ',
				'image' => 'life-work.webp',
			],
		];
	}

	/**
	 * Wizard step 2: what the shopper will charge.
	 *
	 * @return array<string, array{label:string,desc:string}>
	 */
	public static function devices(): array {
		return [
			'iphone-lightning' => [ 'label' => 'آیفون ۸ تا ۱۴', 'desc' => 'درگاه Lightning' ],
			'iphone-usbc'      => [ 'label' => 'آیفون ۱۵ و جدیدتر', 'desc' => 'درگاه USB-C' ],
			'samsung'          => [ 'label' => 'سامسونگ', 'desc' => 'مدل دقیق روی سرعت اثر دارد' ],
			'xiaomi'           => [ 'label' => 'شیائومی / پوکو', 'desc' => 'شارژ توربو به ترکیب سازگار نیاز دارد' ],
			'other'            => [ 'label' => 'گوشی دیگر / نمی‌دانم', 'desc' => 'با راهنمای عمومی شروع کنیم' ],
			'laptop'           => [ 'label' => 'لپ‌تاپ یا تبلت', 'desc' => 'توان و ورودی دستگاه مهم است' ],
		];
	}

	/**
	 * Wizard step 3: what matters most, per need.
	 *
	 * @param string $need One of the needs() keys.
	 * @return array<int, array{key:string,label:string,desc:string}>
	 */
	public static function priorities( string $need ): array {
		$map = [
			'powerbank' => [
				[ 'key' => 'light',    'label' => 'سبک و همراه',            'desc' => 'برای رفت‌وآمد روزانه' ],
				[ 'key' => 'capacity', 'label' => 'شارژ ذخیره بیشتر',        'desc' => 'برای روزهای طولانی‌تر' ],
				[ 'key' => 'reuse',    'label' => 'خرید به‌اندازه نیاز',      'desc' => 'اول وسایل فعلی‌ام را بررسی کنم' ],
			],
			'charger'   => [
				[ 'key' => 'single', 'label' => 'فقط یک دستگاه',      'desc' => 'شارژ ساده در خانه یا محل کار' ],
				[ 'key' => 'multi',  'label' => 'چند دستگاه با هم',    'desc' => 'گوشی، تبلت یا لپ‌تاپ' ],
				[ 'key' => 'reuse',  'label' => 'برای شارژ پاوربانک',  'desc' => 'شاید کلگی فعلی کافی باشد' ],
			],
			'both'      => [
				[ 'key' => 'light', 'label' => 'رفت‌وآمد روزانه', 'desc' => 'یک ترکیب ساده و قابل حمل' ],
				[ 'key' => 'multi', 'label' => 'چند دستگاه',       'desc' => 'نیاز به بررسی توان هم‌زمان' ],
				[ 'key' => 'reuse', 'label' => 'خرج اضافه نکنم',   'desc' => 'اول لوازمی که دارم' ],
			],
		];
		return $map[ $need ] ?? $map['powerbank'];
	}

	/**
	 * Every priority key the browser may submit, across all needs.
	 *
	 * @return array<int, string>
	 */
	public static function priority_keys(): array {
		$keys = [];
		foreach ( array_keys( self::needs() ) as $need ) {
			foreach ( self::priorities( $need ) as $row ) {
				$keys[ $row['key'] ] = true;
			}
		}
		return array_keys( $keys );
	}

	/**
	 * Device-specific advice on the cable and the port.
	 *
	 * @param string $device One of the devices() keys.
	 * @return array{title:string,body:string}
	 */
	public static function device_advice( string $device ): array {
		$map = [
			'iphone-lightning' => [
				'title' => 'کابل متناسب با Lightning',
				'body'  => 'برای اتصال به خروجی USB-C، کابل USB-C به Lightning سازگار لازم داری. کابل USB-C متصل به بعضی پاوربانک‌ها مستقیم به این آیفون‌ها وصل نمی‌شود.',
			],
			'iphone-usbc'      => [
				'title' => 'اتصال USB-C به USB-C',
				'body'  => 'کابل و منبع شارژ سازگار انتخاب کن. توان بالاتر روی جعبه، به‌تنهایی سرعت بیشتری برای هر مدل آیفون تضمین نمی‌کند.',
			],
			'samsung'          => [
				'title' => 'برای سوپرفست، مدل دقیق مهم است',
				'body'  => 'اگر گوشی از Super Fast Charging پشتیبانی می‌کند، پشتیبانی شارژر یا پاوربانک از PD/PPS و شرایط کابل همان مدل را بررسی کن. همه گوشی‌های سامسونگ شرایط یکسانی ندارند.',
			],
			'xiaomi'           => [
				'title' => 'توربو را فقط از روی وات انتخاب نکن',
				'body'  => 'حالت‌های سریع اختصاصی شیائومی به گوشی، شارژر و کابل سازگار وابسته‌اند. روی یک پاوربانک عمومی، رسیدن به همان سرعت شارژر اصلی را قطعی فرض نکن.',
			],
			'other'            => [
				'title' => 'اول درگاه گوشی را بشناسیم',
				'body'  => 'نام مدل در بخش «درباره تلفن» است. شکل درگاه و مشخصات شارژ همان مدل را بررسی کن؛ تا آن زمان سرعت مشخصی برای یک محصول وعده نمی‌دهیم.',
			],
			'laptop'           => [
				'title' => 'اول پشتیبانی شارژ از USB-C را بررسی کن',
				'body'  => 'داشتن USB-C به‌تنهایی کافی نیست. پشتیبانی شارژ ورودی، توان موردنیاز و کابل مناسب لپ‌تاپ یا تبلت را از راهنمای همان مدل بررسی کن.',
			],
		];
		return $map[ $device ] ?? $map['other'];
	}

	/**
	 * Priority-specific result heading and lead.
	 *
	 * @param string $need     Need key.
	 * @param string $priority Priority key.
	 * @return array{heading:string,lead:string,show_products:bool}
	 */
	public static function priority_copy( string $need, string $priority ): array {
		$reuse  = 'reuse' === $priority;
		$multi  = 'multi' === $priority;
		$copy   = [
			'heading' => 'یک انتخاب ساده برای استفاده روزانه.',
			'lead'    => 'اگر بیشتر بیرون از خانه هستی، وزن و اندازه هم در کنار ظرفیت مهم‌اند. فقط به عدد بزرگ‌تر روی جعبه تکیه نکن.',
		];
		if ( $reuse ) {
			$copy = [
				'heading' => 'قبل از خرید، همین وسایل را بررسی کن.',
				'lead'    => 'اگر کلگی فعلی با ورودی پاوربانک یا گوشی سازگار است و سرعتش برایت کافی است، لازم نیست فقط برای عدد وات بالاتر عوضش کنی.',
			];
		} elseif ( $multi ) {
			$copy = [
				'heading' => 'توان هم‌زمان را در نظر بگیر.',
				'lead'    => 'عدد بزرگ روی شارژر معمولاً تمام داستان نیست؛ هنگام اتصال چند دستگاه، جدول تقسیم توان پورت‌ها را بررسی کن.',
			];
		} elseif ( 'capacity' === $priority ) {
			$copy = [
				'heading' => 'ظرفیت بیشتر، با توجه به وزن.',
				'lead'    => 'ظرفیت بیشتر می‌تواند ذخیره بیشتری بدهد؛ اما وزن، انرژی قابل تحویل و مصرف گوشی هم روی تجربه اثر دارند. تعداد شارژ را قطعی فرض نکن.',
			];
		}
		$copy['show_products'] = ! $reuse;
		return $copy;
	}

	/**
	 * The two fixed checklist lines; the device advice is the first item.
	 *
	 * @return array<int, string>
	 */
	public static function checklist(): array {
		return [
			'برای شارژ خود پاوربانک، مشخصات <b>ورودی</b> را ببین؛ برای شارژ گوشی، مشخصات <b>خروجی</b> را.',
			'پیش از پرداخت، کابل داخل جعبه و شرایط ضمانت همان نسخه را بررسی کن.',
		];
	}

	/**
	 * Result disclaimer, always shown above the picks.
	 *
	 * @return string
	 */
	public static function result_warning(): string {
		return 'این‌ها گزینه‌هایی برای بررسی‌اند؛ سازگاری نهایی و سرعت شارژ باید برای مدل دقیق دستگاه و نسخه محصول تأیید شود.';
	}

	/**
	 * The four battery-care facts.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function battery_facts(): array {
		return [
			[
				'id'       => 'daily',
				'kind'     => 'blue',
				'eyebrow'  => '۰۱ / شارژ روزمره',
				'label'    => 'طبق راهنمای اپل',
				'stat'     => 'منتظر <b>۰٪</b> نمان.',
				'title'    => 'هر اتصال به شارژر، یک چرخه نیست.',
				'body'     => 'برای آیفون لازم نیست باتری را کامل خالی کنی و بعد به شارژ بزنی. شارژهای کوتاه در طول روز هم بخشی از استفاده معمول‌اند.',
				'takeaway' => [ 'label' => 'کاری که کمک می‌کند', 'text' => 'وقتی به شارژ نیاز داری، گوشی را وصل کن؛ لازم نیست اول آن را به صفر برسانی.' ],
				'sources'  => [ [ 'label' => 'پشتوانه این نکته: راهنمای باتری اپل', 'url' => 'https://www.apple.com/batteries/why-lithium-ion/' ] ],
			],
			[
				'id'       => 'cycle',
				'kind'     => 'cycle',
				'eyebrow'  => '۰۲ / معنی چرخه شارژ',
				'label'    => 'یک مثال ساده',
				'stat'     => '',
				'equation' => true,
				'title'    => 'مجموع مصرف حساب می‌شود.',
				'body'     => 'یک چرخه یعنی در مجموع معادل ۱۰۰٪ ظرفیت باتری مصرف شود؛ حتی اگر بین این مصرف‌ها چند بار گوشی را شارژ کرده باشی.',
				'takeaway' => [ 'label' => 'پس خیالت راحت‌تر', 'text' => 'تعداد دفعات وصل کردن کابل، به‌تنهایی شمارش چرخه‌های باتری نیست.' ],
				'sources'  => [ [ 'label' => 'پشتوانه این نکته: تعریف چرخه در راهنمای اپل', 'url' => 'https://www.apple.com/batteries/why-lithium-ion/' ] ],
			],
			[
				'id'       => 'temperature',
				'kind'     => '',
				'eyebrow'  => '۰۳ / دمای محیط',
				'label'    => 'راهنمای آیفون',
				'stat'     => '۳۵°C',
				'caption'  => 'دمای محیط؛ نه دمای بدنه گوشی',
				'title'    => 'گرمای محیط را جدی بگیر.',
				'body'     => 'اپل توصیه می‌کند آیفون را از محیط بالاتر از ۳۵ درجه دور نگه داری؛ گرمای زیاد می‌تواند ظرفیت باتری را به‌طور ماندگار کاهش دهد.',
				'takeaway' => [ 'label' => 'کاری که کمک می‌کند', 'text' => 'هنگام شارژ، گوشی را زیر آفتاب یا داخل ماشین داغ نگذار.' ],
				'sources'  => [ [ 'label' => 'پشتوانه این نکته: دما و عمر باتری در راهنمای اپل', 'url' => 'https://www.apple.com/batteries/maximizing-performance/' ] ],
			],
			[
				'id'       => 'limits',
				'kind'     => '',
				'eyebrow'  => '۰۴ / مدیریت خودکار شارژ',
				'label'    => 'وابسته به مدل و تنظیمات',
				'stat'     => 'مکث روی <b>۸۰٪</b>؟',
				'title'    => 'اول تنظیمات را بررسی کن.',
				'body'     => 'شارژ بهینه آیفون ممکن است ادامه شارژ بعد از ۸۰٪ را به تأخیر بیندازد. در گلکسی‌های سازگار هم محدودیت شارژ می‌تواند علت توقف باشد.',
				'takeaway' => [ 'label' => 'کاری که کمک می‌کند', 'text' => 'پیام روی صفحه و تنظیمات باتری را ببین؛ مکث شارژ به‌تنهایی نشانه خرابی نیست.' ],
				'sources'  => [
					[ 'label' => 'راهنمای اپل', 'url' => 'https://support.apple.com/en-ie/108055' ],
					[ 'label' => 'راهنمای سامسونگ', 'url' => 'https://www.samsung.com/uk/support/galaxy-battery/battery-protection-tips/' ],
				],
			],
		];
	}

	/**
	 * The battery-settings walkthroughs, keyed by the browser's own value.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function care_guides(): array {
		return [
			'iphone'    => [
				'label'  => 'آیفون ۱۵ و جدیدتر',
				'path'   => [ 'Settings', 'Battery', 'Charging' ],
				'option' => 'Charge Limit',
				'detail' => 'سقف شارژ را متناسب با نیاز روزانه‌ات انتخاب کن. در نسخه‌های جدید iOS، از ۸۰ تا ۱۰۰ درصد با فاصله‌های ۵ درصدی قابل انتخاب است.',
				'note'   => 'وقتی سقف روی ۱۰۰٪ باشد، گزینه Optimised Battery Charging هم در دسترس است. اگر روز طولانی در پیش داری، لازم نیست خودت را به شارژ ۸۰٪ محدود کنی.',
				'source' => 'https://support.apple.com/en-ie/108055',
				'image'  => 'iphone-charging.png',
				'caption' => 'تصویر رسمی اپل • نمونه صفحه در iOS 18؛ ظاهر نسخه‌های دیگر ممکن است متفاوت باشد.',
				'alt'    => 'صفحه Charging آیفون؛ محل انتخاب Charge Limit و گزینه Optimised Battery Charging',
			],
			'iphone14'  => [
				'label'  => 'آیفون ۱۴ و قدیمی‌تر',
				'path'   => [ 'Settings', 'Battery', 'Battery Health & Charging' ],
				'option' => 'Optimised Battery Charging',
				'detail' => 'شارژ بهینه را بررسی کن. گوشی با یادگیری عادت شارژ، ممکن است ادامه شارژ بعد از ۸۰٪ را تا نزدیک زمان استفاده به تأخیر بیندازد.',
				'note'   => 'این گزینه سقف دائمی ۸۰٪ نیست. اگر اعلان شارژ بهینه را دیدی و زودتر به شارژ کامل نیاز داشتی، اعلان را نگه دار و Charge Now را بزن.',
				'source' => 'https://support.apple.com/en-ie/108055',
			],
			'samsung'   => [
				'label'  => 'گلکسی با One UI 7 و جدیدتر',
				'path'   => [ 'Settings', 'Battery', 'Battery protection' ],
				'option' => 'Maximum',
				'detail' => 'در مدل‌های سازگار، حالت Maximum سقف‌های ۸۰، ۸۵، ۹۰ و ۹۵ درصد دارد. سقفی را انتخاب کن که شارژ کافی برای روزت باقی بگذارد.',
				'note'   => 'در بعضی مدل‌ها مسیر از Settings › Device care › Battery می‌گذرد. نام حالت‌ها و امکانات به مدل و نسخه نرم‌افزار بستگی دارد.',
				'source' => 'https://www.samsung.com/sa_en/support/galaxy-battery/battery-protection-tips/',
			],
			'samsung61' => [
				'label'  => 'گلکسی با One UI 6.1',
				'path'   => [ 'Settings', 'Battery', 'Battery protection' ],
				'option' => 'Maximum · 80%',
				'detail' => 'در این نسخه، حالت Maximum شارژ را در ۸۰٪ متوقف می‌کند. حالت Adaptive محافظت را با الگوی خواب هماهنگ می‌کند.',
				'note'   => 'در نسخه‌های قدیمی‌ترِ سازگار، Protect battery سقف ۸۵٪ دارد. اگر گزینه را پیدا نکردی، نام Battery protection یا Protect battery را در جست‌وجوی تنظیمات وارد کن.',
				'source' => 'https://www.samsung.com/levant/support/mobile-devices/galaxy-battery-protection-feature-in-one-ui-6-1/',
			],
		];
	}

	/**
	 * The care-guide key shown before the shopper picks one.
	 *
	 * @return string
	 */
	public static function default_care(): string {
		return 'iphone';
	}

	/**
	 * The troubleshooting entries, keyed by the browser's own value.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function issues(): array {
		return [
			'slow'       => [
				'label'  => 'فست‌شارژ نمی‌شود',
				'hint'   => 'سرعت کمتر از انتظار',
				'icon'   => 'bolt',
				'title'  => 'شارژ کند، همیشه نشانه خرابی نیست.',
				'intro'  => 'اول کابل و درگاه شارژ را بررسی کن؛ بعد تنظیمات شارژ سریع و دمای گوشی را.',
				'steps'  => [
					'کابل مناسب و درگاه شارژ سریع را انتخاب کن. در بعضی شارژرها و پاوربانک‌ها، همه درگاه‌ها سرعت یکسانی ندارند.',
					'فقط گوشی را وصل نگه دار و سرعت را دوباره بررسی کن. هنگام شارژ چند دستگاه، توان شارژر یا پاوربانک ممکن است بین آن‌ها تقسیم شود.',
					'اگر گوشی تنظیم شارژ سریع دارد، فعال بودن آن را بررسی کن. گرم شدن گوشی یا نزدیک شدن باتری به شارژ کامل هم می‌تواند سرعت را کاهش دهد.',
				],
				'source' => 'https://www.samsung.com/uk/support/mobile-devices/how-to-use-super-fast-charging-on-galaxy-s24-ultra-s24-plus-and-s24/',
			],
			'refill'     => [
				'label'  => 'پاوربانک دیر پر می‌شود',
				'hint'   => 'شارژ شدن خود پاوربانک',
				'icon'   => 'clock',
				'title'  => 'چرا خود پاوربانک دیر شارژ می‌شود؟',
				'intro'  => 'برای سرعت شارژ شدن پاوربانک، مشخصات ورودی (Input) مهم است؛ عدد خروجی (Output) مربوط به شارژ دستگاه‌های دیگر است.',
				'steps'  => [
					'روی دستگاه یا دفترچه، بخش Input را ببین. حداکثر ورودی با خروجی ممکن است فرق کند.',
					'توان و پروتکل خروجی کلگی و ظرفیت کابل را با ورودی پاوربانک تطبیق بده. کلگی قوی‌تر از سقف ورودی، الزاماً سریع‌تر نیست.',
					'برای بررسی زمان شارژ، مصرف هم‌زمان از پاوربانک را قطع کن؛ فقط اگر دفترچه همان مدل اجازه شارژ و استفاده هم‌زمان می‌دهد از این قابلیت استفاده کن.',
				],
				'source' => 'https://www.anker.com/uk/blogs/power-banks/how-long-does-a-power-bank-take-to-charge',
			],
			'capacity'   => [
				'label'  => 'گوشی را دفعات کمتری شارژ می‌کند',
				'hint'   => 'کمتر از چیزی که انتظار داشتی',
				'icon'   => 'stack',
				'title'  => 'ظرفیت اسمی، تعداد شارژ قطعی نیست.',
				'intro'  => '۲۰ هزار تقسیم بر ۵ هزار، معیار دقیق تعداد دفعات شارژ نیست.',
				'steps'  => [
					'مقایسه انرژی باید ولتاژ و وات‌ساعت را هم در نظر بگیرد. ظرفیت اسمی به‌تنهایی کافی نیست.',
					'تبدیل انرژی و استفاده از گوشی هنگام شارژ، انرژی قابل ذخیره در گوشی را کمتر می‌کند.',
					'اگر افت ناگهانی و شدید درصد یا خالی‌شدن غیرعادی بدون استفاده می‌بینی، تجربه را ثبت کن و با ضمانت‌کننده همان کالا مطرح کن.',
				],
				'source' => 'https://service.anker.com/article-description/Why-is-the-Rated-Capacity-of-Power-Banks-Lower-Than-Expected',
			],
			'heat'       => [
				'label'  => 'موقع شارژ گرم می‌شود',
				'hint'   => 'دما و توقف شارژ',
				'icon'   => 'sun',
				'title'  => 'اگر دستگاه داغ شده، شارژ را موقتاً متوقف کن.',
				'intro'  => 'گرم شدن و افت سرعت می‌تواند به شرایط استفاده و دما مربوط باشد.',
				'steps'  => [
					'بازی، کار سنگین و قرار گرفتن زیر آفتاب را متوقف کن؛ دستگاه را در محیط خنک‌تر و دارای جریان هوا بگذار.',
					'گوشی را زیر بالش یا پوشش نگه ندار. اگر قاب باعث حبس گرما می‌شود، هنگام شارژ آن را جدا کن.',
					'اگر تورم، بوی غیرعادی، آسیب یا گرمای شدید وجود دارد، استفاده و شارژ را متوقف کن و برای بررسی به خدمات معتبر مراجعه کن.',
				],
				'source' => 'https://www.apple.com/batteries/maximizing-performance/',
			],
			'disconnect' => [
				'label'  => 'قطع و وصل می‌شود',
				'hint'   => 'اتصال ناپایدار یا شارژ وسایل کوچک',
				'icon'   => 'link',
				'title'  => 'کابل و اتصال را قدم‌به‌قدم بررسی کن.',
				'intro'  => 'از ساده‌ترین قسمت شروع کنیم، بدون فشار آوردن به درگاه.',
				'steps'  => [
					'کابل آسیب‌دیده را کنار بگذار. با یک کابل سالم و سازگار و اتصال محکم دوباره بررسی کن.',
					'برای هندزفری یا ساعت، دفترچه پاوربانک را برای حالت کم‌جریان ببین؛ روش فعال‌سازی در مدل‌ها متفاوت است.',
					'اگر با کابل سالم و پورت مناسب مشکل ادامه داشت، سراغ دفترچه و ضمانت برو. درگاه یا باتری را خودت باز نکن.',
				],
				'source' => 'https://www.mi.com/global/support/faq/details/KA-601699/',
			],
		];
	}

	/**
	 * The troubleshooting entry shown before the shopper picks one.
	 *
	 * @return string
	 */
	public static function default_issue(): string {
		return 'slow';
	}

	/**
	 * What to bring to the seller after the three checks.
	 *
	 * @return string
	 */
	public static function issue_followup(): string {
		return 'مدل دستگاه، عکس برچسب ورودی/خروجی و شرح مشکل را آماده کن و با ضمانت‌کننده یا پشتیبانی فروشنده مطرح کن. با این اطلاعات بررسی دقیق‌تر می‌شود.';
	}

	/**
	 * The FAQ entries.
	 *
	 * @return array<int, array{question:string,answer:string,source:string,source_label:string}>
	 */
	public static function faq(): array {
		return [
			[
				'question'     => 'شارژر قوی‌تر، به باتری آسیب می‌زند؟',
				'answer'       => 'فقط از روی عدد وات نمی‌شود درباره آسیب تصمیم گرفت. باید شارژر، کابل و دستگاه سازگار و استاندارد باشند. اپل استفاده از آداپتور USB-C مک را برای آیفون سازگار مجاز می‌داند؛ این به معنی تأیید هر شارژر متفرقه نیست.',
				'source'       => 'https://support.apple.com/en-us/120548',
				'source_label' => 'راهنمای آداپتورهای اپل',
			],
			[
				'question'     => 'چرا پاوربانک ۲۰ هزار، چهار بار گوشی ۵ هزار را پر نمی‌کند؟',
				'answer'       => 'ظرفیت اسمی باتری‌ها بدون توجه به ولتاژ قابل تقسیم مستقیم نیست. تبدیل انرژی هم اتلاف دارد و استفاده هم‌زمان از گوشی روی نتیجه اثر می‌گذارد. پس تعداد شارژ یک عدد قطعی برای همه گوشی‌ها نیست.',
				'source'       => 'https://service.anker.com/article-description/Why-is-the-Rated-Capacity-of-Power-Banks-Lower-Than-Expected',
				'source_label' => 'توضیح رسمی ظرفیت پاوربانک',
			],
			[
				'question'     => 'با شارژر گوشی، پاوربانک را هم می‌توانم شارژ کنم؟',
				'answer'       => 'اگر خروجی شارژر با ورودی پاوربانک و کابل سازگار باشد، بله. برای سرعت بیشتر باید توان ورودی و پروتکل همان مدل بررسی شود؛ توان خروجی پاوربانک به گوشی، معیار خرید کلگی نیست.',
				'source'       => '',
				'source_label' => '',
			],
			[
				'question'     => 'برای قطعی برق، مودم را هم روشن می‌کند؟',
				'answer'       => 'خودکار فرض نکنیم. ولتاژ، جریان، فیش، قطبیت و توان خروجی باید با مودم سازگار باشند. صرف خرید کابل تبدیل کافی نیست؛ مدل مودم و آداپتورش باید بررسی شود.',
				'source'       => '',
				'source_label' => '',
			],
			[
				'question'     => 'قبل از خرید درباره گارانتی چه بپرسم؟',
				'answer'       => 'نام شرکت، مدت باقی‌مانده، زمان شروع ضمانت و روش فعال‌سازی را برای همان رنگ و نسخه‌ای که می‌خری بررسی کن. «اصالت و سلامت فیزیکی» با ضمانت تعمیر یکسان نیست.',
				'source'       => '',
				'source_label' => '',
			],
			[
				'question'     => 'فراخوان یک مدل را از کجا بررسی کنم؟',
				'answer'       => 'از سایت رسمی سازنده و با مدل و سریال دستگاه. بودن نام یک برند در خبر، وضعیت همه محصولاتش را مشخص نمی‌کند. شرایط خدمات در ایران را هم جدا از فروشنده بپرس.',
				'source'       => 'https://www.anker.com/my/rc2506',
				'source_label' => 'بررسی فراخوان انکر',
			],
		];
	}

	/**
	 * The illustration used by the hero.
	 *
	 * @return array{file:string,alt:string,width:int,height:int}
	 */
	public static function hero(): array {
		return [
			'file'   => 'hero.webp',
			'alt'    => 'چیدمان آرام گوشی، پاوربانک و شارژر روی میز آبی',
			'width'  => 1536,
			'height' => 1024,
		];
	}

	/**
	 * The laptop use-case illustration.
	 *
	 * @return array{file:string,alt:string,width:int,height:int}
	 */
	public static function laptop(): array {
		return [
			'file'   => 'life-laptop.webp',
			'alt'    => 'پاوربانک در کنار لپ‌تاپ، تبلت و گوشی روی میز',
			'width'  => 1530,
			'height' => 930,
		];
	}
}
