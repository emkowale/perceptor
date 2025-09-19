<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Ajax: return HTML for recent Perceptor uploads
 */
add_action('wp_ajax_perceptor_recent', function() {
  // Get the 5 most recent attachments uploaded by Perceptor
  $q = new WP_Query([
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'posts_per_page' => 5,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => [
      [
        'key'     => 'perceptor_camera',
        'compare' => 'EXISTS'
      ]
    ]
  ]);

  if (!$q->have_posts()) {
    echo "<p>No recent uploads.</p>";
    wp_die();
  }

  echo "<ul class='perceptor-recent-list'>";
  while ($q->have_posts()) {
    $q->the_post();
    $url    = wp_get_attachment_url(get_the_ID());
    $title  = esc_html(get_the_title());
    $camera = esc_html(get_post_meta(get_the_ID(), 'perceptor_camera', true));
    $time   = esc_html(get_the_date('Y-m-d H:i:s'));

    echo "<li>";
    echo "<a href='{$url}' target='_blank'>{$title}</a>";
    echo " <small>(Camera {$camera}, {$time})</small>";
    echo "</li>";
  }
  echo "</ul>";

  wp_reset_postdata();
  wp_die();
});
