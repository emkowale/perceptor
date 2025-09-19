<?php
/** Perceptor AJAX enqueue handler (unified with queue.php) */
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/queue.php';

add_action('wp_ajax_perceptor_enqueue', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['msg' => 'Permission denied']);
    }

    $camera   = sanitize_text_field($_POST['camera'] ?? '');
    $duration = intval($_POST['duration'] ?? 0);

    if (!$camera || $duration <= 0) {
        wp_send_json_error(['msg' => 'Missing or invalid parameters']);
    }

    $q = _pc_jobs();
    $job = [
        'id'       => uniqid('job_', true),
        'type'     => 'capture',
        'camera'   => $camera,
        'duration' => $duration,
        'ts'       => time(),
    ];
    $q[] = $job;
    _pc_jobs_save($q);

    wp_send_json_success(['job' => $job]);
});
