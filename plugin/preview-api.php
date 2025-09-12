<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Preview REST API for Perceptor
 * POST /wp-json/perceptor/v1/preview_start  { camera: "camera1" }
 * GET  /wp-json/perceptor/v1/preview_url?camera=camera1
 */

require_once __DIR__ . '/queue.php';

add_action('rest_api_init', function () {
  register_rest_route('perceptor/v1','/preview_start', [
    'methods' => 'POST',
    'permission_callback' => function(){ return current_user_can('manage_options'); },
    'callback' => function(WP_REST_Request $r){
      $camera = (string)($r->get_param('camera') ?? '');
      if (!$camera) return new WP_Error('bad','missing camera', ['status'=>400]);

      // push a snapshot job to the worker queue
      if (!function_exists('_pc_jobs')) return new WP_Error('jobs','queue not available', ['status'=>500]);
      $q = _pc_jobs();
      $job = [
        'id' => uniqid('job_', true),
        'type' => 'snapshot',
        'camera' => $camera,
        'ts' => time()
      ];
      $q[] = $job;
      _pc_jobs_save($q);
      return ['ok'=>true,'job'=>$job];
    }
  ]);

  register_rest_route('perceptor/v1','/preview_url', [
    'methods' => 'GET',
    'permission_callback' => function(){ return current_user_can('manage_options'); },
    'callback' => function(WP_REST_Request $r){
      $camera = (string)($r->get_param('camera') ?? '');
      if (!$camera) return new WP_Error('bad','missing camera', ['status'=>400]);

      // find most recent attachment with perceptor_camera meta matching numeric camera id
      // camera values are like "camera1" -> extract the numeric part
      if (preg_match('/camera(\d+)/', $camera, $m)) {
        $camNum = intval($m[1]);
      } else {
        $camNum = 0;
      }

      $args = [
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'post_status' => 'inherit',
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
          ['key'=>'perceptor_camera','value'=>$camNum,'compare'=>'=']
        ]
      ];
      $q = new WP_Query($args);
      if (empty($q->posts)) return ['ok'=>false,'message'=>'no_snapshot'];
      $p = $q->posts[0];
      return ['ok'=>true,'snapshot_url'=>wp_get_attachment_url($p->ID),'id'=>$p->ID];
    }
  ]);
});
