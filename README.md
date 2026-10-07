# TehranSpeaker Charge Guide

راهنمای انتخاب پاوربانک و شارژر و مراقبت از باتری، برای قالب amazing روی ووکامرس.

Version: **0.4.0** · Requires PHP **8.0** · Requires **WooCommerce** · Author: Parsa Dana, Keyvan Havestin

---

## در یک نگاه

| موضوع | مقدار |
| --- | --- |
| شورت‌کد | `[ts_charge_guide]` |
| صفحه راهنما | از تنظیمات: ابزارها › راهنمای شارژ (یک برگه را جایگزین می‌کند) |
| مسیر REST | `POST /wp-json/ts-charge/v1/recommend` |
| گزینه دیتابیس | `ts_charge_guide_settings` |
| فایل‌های دارایی | `assets/style.css`, `assets/token-bridge.css`, `assets/js/*.js`, `assets/images/*` |
| تست‌ها | `php tests/unit/run.php` · `php tests/unit/bootstrap.php` · `node tests/js/run.js` (پیش‌نیاز: `php tests/js/make-fixture.php`) |

## چرا افزونه است، نه قالب

این راهنما یک سطح مستقل و قابل خاموش‌کردن است: تنظیمات دارد، محصولات ووکامرس را می‌خواند، به
مخاطب عمومی سرویس می‌دهد و باید بدون تغییر قالب بتواند فعال یا غیرفعال شود. بنابراین کد در
`wp-content/plugins/ts-charge-guide/` می‌نشیند و از قالب فقط توکن‌های ظاهری را قرض می‌گیرد.
غیرفعال‌کردن افزونه قالب را دست‌نخورده رها می‌کند: نه هوکی جا می‌ماند، نه CSS/JS اضافه‌ای بار می‌شود.

## نصب و راه‌اندازی

1. افزونه را فعال کن (پوشه `ts-charge-guide` در `wp-content/plugins/`).
2. ابزارها › **راهنمای شارژ** را باز کن.
3. **صفحه راهنما** را انتخاب کن؛ محتوای آن برگه با راهنما جایگزین می‌شود و سرصفحه/پاصفحه قالب
   باقی می‌ماند. جای دیگر هم می‌توانی شورت‌کد `[ts_charge_guide]` را در یک برگه بگذاری.
4. **دسته‌بندی پاوربانک** و **دسته‌بندی شارژر** را از درخت دسته‌های خود فروشگاه انتخاب کن و
   **تعداد کارت هر دسته** (۱ تا ۱۲) را تعیین کن.
5. اگر صفحه‌ای که راهنما روی آن است کش می‌شود (WP Rocket)، پس از ذخیره، کش برگه را پاک کن.

> تا وقتی هیچ دسته‌بندی انتخاب نشده باشد، بخش «آشنایی با چند انتخاب» رندر **نمی‌شود**؛ نه لیست
> پیش‌فرضی، نه محصول حدسی. پیام هشدار همین را در صفحه تنظیمات نشان می‌دهد.

## داده فروشگاه از کجاست

هیچ نام، شناسه، تصویر، لینک یا قیمتی در کد نوشته نشده است:

- نگاشت «پاوربانک» و «شارژر» به دسته‌های واقعی فروشگاه از تنظیمات خوانده می‌شود
  (`Settings::category_terms()`).
- کارت هر محصول از ووکامرس زنده خوانده می‌شود
  (نام، پیوند یکتا، تصویر شاخص، قیمت نمایشی، موجودی، برند در صورت وجود تاکسونومی `product_brand`)
  — `CatalogAdapter`.
- نتیجه پرسش‌وپاسخ هم از همان کاتالوگ انتخاب می‌شود، نه از فهرست ثابت
  (`Recommendation::build()`).

**اثبات خالی‌بودن:** با تنظیمات خالی، `CatalogAdapter::products()` آرایه خالی برمی‌گرداند و حتی یک
کوئری هم اجرا نمی‌شود (`tests/unit/run.php` → «with no category assigned the catalog returns nothing»).

## مقیاس، کش و امنیت

- **محدودسازی:** هر دسته حداکثر `cards_per_kind` محصول می‌خواند؛ کل صفحه حداکثر ۲×۱۲ کارت.
- **کش:** هر دسته در یک ترنزینت `ts_charge_guide_catalog_*` با عمر ۱۵ دقیقه نگه داشته می‌شود؛
  یک بار شکست، ۶۰ ثانیه در کش منفی می‌ماند تا رندر بعدی کوئری را تکرار نکند. فهرست کلیدها در
  گزینه `ts_charge_guide_cache_index` است و دکمه «پاک کردن کش محصولات راهنما» آن‌ها را پاک می‌کند.
- **مسیر عمومی:** `POST ts-charge/v1/recommend` فقط سه فیلد `need`/`device`/`priority` را می‌پذیرد،
  هر مقدار را با enum همان رابط می‌سنجد، بدنه بیش از ۲۰۴۸ بایت را رد می‌کند، `no-store` برمی‌گرداند
  و هرگز نمی‌گوید یک محصول «سازگار» است (`certifies: false`).
- **خروجی امن:** در PHP هر مقدار برای بستر خودش escape می‌شود؛ در JS هیچ متن دریافتی با
  `innerHTML` نوشته نمی‌شود جز `price` واکامرس، آن هم از فیلتر `safeMarkup`.
- **بدون فونت و پالت اختصاصی:** فایل فونتی همراه افزونه نیست؛ خانواده فونت قالب نام برده می‌شود و
  رنگ‌ها از طریق `--cg-*` در `token-bridge.css` به توکن‌های قالب وصل می‌شوند.

## قرارداد REST

```
POST /wp-json/ts-charge/v1/recommend
{ "need": "powerbank|charger|both", "device": "<کلید دستگاه>", "priority": "<کلید اولویت>" }

200 → { need, device, priority, heading, lead, checklist[], warning, products[], certifies: false }
      products[] = { id, name, url, image, priceHtml, inStock, brand }
400 → { message: "invalid_answers" | "invalid_request" }
503 → { message: "unavailable" }
```

اولویت `reuse` («اول وسایل فعلی‌ام را بررسی کنم») عمداً `products: []` برمی‌گرداند: در آن مسیر
راهنما خرید پیشنهاد نمی‌کند.

## فیلترها

| فیلتر | کار |
| --- | --- |
| `ts_charge_guide_catalog_args` | (اختیاری، در `CatalogAdapter`) آرگومان‌های کوئری ووکامرس را پیش از اجرا تغییر می‌دهد. |

## تست‌ها

```bash
php tests/unit/bootstrap.php            # قرارداد سرآیند افزونه (نام، نسخه، نویسنده، Text Domain)
php tests/unit/run.php                  # ۱۱۳ بررسی: تنظیمات، کاتالوگ، پیشنهاد، ویو، REST، نظم منابع
php tests/js/make-fixture.php           # ساخت فیچر از ویو واقعی
node tests/js/run.js                    # ۳۸ بررسی JS روی jsdom
php tests/browser/preview.php           # صفحه پیش‌نمایش برای اندازه‌گیری در مرورگر
```

سنجش چیدمان با کروم واقعی (عرض ۱۲۸۰/۷۶۸/۳۹۰، حالت روشن و تیره): هیچ عنصری از عرض دید عبور
نمی‌کند، شبکه محصولات ۳/۲/۲ ستونه است، در هر لحظه یک پنل مراقبت باتری و یک پنل عیب‌یابی
دیده می‌شود و صفحه تنها یک `h1` دارد. ظاهر با صفحه زنده راهنما
(<https://www.tehroonspeaker.ir/انتخاب-پاوربانک/>) جزء‌به‌جزء مقایسه شده است؛ روش کار و اختلاف‌های
باقی‌مانده در `docs/reports/2026-10-07-charge-guide-audit.md` آمده است.

## نقشه فایل‌ها

```
ts-charge-guide.php          سرآیند، ثابت‌ها، autoloader، راه‌اندازی روی plugins_loaded
uninstall.php                پاک‌کردن فقط گزینه و ترنزینت‌های خودِ افزونه
includes/src/
  App.php                    ساخت سرویس‌ها و ثبت هوک‌ها
  Settings.php               گزینه، پیش‌فرض‌ها، sanitize، Settings API
  Content.php                همه متن‌های راهنما (کپی رابط، نه داده فروشگاه)
  CatalogAdapter.php         خواندن زنده محصولات دو دسته + کش + محدودسازی
  Recommendation.php         نگاشت پاسخ‌ها به نتیجه (کلاس خالص)
  GuideView.php              رندر کامل راهنما با escape در هر sink
  LandingPage.php            template_include + شورت‌کد + تشخیص درخواست راهنما
  Assets.php                 بارگذاری مشروط CSS و ماژول‌های JS
  AdminScreens.php           صفحه تنظیمات + بررسی لحظه‌ای کاتالوگ + پاک‌کردن کش
  Rest/Controller.php        لایه مشترک REST (بدنه محدود، no-store، خطای کنترل‌شده)
  Rest/RecommendController.php  مسیر recommend
templates/page.php           برگه راهنما با سرصفحه/پاصفحه قالب
assets/token-bridge.css      اتصال --cg-* به توکن‌های قالب
assets/style.css             ظاهر راهنما (فقط با توکن‌ها)
assets/js/{entry,rest,dom,wizard,panels}.js   ماژول‌های ES با Script Modules API
assets/images/               تصویرهای راهنما
tests/unit, tests/js, tests/browser          تست‌ها و پیش‌نمایش
docs/reports/                گزارش‌ها، از جمله بازبینی نسخه ۰.۳.۰
```

## انتشار

افزونه پوشه مستقل و مخزن خودش است (`origin git@github.com:keyvansolha/ts-charge-guide.git`).
پس از هر تغییر رفتار، شماره نسخه فقط در سرآیند `ts-charge-guide.php` بالا می‌رود؛ ثابت
`TS_CHARGE_GUIDE_VERSION` همان مقدار را می‌خواند و همه دارایی‌ها با همان نسخه بار می‌شوند. کش برگه
را بعد از انتشار (و هر تغییر محتوایی) پاک کن.
