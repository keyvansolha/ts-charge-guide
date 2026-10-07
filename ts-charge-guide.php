<?php
/**
 * Plugin Name: TehranSpeaker Charge Guide
 * Description: راهنمای تصویری پاوربانک و شارژر؛ شورت‌کد [ts_charge_guide]
 * Version: 0.3.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: TehranSpeaker
 */
if (!defined('ABSPATH')) { exit; }
function ts_charge_guide_enqueue_styles() {
 wp_enqueue_style('ts-charge-guide-embed',plugins_url('assets/embed.css',__FILE__),array(),'0.3.0');
}
add_action('wp_enqueue_scripts','ts_charge_guide_enqueue_styles');
function ts_charge_guide_render_shortcode() {
 ts_charge_guide_enqueue_styles();
 wp_enqueue_script('ts-charge-guide-native',plugins_url('assets/native.js',__FILE__),array(),'0.3.0',true);
 $base=trailingslashit(plugins_url('app',__FILE__));
 $fragment=file_get_contents(__DIR__.'/assets/fragment.html');
 if ($fragment===false) { return '<p>فایل راهنمای شارژ پیدا نشد.</p>'; }
 $fragment=str_replace(array('__BASE__','__NATIVE__'),array(esc_url($base),esc_url(plugins_url('assets/native.css',__FILE__).'?v=0.3.0')),$fragment);
 return '<div class="ts-charge-guide-native" data-base="'.esc_url($base).'"><template>'.$fragment.'</template><noscript>برای نمایش راهنمای تعاملی، جاوااسکریپت را فعال کنید.</noscript></div>';
}
add_shortcode('ts_charge_guide','ts_charge_guide_render_shortcode');
