<?php
header('Content-Type: text/plain; charset=utf-8');

$camera='camera1';
$ip='10.255.252.77';
$duration=5;
$outDir = __DIR__.'/../captures';
@mkdir($outDir, 0775, true);
$ts = date('Ymd_His');
$dst = "$outDir/diag_$ts.mp4";

$cmd = sprintf(
  '/usr/bin/ffmpeg -hide_banner -y -loglevel info -rtsp_transport tcp -i %s -t %d -an -vf "crop=trunc(iw/2)*2:trunc((ih-80)/2)*2:0:40" -pix_fmt yuv420p -c:v libx264 -preset veryfast -crf 22 -movflags +faststart %s 2>&1',
  escapeshellarg("rtsp://$camera:password@$ip:554/stream1"),
  $duration,
  escapeshellarg($dst)
);

echo "user: " . trim(shell_exec('id')) . "\n";
echo "which ffmpeg: " . trim(shell_exec('which ffmpeg')) . "\n";
echo "cmd:\n$cmd\n\n";
$output = [];
$rc = 0;
exec($cmd, $output, $rc);
echo "return_code: $rc\n";
echo "stdout+stderr:\n" . implode("\n", $output) . "\n";
echo "file_exists: " . (file_exists($dst) ? 'yes' : 'no') . "\n";
echo "filesize: " . (file_exists($dst) ? filesize($dst) : 0) . "\n";
