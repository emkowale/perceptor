<?php
// Perceptor DB helpers (<=100 lines)
defined('ABSPATH') || exit;

function perceptor_cameras_table(): string {
  global $wpdb;
  return $wpdb->prefix . 'perceptor_cameras';
}

function perceptor_install_db(): void {
  global $wpdb;
  $table   = perceptor_cameras_table();
  $charset = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table (
    mac        varchar(17)  NOT NULL,
    name       varchar(100) NOT NULL,
    last_ip    varchar(45)  NULL,
    state      varchar(8)   NOT NULL DEFAULT 'down',
    last_seen  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (mac)
  ) $charset;";

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}
