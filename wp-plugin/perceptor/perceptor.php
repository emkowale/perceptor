<?php
/**
 * Plugin Name: Perceptor
 * Description: BearTrax Perceptor — dashboard, live preview, and settings.
 * Version: 0.1.0
 * Author: Eric Kowalewski
 * Requires PHP: 8.3
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

define('PERCEPTOR_VERSION', '0.1.0');
define('PERCEPTOR_MENU', 'perceptor-root');

/** Admin menu: Dashboard, Live Preview, Settings */
add_action('admin_menu', function () {
  add_menu_page('Perceptor', 'Perceptor', 'manage_options', PERCEPTOR_MENU, 'perceptor_dashboard_page', 'dashicons-video-alt3', 3);
  add_submenu_page(PERCEPTOR_MENU, 'Dashboard', 'Dashboard', 'manage_options', 'perceptor-dashboard', 'perceptor_dashboard_page');
  add_submenu_page(PERCEPTOR_MENU, 'Live Preview', 'Live Preview', 'manage_options', 'perceptor-preview', 'perceptor_preview_page');
  add_submenu_page(PERCEPTOR_MENU, 'Settings', 'Settings', 'manage_options', 'perceptor-settings', 'perceptor_settings_page');
});

/** Placeholder pages (we will split into small files next) */
function perceptor_dashboard_page() {
  echo "<div class='wrap'><h1>Perceptor Dashboard</h1><p>Coming soon.</p></div>";
}
function perceptor_preview_page() {
  echo "<div class='wrap'><h1>Perceptor — Live Preview</h1><p>Waiting for stream…</p></div>";
}
function perceptor_settings_page() {
  echo "<div class='wrap'><h1>Perceptor Settings</h1><p>Configure endpoint and secret here.</p></div>";
}

// Next steps (each ≤100 lines):
// require __DIR__.'/updater.php';
// require __DIR__.'/dashboard.php';
// require __DIR__.'/preview.php';
// require __DIR__.'/preview-api.php';
// require __DIR__.'/settings.php';

require __DIR__.'/updater.php';
