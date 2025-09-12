<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** Admin-AJAX: return worker heartbeat status (green if last ping < 120s) */
add_action('wp_ajax_perceptor_status', function(){
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['code'=>'cap'],403);
  }

  $last = intval(get_option('perceptor_last_ping',0));
  $age  = time() - $last;
  $ok   = ($last>0 && $age < 120); // 2 minute window

  wp_send_json_success(['ok'=>$ok,'last'=>$last,'age'=>$age,'now'=>time()]);
});
