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

/** Page includes */
require_once __DIR__."/settings.php";
require_once __DIR__."/preview.php";
require_once __DIR__."/dashboard.php";
require_once __DIR__.'/updater.php';
require_once __DIR__.'/preview-api.php';
require_once __DIR__.'/queue.php';
require_once __DIR__.'/upload.php';
require_once __DIR__.'/ajax.php';
require_once __DIR__.'/ajax-recent.php';
require_once __DIR__.'/ajax-status.php';
require_once __DIR__.'/ajax-ping.php';
require_once __DIR__.'/ajax-capture.php';

/** Hide duplicate top-level submenu */
add_action('admin_menu', function(){ remove_submenu_page(PERCEPTOR_MENU, PERCEPTOR_MENU); }, 999);
