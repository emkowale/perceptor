<?php
// Usage: php preview_pusher.php camera1
// Reads WP_ENDPOINT/WP_SECRET from /etc/perceptor.env (same as clips).
// 1) Asks WP for current preview session for the camera
// 2) Runs ffmpeg to make short HLS segments locally
// 3) Uploads each new/updated file (playlist/segments) to WP with HMAC

if (php_sapi_name() !== 'cli') { echo "CLI only\n"; exit(1); }
$camera = $argv[1] ?? '';
if (!$camera) { echo "usage: php preview_pusher.php <camera>\n"; exit(2); }

function envload($path='/etc/perceptor.env'){
  if (!is_readable($path)) return;
  foreach (file($path, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l){
    if ($l[0]==='#') continue;
    [$k,$v]=array_map('trim', explode('=', $l, 2));
    if ($k!=='') putenv("$k=$v");
  }
}

function get_rtsp($cam, $ip){
  $user=$cam; $pass='password';
  return "rtsp://$user:$pass@$ip:554/stream1";
}

function get_camera_ip($cam){
  $cfg = json_decode(@file_get_contents(__DIR__.'/../config/cameras.json'), true);
  return $cfg[$cam]['ip'] ?? '';
}

function wp_preview_url($endpointBase, $camera){
  // endpointBase is like https://site/wp-json/perceptor/v1/upload
  $base = preg_replace('#/upload$#','/preview_url',$endpointBase);
  $url  = $base . '?camera=' . rawurlencode($camera);
  $j = @json_decode(@file_get_contents($url), true);
  if (!$j || empty($j['ok'])) return [false, null, null];
  return [true, $j['session'], $j['playlist_url']];
}

function upload_file($endpointBase, $secret, $session, $localFile, $name){
  $ep = preg_replace('#/upload$#','/preview_chunk',$endpointBase);
  $ts = time();
  $payload = json_encode(['session'=>$session,'name'=>$name,'ts'=>$ts], JSON_UNESCAPED_SLASHES);
  $sig = hash_hmac('sha256', $payload, $secret);

  $ch = curl_init($ep);
  $post = [
    'session' => $session,
    'type'    => (substr($name,-5)=='.m3u8') ? 'playlist' : 'segment',
    'name'    => $name,
    'file'    => new CURLFile($localFile, (substr($name,-5)=='.m3u8'?'application/vnd.apple.mpegurl':'video/mp2t'), basename($name)),
  ];
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_HTTPHEADER => [
      'X-Perceptor-Date: '.$ts,
      'X-Perceptor-Signature: '.$sig,
      'X-Perceptor-Payload: '.$payload
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 20,
  ]);
  $resp = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);
  if ($code >= 200 && $code < 300) return true;
  fwrite(STDERR, "upload $name -> HTTP $code $err $resp\n");
  return false;
}

envload();
$EP = getenv('WP_ENDPOINT') ?: '';
$SEC= getenv('WP_SECRET')   ?: '';
if (!$EP || !$SEC) { fwrite(STDERR,"Missing WP_ENDPOINT/WP_SECRET in /etc/perceptor.env\n"); exit(3); }

$ip = get_camera_ip($camera);
if (!$ip) { fwrite(STDERR,"No IP for $camera in config/cameras.json\n"); exit(4); }

list($ok,$session,$playlistUrl) = wp_preview_url($EP, $camera);
if (!$ok) { fwrite(STDERR,"No active preview session for $camera (click Start Preview in WP)\n"); exit(5); }

$rtsp = get_rtsp($camera, $ip);

// local HLS workspace
$base = __DIR__ . '/../hls';
@mkdir($base,0775,true);
$work = "$base/$session";
@mkdir($work,0775,true);

// build FFmpeg command: 2s segments, short list, delete old; fast transcode
$dstPlaylist = "$work/index.m3u8";
$dstSegments = "$work/seg_%04d.ts";
$vf = "crop=trunc(iw/2)*2:trunc((ih-120)/2)*2:0:60"; // same 60px top/bottom crop
$cmd = sprintf(
  '/usr/bin/ffmpeg -hide_banner -y -loglevel warning -rtsp_transport tcp -i %s '.
  '-an -vf %s -c:v libx264 -preset veryfast -crf 24 '.
  '-f hls -hls_time 2 -hls_list_size 6 -hls_flags delete_segments+program_date_time '.
  '-hls_segment_filename %s %s 2>> %s',
  escapeshellarg($rtsp),
  escapeshellarg($vf),
  escapeshellarg($dstSegments),
  escapeshellarg($dstPlaylist),
  escapeshellarg("$work/ffmpeg_hls.log")
);

// start ffmpeg
$proc = proc_open($cmd, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
if (!is_resource($proc)) { fwrite(STDERR,"Failed to launch ffmpeg\n"); exit(6); }

// simple uploader loop: watch dir for new/changed files and push them
$seen = [];
$lastSessionCheck = 0;
$stop = false;

while (true) {
  // check session still active every ~3s
  if (time()-$lastSessionCheck >= 3) {
    $lastSessionCheck = time();
    list($ok2,$sess2,$pl2) = wp_preview_url($EP, $camera);
    if (!$ok2 || $sess2 !== $session) { $stop = true; }
  }

  // scan files
  foreach (glob("$work/*") as $file) {
    $name = basename($file);
    if (!preg_match('/\.m3u8$|\.ts$/', $name)) continue;
    $key = $name . ':' . filemtime($file) . ':' . filesize($file);
    if (isset($seen[$key])) continue;
    // upload
    if (upload_file($EP, $SEC, $session, $file, $name)) {
      // mark all with this name as seen by size/mtime
      $seen[$key] = true;
    }
  }

  if ($stop) break;
  usleep(300000); // 300ms
}

// cleanup
proc_terminate($proc);
proc_close($proc);
echo "stopped\n";
