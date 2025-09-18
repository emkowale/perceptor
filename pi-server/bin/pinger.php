<?php
declare(strict_types=1);

$secret = "L0vebug1999!"; // replace with your actual secret
$url    = "https://thebeartraxs.com/wp-json/perceptor/v1/ping";

while (true) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['secret' => $secret]),
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log("Ping failed: $err");
    } else {
        error_log("Ping sent: $response");
    }

    sleep(60); // wait 60s before next ping
}
