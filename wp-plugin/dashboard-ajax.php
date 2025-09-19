<?php
// dashboard-ajax.php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

function perceptor_opts_min(): array {
  $o = get_option('perceptor_options', []);
  $o = wp_parse_args($o, ['default_seconds'=>20,'camera_map'=>[]]);
  if (!is_array($o['camera_map'])) $o['camera_map'] = [];
  return $o;
}
function perceptor_ccpi_online(): bool {
  $last = (int) get_option('perceptor_ccpi_last_seen', 0);
  return ($last > 0) && ( time() - $last < 300 ); // 5 min heartbeat
}

add_action('wp_ajax_perceptor_status', function () {
  check_ajax_referer('perceptor');
  $o = perceptor_opts_min();
  $on = perceptor_ccpi_online();
  $cams = [];
  foreach ($o['camera_map'] as $id=>$name) $cams[] = ['id'=>(string)$id,'online'=>$on];
  wp_send_json(['ccpi'=>$on,'cameras'=>$cams]);
});

add_action('wp_ajax_perceptor_capture', function () {
  check_ajax_referer('perceptor');
  if (!current_user_can('manage_options')) wp_send_json_error(['ok'=>false,'msg'=>'forbidden']);
  $cam = sanitize_text_field($_POST['camera_id'] ?? '');
  $sec = max(1, min(120, (int)($_POST['seconds'] ?? 20)));
  if (!$cam) wp_send_json_error(['ok'=>false,'msg'=>'missing camera']);
  if (!function_exists('perceptor_enqueue_job')) wp_send_json_error(['ok'=>false,'msg'=>'queue.php not loaded']);
  $ok = (bool) perceptor_enqueue_job($cam, $sec);
  wp_send_json(['ok'=>$ok]);
});
