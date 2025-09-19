<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Auth: require secret in POST body to match the WP option.
 * Route: /wp-json/perceptor/v1/upload
 * Accepts multipart field "file" plus (camera, duration, sha256, secret).
 */
add_action('rest_api_init', function () {
  register_rest_route('perceptor/v1','/upload', [
    'methods' => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function(WP_REST_Request $r){
      $provided = (string)($r->get_param('secret') ?? '');
      $expected = (string)get_option('perceptor_secret','');
      if (!$expected || !hash_equals($expected, $provided)) {
        return new WP_Error('auth','bad secret',['status'=>401]);
      }

      $f = $r->get_file_params()['file'] ?? null;
      if (!$f || !is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE)) {
        return new WP_Error('upload','no file',['status'=>400]);
      }

      require_once ABSPATH.'wp-admin/includes/file.php';
      require_once ABSPATH.'wp-admin/includes/media.php';
      require_once ABSPATH.'wp-admin/includes/image.php';

      $moved = wp_handle_sideload($f, ['test_form' => false]);
      if (!empty($moved['error'])) return new WP_Error('upload',$moved['error'],['status'=>500]);

      $file = $moved['file']; 
      $type = $moved['type'] ?: 'video/mp4';
      $name = sanitize_file_name(basename($file));

      $attach_id = wp_insert_attachment([
        'post_mime_type' => $type,
        'post_title'     => $name,
        'post_content'   => '',
        'post_status'    => 'inherit'
      ], $file);

      if (is_wp_error($attach_id) || !$attach_id) return new WP_Error('attach','fail',['status'=>500]);

      // Generate metadata
      $meta = wp_generate_attachment_metadata($attach_id, $file);
      wp_update_attachment_metadata($attach_id, $meta);

      // Save camera number into post meta
      $camera = intval($r->get_param('camera') ?? 0);
      update_post_meta($attach_id, 'perceptor_camera', $camera);

      return [
        'ok'       => true,
        'id'       => $attach_id,
        'url'      => wp_get_attachment_url($attach_id),
        'camera'   => $camera,
        'duration' => intval($r->get_param('duration') ?? 0),
        'sha256'   => sanitize_text_field($r->get_param('sha256') ?? '')
      ];
    }
  ]);
});
