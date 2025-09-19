<?php
// legacy-bridge.php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

define('PERCEPTOR_LEGACY_DIR', __DIR__ . '/perceptor-legacy');

/** Renderers: reuse legacy pages without changing their code */
function perceptor_dashboard_page() {
  $f = PERCEPTOR_LEGACY_DIR.'/dashboard.php';
  if (is_readable($f)) include $f; else echo '<div class="error"><p>Legacy dashboard missing.</p></div>';
}
function perceptor_settings_page() {
  $f = PERCEPTOR_LEGACY_DIR.'/settings.php';
  if (is_readable($f)) include $f; else echo '<div class="error"><p>Legacy settings missing.</p></div>';
}
function perceptor_preview_page() {
  $f = PERCEPTOR_LEGACY_DIR.'/preview.php';
  if (is_readable($f)) include $f; else echo '<div class="error"><p>Legacy preview missing.</p></div>';
}

/** Enqueue legacy admin JS/CSS on Perceptor pages only */
add_action('admin_enqueue_scripts', function($hook){
  if (strpos($hook, 'perceptor') === false) return;
  $js = PERCEPTOR_LEGACY_DIR.'/perceptor-admin.js';
  if (is_readable($js)) {
    wp_enqueue_script('perceptor-legacy-admin', plugins_url('perceptor-legacy/perceptor-admin.js', __FILE__), ['jquery'], PERCEPTOR_VERSION ?? '0.0.0', true);
  }
  $css = PERCEPTOR_LEGACY_DIR.'/perceptor-admin.css';
  if (is_readable($css)) {
    wp_enqueue_style('perceptor-legacy-admin', plugins_url('perceptor-legacy/perceptor-admin.css', __FILE__), [], PERCEPTOR_VERSION ?? '0.0.0');
  }
});

/** Helper to include an ajax file and exit safely */
function perceptor_legacy_ajax_include(string $file): void {
  $path = PERCEPTOR_LEGACY_DIR . '/' . $file;
  if (!is_readable($path)) wp_send_json_error(['ok'=>false,'error'=>'missing '.$file], 404);
  include $path;
  // legacy files usually echo/exit; if not, ensure we end here:
  if (!wp_doing_ajax()) exit;
}

/** AJAX bindings → legacy handlers */
add_action('wp_ajax_perceptor_status', function(){ perceptor_legacy_ajax_include('ajax-status.php'); });
add_action('wp_ajax_perceptor_capture', function(){ perceptor_legacy_ajax_include('ajax-capture.php'); });
add_action('wp_ajax_perceptor_recent', function(){ perceptor_legacy_ajax_include('ajax-recent.php'); });
add_action('wp_ajax_perceptor_ping',   function(){ perceptor_legacy_ajax_include('ajax-ping.php'); });

/* In case legacy JS calls these names, bind aliases too */
add_action('wp_ajax_perceptor_legacy_status',  function(){ perceptor_legacy_ajax_include('ajax-status.php'); });
add_action('wp_ajax_perceptor_legacy_capture', function(){ perceptor_legacy_ajax_include('ajax-capture.php'); });
add_action('wp_ajax_perceptor_legacy_recent',  function(){ perceptor_legacy_ajax_include('ajax-recent.php'); });
add_action('wp_ajax_perceptor_legacy_ping',    function(){ perceptor_legacy_ajax_include('ajax-ping.php'); });
