<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** Admin-AJAX: enqueue capture job for worker */
add_action('wp_ajax_perceptor_capture', function() {
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['code'=>'cap'],403);
  }

  $camera   = intval($_POST['camera'] ?? 0);
  $duration = intval($_POST['duration'] ?? 10);

  if ($camera < 1 || $camera > 6) {
    wp_send_json_error(['msg'=>'Invalid camera']);
  }
  if ($duration < 1) $duration = 1;

  // Direct call into REST handler (avoid loopback 401)
  $request = new WP_REST_Request('POST', '/perceptor/v1/job_enqueue');
  $request->set_param('type', 'capture');
  $request->set_param('camera', $camera);
  $request->set_param('duration', $duration);
  $response = rest_do_request($request);
  $data = $response->get_data();

  if (!$data || empty($data['ok'])) {
    wp_send_json_error(['msg'=>'Enqueue failed','resp'=>$data]);
  }

  wp_send_json_success([
    'msg'      => 'Capture job enqueued',
    'job'      => $data['job'] ?? null,
    'camera'   => $camera,
    'duration' => $duration,
  ]);
});
