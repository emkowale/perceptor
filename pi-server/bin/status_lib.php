<?php
// Perceptor status helper (<=100 lines)
// Reads cameras.json (name+mac), resolves IPs, probes RTSP, posts to WP.
// Usage from pinger.php:  require_once __DIR__.'/status_lib.php'; perceptor_status_tick();

function perceptor_status_tick(int $minIntervalSec = 60): array {
  static $last = 0;
  $now = time();
  if ($now - $last < $minIntervalSec) return [];
  $last = $now;

  $cfgFile = '/var/www/perceptor/config/cameras.json';
  $cams = is_file($cfgFile) ? json_decode(@file_get_contents($cfgFile), true) : [];
  if (!is_array($cams)) $cams = [];

  $env = is_file('/etc/perceptor.env') ? @parse_ini_file('/etc/perceptor.env') : [];
  $endpoint = isset($env['WP_ENDPOINT']) ? rtrim($env['WP_ENDPOINT'], '/') : '';
  $secret   = $env['WP_SECRET']   ?? '';
  $node     = $env['NODE_ID']     ?? 'ccpi';
  $timeout  = (int)($env['PROBE_TIMEOUT_SEC'] ?? 3);

  $out = [];
  foreach ($cams as $cam) {
    if (empty($cam['mac'])) continue;
    $mac = strtoupper($cam['mac']);
    $ip  = perceptor_mac_to_ip($mac);
    $ok  = perceptor_probe_rtsp($ip, $timeout);
    $out[] = [
      'mac'       => $mac,
      'name'      => $cam['name'] ?? $mac,
      'ip'        => $ip,
      'state'     => $ok ? 'up' : 'down',
      'timestamp' => $now,
      'node_id'   => $node,
    ];
  }

  if ($endpoint && $secret && $out) {
    $url = $endpoint . '/wp-json/perceptor/v1/status';
    @perceptor_http_post_json($url, $secret, $out);
  }
  return $out;
}

function perceptor_mac_to_ip(string $mac): ?string {
  $macArg = escapeshellarg($mac);
  $n = @shell_exec("ip neigh | grep -i $macArg");
  if ($n && preg_match('/^([0-9.]+)/', $n, $m)) return $m[1];
  $a = @shell_exec("arp -an | grep -i $macArg");
  if ($a && preg_match('/\(([\d.]+)\)/', $a, $m)) return $m[1];
  return null;
}

function perceptor_probe_rtsp(?string $ip, int $timeout): bool {
  if (!$ip) return false;
  // Creds are fixed as requested: username=username, password=password
  $rtsp = "rtsp://username:password@$ip:554/stream1";
  $cmd = "timeout " . intval($timeout)
       . " ffprobe -v error -select_streams v:0 -show_entries stream=codec_type"
       . " -of csv=p=0 " . escapeshellarg($rtsp) . " 2>/dev/null";
  $out = @shell_exec($cmd);
  return trim((string)$out) !== '';
}

function perceptor_http_post_json(string $url, string $secret, array $payload): void {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'X-Perceptor-Secret: ' . $secret
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 6,
  ]);
  @curl_exec($ch);
  @curl_close($ch);
}
