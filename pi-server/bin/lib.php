<?php declare(strict_types=1);

/** Load key=value lines from /etc/perceptor.env into getenv() */
function env_load(string $path='/etc/perceptor.env'): void {
  if (!is_readable($path)) return;
  foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
    if ($l[0] === '#') continue;
    [$k, $v] = array_map('trim', explode('=', $l, 2));
    if ($k !== '') putenv("$k=$v");
  }
}

/** POST a file to WP — send fields in multipart body (secret/camera/duration/sha256) and keep headers too */
function wp_post_file(
  string $url, string $secret, array $payload,
  string $field, string $file, string $mime
): array {
  $ts  = time();
  $p   = json_encode($payload + ['ts' => $ts], JSON_UNESCAPED_SLASHES);
  $sig = hash_hmac('sha256', $p, $secret);

  $post = [
    $field     => new CURLFile($file, $mime, basename($file)),
    'secret'   => $secret,
    'payload'  => $p,
    'sig'      => $sig,
    'camera'   => (string)($payload['camera']   ?? ''),
    'duration' => (string)($payload['duration'] ?? 0),
    'sha256'   => (string)($payload['sha256']   ?? ''),
  ];

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $post,
    CURLOPT_HTTPHEADER     => [
      'X-Perceptor-Date: ' . $ts,
      'X-Perceptor-Signature: ' . $sig,
      'X-Perceptor-Payload: ' . $p
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 60
  ]);
  $resp = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);
  return [$code, $resp, $err];
}
