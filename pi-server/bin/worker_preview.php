#!/usr/bin/env php
<?php
declare(strict_types=1);

function poll_job(): ?array {
    $secret   = getenv('WP_SECRET');
    $endpoint = "https://thebeartraxs.com/wp-json/perceptor/v1/job_next";

    if (!$secret) {
        fwrite(STDERR, "[poll_job] No WP_SECRET set\n");
        return null;
    }

    $ts      = time();
    $payload = ['ts' => $ts];
    $sig     = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "x-perceptor-date: $ts",
            "x-perceptor-signature: $sig"
        ],
        CURLOPT_POSTFIELDS => json_encode(new stdClass()), // send {}
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        fwrite(STDERR, "[poll_job] CURL error: " . curl_error($ch) . PHP_EOL);
        return null;
    }
    curl_close($ch);

    return json_decode($resp, true);
}

function run_preview(string $camera): void {
    $outputDir = "/var/www/perceptor/hls/$camera";
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    $cmd = sprintf(
        "ffmpeg -hide_banner -loglevel error -y -f v4l2 -i /dev/video0 ".
        "-c:v libx264 -preset ultrafast -tune zerolatency -f hls ".
        "-hls_time 2 -hls_list_size 3 -hls_flags delete_segments ".
        "%s/index.m3u8",
        escapeshellarg($outputDir)
    );

    echo "[" . date('c') . "] Starting preview for $camera\n";
    passthru($cmd);
    echo "[" . date('c') . "] Preview stopped for $camera\n";
}

function run_worker(): void {
    echo "[" . date('c') . "] Preview worker starting; polling...\n";

    while (true) {
        $resp = poll_job();
        if ($resp && isset($resp['job']) && $resp['job']) {
            $job = $resp['job'];
            if ($job['type'] === 'preview') {
                run_preview($job['camera']);
            } else {
                echo "[" . date('c') . "] Ignoring non-preview job {$job['id']}\n";
            }
        }
        usleep(2_000_000); // sleep 2s
    }
}

run_worker();
