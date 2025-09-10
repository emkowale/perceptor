<?php declare(strict_types=1);
require __DIR__.'/../bin/lib.php'; env_load();
$cfg = json_decode(@file_get_contents(__DIR__.'/../config/cameras.json'), true) ?? [];
$cam = $_POST['camera'] ?? ''; $dur = max(1, (int)($_POST['duration'] ?? 0)); $s = (($_POST['stream'] ?? '1') === '2') ? '2' : '1';
$ip  = $cfg[$cam]['ip'] ?? '';
if (!$cam || !$dur || !$ip) { http_response_code(400); exit('bad input'); }

$rtsp = "rtsp://$cam:password@$ip:554/stream$s";
$out  = dirname(__DIR__) . '/captures'; if (!is_dir($out)) @mkdir($out, 0775, true);
$file = $out . '/' . sprintf('%s_%ss_%s.mp4', $cam, $dur, date('Ymd_His'));
$log  = $out . '/ffmpeg.log'; @touch($log);

$vf = '-vf ' . escapeshellarg('crop=trunc(iw/2)*2:trunc((ih-140)/2)*2:0:70');
$cmd = sprintf('/usr/bin/ffmpeg -hide_banner -y -loglevel error -rtsp_transport tcp -i %s -t %d -an %s -pix_fmt yuv420p -c:v libx264 -preset veryfast -crf 22 -movflags +faststart %s 2>> %s',
  escapeshellarg($rtsp), $dur, $vf, escapeshellarg($file), escapeshellarg($log));

exec($cmd, $o, $rc);
if ($rc !== 0 || !file_exists($file) || filesize($file) < 1024) {
  http_response_code(500); echo "capture failed\n$cmd\n"; @passthru('tail -n 100 ' . escapeshellarg($log)); exit;
}

$EP = getenv('WP_ENDPOINT') ?: ''; $SEC = getenv('WP_SECRET') ?: '';
[$code, $resp, $err] = wp_post_file($EP, $SEC, [
  'camera' => $cam, 'duration' => $dur, 'captured' => date('Y-m-d H:i:s'), 'sha256' => hash_file('sha256', $file)
], 'file', $file, 'video/mp4');

header('Content-Type: application/json');
echo json_encode(['ok' => ($code >= 200 && $code < 300), 'local' => basename($file), 'wpCode' => $code, 'resp' => $resp ?: $err]);
