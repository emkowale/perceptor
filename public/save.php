<?php
$cfgFile = __DIR__ . '/../config/cameras.json';
$state = json_decode(file_get_contents($cfgFile), true);
foreach ($_POST['ip'] ?? [] as $cam=>$ip) {
  if (isset($state[$cam])) $state[$cam]['ip'] = trim($ip ?? '');
}
file_put_contents($cfgFile, json_encode($state, JSON_PRETTY_PRINT));
header('Location: /?saved=1');
