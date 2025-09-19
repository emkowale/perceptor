<?php
$cfgFile = __DIR__ . '/../config/cameras.json';
$state = json_decode(file_get_contents($cfgFile), true);
$durations = [5,10,15,20,25,30];
$defaultUserPass = 'password';
$last = $_GET['ok'] ?? null;
?><!doctype html><meta charset="utf-8">
<link rel="stylesheet" href="/style.css">
<div class="header">
  <img src="/../assets/logo.png" alt="Bear Traxs">
  <h1>Perceptor — Capture Clips</h1>
</div>
<div class="container">
  <?php if ($last): ?>
  <div class="card success"><b>Done.</b> Your clip: <a href="/<?= htmlspecialchars($last) ?>" download><?= htmlspecialchars($last) ?></a></div>
  <?php endif; ?>

  <div class="card">
    <h3>Camera IPs <span class="badge">camera1…camera6</span></h3>
    <form method="post" action="/save.php" class="grid">
      <?php foreach ($state as $cam=>$info): ?>
        <div>
          <label><?= $cam ?> IP</label>
          <input name="ip[<?= $cam ?>]" value="<?= htmlspecialchars($info['ip']) ?>" placeholder="10.255.252.xxx">
        </div>
      <?php endforeach; ?>
      <div style="grid-column:1 / -1"><button class="primary">Save IPs</button></div>
    </form>
    <p class="small">Usernames are <code>camera1..camera6</code> with password <code><?= $defaultUserPass ?></code>.</p>
  </div>

  <div class="card">
    <h3>Capture</h3>
    <form method="post" action="/capture.php" class="grid">
      <div>
        <label>Camera</label>
        <select name="camera" required>
          <?php foreach (array_keys($state) as $cam): ?>
            <option value="<?= $cam ?>"><?= $cam ?> (<?= htmlspecialchars($state[$cam]['ip']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Duration (seconds)</label>
        <select name="duration" required>
          <?php foreach ($durations as $d): ?><option value="<?= $d ?>"><?= $d ?></option><?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Stream quality</label>
        <select name="stream"><option value="1">stream1 (high)</option><option value="2">stream2 (low)</option></select>
      </div>
      <div>
        <label>Crop preset</label>
        <select name="crop">
          <option value="soft" selected>Soft (trim ~40px top/bottom)</option>
          <option value="none">None</option>
          <option value="delogo">Delogo (no crop)</option>
        </select>
      </div>
      <div style="grid-column:1 / -1"><button class="primary">Capture Clip</button></div>
    </form>
    <p class="small">Audio is disabled. Files appear in <code>/captures/</code> and are offered to download.</p>
  </div>

  <div class="card">
    <h3>Recent Clips</h3>
    <table class="table"><tr><th>File</th><th>Size</th><th>When</th></tr>
    <?php
      $dir = realpath(__DIR__.'/../captures');
      if ($dir) {
        $files = array_values(array_filter(scandir($dir), fn($f)=>preg_match('/\.mp4$/',$f)));
        rsort($files);
        foreach (array_slice($files,0,10) as $f) {
          $p = "$dir/$f";
          echo "<tr><td><a href=\"/../captures/$f\" download>$f</a></td><td>".round(filesize($p)/1048576,2)." MB</td><td>".date('Y-m-d H:i:s', filemtime($p))."</td></tr>";
        }
      }
    ?>
    </table>
  </div>
</div>
