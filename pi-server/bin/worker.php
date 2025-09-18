#!/usr/bin/env php
<?php
declare(strict_types=1);

function poll_job(): ?array {
    $secret   = getenv('WP_SECRET');
    $endpoint = "https://thebeartraxs.com/wp-json/perceptor/v1/job_next";

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

function run_worker(): void {
    echo "[" . date('c') . "] Worker starting; polling...\n";

    while (true) {
        $resp = poll_job();
        if ($resp && isset($resp['job']) && $resp['job']) {
            $job = $resp['job'];
            echo "[" . date('c') . "] Got job {$job['id']} type={$job['type']} camera={$job['camera']}\n";

            // TODO: Handle preview/record jobs here
        } else {
            usleep(2_000_000); // sleep 2s
        }
    }
}

run_worker();
