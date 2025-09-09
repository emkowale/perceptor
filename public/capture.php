<?php
$camera   = $_POST['camera']  ?? '';
$duration = intval($_POST['duration'] ?? 0);
$stream   = ($_POST['stream'] ?? '1') === '2' ? '2' : '1';
$crop     = $_POST['crop'] ?? 'soft';
if (!$camera || !$duration) die('Missing fields.');

$cfg = json_decode(file_get_contents(__DIR__.'/../config/cameras.json'), true);
$ip  = $cfg[$camera]['ip'] ?? '';
if (!$ip) die('Camera IP not set.');

$user = $camera; $pass = 'password';
$rtsp = "rtsp://$user:$pass@$ip:554/stream$stream";

@mkdir(__DIR__.'/../captures', 0775, true);
$ts   = date('Ymd_His');
$file = sprintf('%s_%ss_%s.mp4', $camera, $duration, $ts);
$path = realpath(__DIR__.'/../captures') . "/$file";

$vf = ($crop==='soft') ? "-vf crop=iw:ih-80:0:40" : "";

$cmd = sprintf(
  'ffmpeg -hide_banner -loglevel error -rtsp_transport tcp -i %s -t %d -an -pix_fmt yuv420p %s -c:v libx264 -preset veryfast -crf 22 -movflags +faststart %s',
  escapeshellarg($rtsp), $duration, $vf, escapeshellarg($path)
);
exec($cmd, $o, $rc);
if ($rc !== 0 || !file_exists($path)) { http_response_code(500); echo "<pre>Capture failed.\n$cmd\n</pre>"; exit; }

header('Location: /?ok=../captures/'.rawurlencode($file));
