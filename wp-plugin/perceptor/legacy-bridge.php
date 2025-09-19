<?php
// legacy-bridge.php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$__base = plugin_dir_path(__FILE__);
if (!defined('PERCEPTOR_LEGACY_DIR')) {
  define('PERCEPTOR_LEGACY_DIR', rtrim($__base, "/\\") . '/perceptor-legacy');
}

/** Renderers that load legacy pages */
function perceptor_dashboard_page() {
  $f = PERCEPTOR_LEGACY_DIR . '/dashboard.php';
  if (is_readable($f)) { include $f; return; }
  printf('<div class="error"><p>Legacy dashboard missing at: <code>%s</code></p></div>', esc_html($f));
}
function perceptor_settings_page() {
  $f = PERCEPTOR_LEGACY_DIR . '/settings.php';
  if (is_readable($f)) { include $f; return; }
  printf('<div class="error"><p>Legacy settings missing at: <code>%s</code></p></div>', esc_html($f));
}
function perceptor_preview_page() {
  $f = PERCEPTOR_LEGACY_DIR . '/preview.php';
  if (is_readable($f)) { include $f; return; }
  printf('<div class="error"><p>Legacy preview missing at: <code>%s</code></p></div>', esc_html($f));
}

/** Enqueue legacy admin JS/CSS on Perceptor pages only */
add_action('admin_enqueue_scripts', function($hook){
  if (strpos($hook, 'perceptor') === false) return;
  $js_path  = PERCEPTOR_LEGACY_DIR . '/perceptor-admin.js';
  $css_path = PERCEPTOR_LEGACY_DIR . '/perceptor-admin.css';
  if (is_readable($js_path)) {
    wp_enqueue_script('perceptor-legacy-admin',
      plugins_url('perceptor-legacy/perceptor-admin.js', __FILE__),
      ['jquery'], defined('PERCEPTOR_VERSION')?PERCEPTOR_VERSION:'0.0.0', true);
  }
  if (is_readable($css_path)) {
    wp_enqueue_style('perceptor-legacy-admin',
      plugins_url('perceptor-legacy/perceptor-admin.css', __FILE__),
      [], defined('PERCEPTOR_VERSION')?PERCEPTOR_VERSION:'0.0.0');
  }
});

/** Helper to include a legacy ajax file and exit */
function perceptor_legacy_ajax_include(string $file): void {
  $path = PERCEPTOR_LEGACY_DIR . '/' . ltrim($file, '/');
  if (!is_readable($path)) wp_send_json_error(['ok'=>false,'error'=>'missing '.$file,'path'=>$path], 404);
  include $path; exit;
}

/** AJAX bindings → legacy handlers */
add_action('wp_ajax_perceptor_status',  function(){ perceptor_legacy_ajax_include('ajax-status.php'); });
add_action('wp_ajax_perceptor_capture', function(){ perceptor_legacy_ajax_include('ajax-capture.php'); });
add_action('wp_ajax_perceptor_recent',  function(){ perceptor_legacy_ajax_include('ajax-recent.php'); });
add_action('wp_ajax_perceptor_ping',    function(){ perceptor_legacy_ajax_include('ajax-ping.php'); });
