<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** Minimal FIFO job queue stored in WP option (<=100 LOC). */
function _pc_jobs(){
  $j = get_option('perceptor_jobqueue', []);
  return is_array($j) ? $j : [];
}
function _pc_jobs_save(array $j){
  update_option('perceptor_jobqueue', $j, false);
}
function _pc_hmac_ok_q(array $p){
  $sec = get_option('perceptor_secret','');
  if (!$sec) return new WP_Error('auth','no secret',['status'=>401]);
  $ts = intval($p['ts'] ?? 0);
  if (abs(time() - $ts) > 300) return new WP_Error('auth','ts',['status'=>401]);
  $calc = hash_hmac('sha256', json_encode($p, JSON_UNESCAPED_SLASHES), $sec);
  $sig = strtolower($_SERVER['HTTP_X_PERCEPTOR_SIGNATURE'] ?? '');
  if (!hash_equals($calc, $sig)) return new WP_Error('auth','sig',['status'=>401]);
  return true;
}

add_action('rest_api_init', function(){
  register_rest_route('perceptor/v1', '/job_enqueue', [
    'methods' => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function(WP_REST_Request $r){
      $type = sanitize_text_field($r->get_param('type'));
      $camera = sanitize_text_field($r->get_param('camera'));
      $duration = intval($r->get_param('duration') ?? 0);
      if (!$type || !$camera) return new WP_Error('bad','missing',['status'=>400]);
      $job = [
        'id' => uniqid('job_', true),
        'type' => $type,
        'camera' => $camera,
        'duration' => $duration,
        'ts' => time()
      ];
      $q = _pc_jobs();
      $q[] = $job;
      _pc_jobs_save($q);
      return ['ok' => true, 'job' => $job];
    }
  ]);

  register_rest_route('perceptor/v1', '/job_next', [
    'methods' => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function(WP_REST_Request $r){
      $p = ['ts' => intval($r->get_header('x-perceptor-date') ?: 0)];
      $ok = _pc_hmac_ok_q($p); if (is_wp_error($ok)) return $ok;
      $q = _pc_jobs();
      if (!$q) return ['ok' => true, 'job' => null];
      $job = array_shift($q);
      _pc_jobs_save($q);
      return ['ok' => true, 'job' => $job];
    }
  ]);
});
