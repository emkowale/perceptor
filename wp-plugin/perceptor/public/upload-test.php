<?php
require_once __DIR__.'/uploader.php';
$path = __DIR__.'/../captures/dummy.mp4';
[$code, $resp, $err] = perceptor_upload_to_wp($path, 'camera1', 2, date('Y-m-d H:i:s'));
header('Content-Type: text/plain');
echo "HTTP: $code\n";
echo "ERR: $err\n";
echo "RESP:\n$resp\n";
