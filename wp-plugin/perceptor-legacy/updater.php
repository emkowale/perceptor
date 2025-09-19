<?php
declare(strict_types=1);
/**
 * Minimal GitHub updater for the Perceptor plugin.
 * Expects a Release on github.com/emkowale/perceptor with an asset named perceptor.zip
 * whose root folder is "perceptor/" (contains perceptor.php).
 */
if (!defined('ABSPATH')) exit;

add_filter('pre_set_site_transient_update_plugins', function($transient){
  if (empty($transient->checked)) return $transient;

  $plugin = 'perceptor/perceptor.php';
  $current = defined('PERCEPTOR_VERSION') ? PERCEPTOR_VERSION : '0.0.0';

  $api = 'https://api.github.com/repos/emkowale/perceptor/releases/latest';
  $res = wp_remote_get($api, ['headers'=>['User-Agent'=>'Perceptor-Updater']]);
  if (is_wp_error($res)) return $transient;

  $body = json_decode(wp_remote_retrieve_body($res), true);
  if (!$body || empty($body['tag_name'])) return $transient;

  $latest = ltrim((string)$body['tag_name'], 'v'); // e.g., v0.1.1
  if (version_compare($latest, $current, '<=')) return $transient;

  // find perceptor.zip in assets
  $pkg = '';
  foreach (($body['assets'] ?? []) as $a) {
    if (isset($a['name']) && $a['name'] === 'perceptor.zip' && !empty($a['browser_download_url'])) {
      $pkg = $a['browser_download_url']; break;
    }
  }
  if (!$pkg) return $transient; // no properly named asset => no update offered

  $obj = (object)[
    'slug'        => 'perceptor',
    'plugin'      => $plugin,
    'new_version' => $latest,
    'package'     => $pkg, // zip must contain a top-level "perceptor/" directory
    'url'         => 'https://github.com/emkowale/perceptor/releases/latest',
    'tested'      => get_bloginfo('version'),
    'requires_php'=> '8.3'
  ];
  $transient->response[$plugin] = $obj;
  return $transient;
});

add_filter('plugins_api', function($res, $action, $args){
  if ($action !== 'plugin_information' || ($args->slug ?? '') !== 'perceptor') return $res;
  $api = 'https://api.github.com/repos/emkowale/perceptor/releases/latest';
  $r = wp_remote_get($api, ['headers'=>['User-Agent'=>'Perceptor-Updater']]);
  if (is_wp_error($r)) return $res;
  $b = json_decode(wp_remote_retrieve_body($r), true);
  if (!$b) return $res;
  $latest = ltrim((string)$b['tag_name'], 'v');
  $asset = '';
  foreach (($b['assets'] ?? []) as $a) {
    if (($a['name'] ?? '') === 'perceptor.zip') { $asset = $a['browser_download_url']; break; }
  }
  return (object)[
    'name' => 'Perceptor',
    'slug' => 'perceptor',
    'version' => $latest,
    'author' => '<a href="https://thebeartraxs.com/">BearTrax</a>',
    'homepage' => 'https://github.com/emkowale/perceptor',
    'download_link' => $asset ?: 'https://github.com/emkowale/perceptor/releases/latest',
    'sections' => ['description' => 'BearTrax Perceptor — dashboard, live preview, and settings.'],
    'requires_php' => '8.3'
  ];
}, 10, 3);
