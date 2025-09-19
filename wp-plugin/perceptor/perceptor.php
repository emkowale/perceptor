<?php
/**
 * Plugin Name: Perceptor
 * Description: BearTrax Perceptor — dashboard, live preview, and settings.
 * Version: 0.1.9
 * Update URI: github.com/emkowale/perceptor
 * Author: Eric Kowalewski
 * Requires PHP: 8.3
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/includes/db.php';
register_activation_hook(__FILE__, 'perceptor_install_db');

define('PERCEPTOR_VERSION', '0.1.9');
define('PERCEPTOR_MENU', 'perceptor-root');
define('PERCEPTOR_LEGACY_DIR', rtrim(plugin_dir_path(__FILE__), "/\\") . '/perceptor-legacy');

/** Lazily load legacy page functions on demand (avoids redeclare + missing callbacks) */
function perceptor_load_legacy_pages(): void {
  if (!function_exists('perceptor_dashboard_page')) {
    $p = PERCEPTOR_LEGACY_DIR . '/dashboard.php'; if (is_readable($p)) require_once $p;
  }
  if (!function_exists('perceptor_settings_page')) {
    $p = PERCEPTOR_LEGACY_DIR . '/settings.php';  if (is_readable($p)) require_once $p;
  }
  if (!function_exists('perceptor_preview_page')) {
    $p = PERCEPTOR_LEGACY_DIR . '/preview.php';   if (is_readable($p)) require_once $p;
  }
  // Load legacy AJAX registrations if present
  $ajax = PERCEPTOR_LEGACY_DIR . '/ajax.php'; if (is_readable($ajax)) require_once $ajax;
}

/** Wrapper callbacks that guarantee functions exist at render time */
function perceptor_admin_dashboard() { perceptor_load_legacy_pages();
  if (function_exists('perceptor_dashboard_page')) { perceptor_dashboard_page(); return; }
  printf('<div class="error"><p>Legacy dashboard not found at <code>%s</code></p></div>', esc_html(PERCEPTOR_LEGACY_DIR.'/dashboard.php'));
}
function perceptor_admin_settings() { perceptor_load_legacy_pages();
  if (function_exists('perceptor_settings_page')) { perceptor_settings_page(); return; }
  printf('<div class="error"><p>Legacy settings not found at <code>%s</code></p></div>', esc_html(PERCEPTOR_LEGACY_DIR.'/settings.php'));
}
function perceptor_admin_preview() { perceptor_load_legacy_pages();
  if (function_exists('perceptor_preview_page')) { perceptor_preview_page(); return; }
  printf('<div class="error"><p>Legacy preview not found at <code>%s</code></p></div>', esc_html(PERCEPTOR_LEGACY_DIR.'/preview.php'));
}

/** Admin menu uses our wrappers (never missing) */
add_action('admin_menu', function () {
  add_menu_page('Perceptor','Perceptor','manage_options', PERCEPTOR_MENU, 'perceptor_admin_dashboard','dashicons-video-alt3',3);
  add_submenu_page(PERCEPTOR_MENU,'Dashboard','Dashboard','manage_options','perceptor-dashboard','perceptor_admin_dashboard');
  add_submenu_page(PERCEPTOR_MENU,'Live Preview','Live Preview','manage_options','perceptor-preview','perceptor_admin_preview');
  add_submenu_page(PERCEPTOR_MENU,'Settings','Settings','manage_options','perceptor-settings','perceptor_admin_settings');
  remove_submenu_page(PERCEPTOR_MENU, PERCEPTOR_MENU);
});

/** Enqueue legacy assets on our pages */
add_action('admin_enqueue_scripts', function($hook){
  if (strpos($hook, 'perceptor') === false) return;
  $js  = PERCEPTOR_LEGACY_DIR . '/perceptor-admin.js';
  $css = PERCEPTOR_LEGACY_DIR . '/perceptor-admin.css';
  if (is_readable($js))  wp_enqueue_script('perceptor-legacy-admin', plugins_url('perceptor-legacy/perceptor-admin.js', __FILE__), ['jquery'], PERCEPTOR_VERSION, true);
  if (is_readable($css)) wp_enqueue_style('perceptor-legacy-admin',  plugins_url('perceptor-legacy/perceptor-admin.css', __FILE__), [], PERCEPTOR_VERSION);
});

/** Keep your existing modules (unchanged) */
require __DIR__ . '/updater.php';
require __DIR__ . '/preview-api.php';
require __DIR__ . '/queue.php';
