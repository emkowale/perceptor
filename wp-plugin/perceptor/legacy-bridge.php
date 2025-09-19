<?php
// legacy-bridge.php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$base = plugin_dir_path(__FILE__);
if (!defined('PERCEPTOR_LEGACY_DIR')) {
  define('PERCEPTOR_LEGACY_DIR', rtrim($base, "/\\") . '/perceptor-legacy');
}

/** Load legacy pages (they define perceptor_*_page functions) */
foreach (['dashboard.php','settings.php','preview.php'] as $f) {
  $p = PERCEPTOR_LEGACY_DIR . '/' . $f;
  if (is_readable($p)) require_once $p;
}

/** Load legacy AJAX registrations (if present) */
$ajax = PERCEPTOR_LEGACY_DIR . '/ajax.php';
if (is_readable($ajax)) require_once $ajax;

/** Enqueue legacy admin assets on Perceptor screens */
add_action('admin_enqueue_scripts', function($hook){
  if (strpos($hook, 'perceptor') === false) return;
  $js  = PERCEPTOR_LEGACY_DIR . '/perceptor-admin.js';
  $css = PERCEPTOR_LEGACY_DIR . '/perceptor-admin.css';
  if (is_readable($js)) {
    wp_enqueue_script(
      'perceptor-legacy-admin',
      plugins_url('perceptor-legacy/perceptor-admin.js', __FILE__),
      ['jquery'],
      defined('PERCEPTOR_VERSION') ? PERCEPTOR_VERSION : '0.0.0',
      true
    );
  }
  if (is_readable($css)) {
    wp_enqueue_style(
      'perceptor-legacy-admin',
      plugins_url('perceptor-legacy/perceptor-admin.css', __FILE__),
      [],
      defined('PERCEPTOR_VERSION') ? PERCEPTOR_VERSION : '0.0.0'
    );
  }
});
