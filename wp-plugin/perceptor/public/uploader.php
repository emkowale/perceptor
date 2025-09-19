<?php
if (!function_exists('perceptor_upload_to_wp')) {
  function perceptor_load_env($path='/etc/perceptor.env') {
    if (!is_readable($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
      if ($line[0]==='#') continue;
      [$k,$v] = array_map('trim', explode('=', $line, 2));
      if ($k !== '') putenv("$k=$v");
    }
  }

  function perceptor_upload_to_wp($localPath, $camera, $duration, $capturedAt) {
    perceptor_load_env();
    $wpEndpoint = getenv('WP_ENDPOINT') ?: '';
    $secret     = getenv('WP_SECRET')   ?: '';

    if (!$wpEndpoint || !$secret) {
      return [0, '', 'Missing WP_ENDPOINT or WP_SECRET in /etc/perceptor.env'];
    }
    if (!file_exists($localPath)) {
      return [0, '', 'Local file not found'];
    }

    $ts   = time();
    $hash = hash_file('sha256', $localPath);
    $payload = json_encode([
      'camera'   => (string)$camera,
      'duration' => intval($duration),
      'captured' => (string)$capturedAt,
      'sha256'   => $hash,
      'ts'       => $ts,
    ], JSON_UNESCAPED_SLASHES);
    $sig = hash_hmac('sha256', $payload, $secret);

    $ch = curl_init($wpEndpoint);
    $post = [
      'camera'      => $camera,
      'duration'    => $duration,
      'captured_at' => $capturedAt,
      'file'        => new CURLFile($localPath, 'video/mp4', basename($localPath)),
    ];
    curl_setopt_array($ch, [
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => $post,
      CURLOPT_HTTPHEADER     => [
        'X-Perceptor-Date: ' . $ts,
        'X-Perceptor-Signature: ' . $sig,
        'X-Perceptor-Payload: ' . $payload, // optional for debugging
      ],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_TIMEOUT        => 120,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $resp, $err];
  }
}
