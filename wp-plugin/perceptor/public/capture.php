<?php
// Inputs
$camera   = $_POST['camera']  ?? '';
$duration = max(1, intval($_POST['duration'] ?? 0));
$stream   = ($_POST['stream'] ?? '1') === '2' ? '2' : '1';
$crop     = $_POST['crop'] ?? 'soft'; // soft | none | delogo
if (!$camera || !$duration) { http_response_code(400); exit('Missing fields.'); }

// Config
$cfg = json_decode(file_get_contents(__DIR__.'/../config/cameras.json'), true);
$ip  = $cfg[$camera]['ip'] ?? '';
if (!$ip) { http_response_code(400); exit('Camera IP not set.'); }

// Overlay config (for delogo)
$overlayCfgPath = __DIR__.'/../config/overlays.json';
$overlayCfg = file_exists($overlayCfgPath) ? json_decode(file_get_contents($overlayCfgPath), true) : [];
$ov = $overlayCfg[$camera] ?? null;

// RTSP URL
$user = $camera; $pass = 'password';
$rtsp = "rtsp://$user:$pass@$ip:554/stream$stream";

// Output paths
$outDir = __DIR__ . '/../captures';
if (!is_dir($outDir)) @mkdir($outDir, 0775, true);
$ts   = date('Ymd_His');
$file = sprintf('%s_%ss_%s.mp4', $camera, $duration, $ts);
$path = $outDir . "/$file";

// Build filter
$vf = '';
if ($crop === 'soft') {
  $vf = '-vf ' . escapeshellarg('crop=trunc(iw/2)*2:trunc((ih-120)/2)*2:0:60');
} elseif ($crop === 'delogo' && $ov && isset($ov['top'], $ov['bottom'])) {
  $t = $ov['top'];    $b = $ov['bottom'];
  // sanitize ints
  foreach (['x','y','w','h'] as $k) { $t[$k] = intval($t[$k]); $b[$k] = intval($b[$k]); }
  $fltr = sprintf('delogo=x=%d:y=%d:w=%d:h=%d:show=0,delogo=x=%d:y=%d:w=%d:h=%d:show=0',
                  $t['x'],$t['y'],$t['w'],$t['h'], $b['x'],$b['y'],$b['w'],$b['h']);
  $vf = '-vf ' . escapeshellarg($fltr);
} // else none

// Record
$ffmpeg = '/usr/bin/ffmpeg';
$log    = $outDir . '/ffmpeg.log';
@touch($log);

$cmd = sprintf(
  '%s -hide_banner -y -loglevel error -rtsp_transport tcp -i %s -t %d -an %s -pix_fmt yuv420p -c:v libx264 -preset veryfast -crf 22 -movflags +faststart %s 2>> %s',
  escapeshellcmd($ffmpeg),
  escapeshellarg($rtsp),
  $duration,
  $vf,
  escapeshellarg($path),
  escapeshellarg($log)
);

exec($cmd, $o, $rc);

if ($rc !== 0 || !file_exists($path) || filesize($path) < 1024) {
  http_response_code(500);
  echo "<pre>Capture failed.\nCommand:\n$cmd\n\nLog tail:\n";
  @passthru('tail -n 120 ' . escapeshellarg($log));
  echo "</pre>";
  exit;
}

// Auto-upload to WordPress
require_once __DIR__.'/uploader.php';
[$code, $resp, $err] = perceptor_upload_to_wp($path, $camera, $duration, date('Y-m-d H:i:s'));

// Show result
header('Content-Type: text/html; charset=utf-8');
if ($code >= 200 && $code < 300) {
  $data = json_decode($resp, true);
  $wpUrl = $data['url'] ?? null;
  echo "<div style='font:14px system-ui; padding:16px'>
          <p><b>Capture saved locally:</b> <a href='/../captures/".htmlspecialchars($file)."' download>download</a></p>";
  if ($wpUrl) {
    echo "<p><b>Uploaded to WordPress:</b> <a href='".htmlspecialchars($wpUrl)."' target='_blank'>".htmlspecialchars($wpUrl)."</a></p>";
  } else {
    echo "<p><b>Upload OK</b> (no URL in response):</p><pre>".htmlspecialchars($resp)."</pre>";
  }
  echo "</div>";
  exit;
} else {
  echo "<div style='font:14px system-ui; padding:16px'>
          <p><b>Capture saved locally:</b> <a href='/../captures/".htmlspecialchars($file)."' download>download</a></p>
          <p style='color:#b00'><b>Upload to WordPress failed</b> (HTTP $code):</p>
          <pre>".htmlspecialchars($resp ?: $err)."</pre>
        </div>";
  exit;
}
