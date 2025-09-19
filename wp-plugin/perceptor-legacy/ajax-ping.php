<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * REST endpoint: /wp-json/perceptor/v1/ping
 * Purpose: worker heartbeat to prove it's alive.
 */
add_action('rest_api_init', function () {
  register_rest_route('perceptor/v1','/ping', [
    'methods' => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function(WP_REST_Request $r){
      $provided = (string)($r->get_param('secret') ?? '');
      $expected = (string)get_option('perceptor_secret','');
      if (!$expected || !hash_equals($expected, $provided)) {
        return new WP_Error('auth','bad secret',['status'=>401]);
      }

      update_option('perceptor_last_ping', time(), false);
      return ['ok'=>true,'ts'=>time()];
    }
  ]);
});
