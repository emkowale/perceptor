<?php
// dashboard.php (UI only; logic lives in dashboard-ajax.php)
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

function perceptor_get_opts(): array {
  $o = get_option('perceptor_options', []);
  $o = wp_parse_args($o, ['default_seconds'=>20,'camera_map'=>[],'ccpi_base_url'=>'']);
  if (!is_array($o['camera_map'])) $o['camera_map'] = [];
  return $o;
}

function perceptor_dashboard_page() {
  $o    = perceptor_get_opts();
  $secs = (int)$o['default_seconds'];
  $cams = $o['camera_map'];
  $ajax = admin_url('admin-ajax.php');
  $nonce = wp_create_nonce('perceptor');

  echo '<div class="wrap"><h1>Perceptor • Dashboard</h1>';
  echo '<h2>Status</h2><div id="perc-status">';
  echo '<span>ccpi: <b class="p-dot" data-kind="ccpi">•</b></span>';
  foreach ($cams as $id=>$name) {
    printf(' <span class="p-cam">%s: <b class="p-dot" data-cam="%s">•</b></span>', esc_html($name), esc_attr($id));
  }
  echo '</div>';

  printf('<h2>Capture</h2><label>Seconds <input id="perc-secs" type="number" min="1" max="120" value="%d" class="small-text"></label>', $secs);
  echo '<div id="perc-buttons" style="margin-top:10px">';
  foreach ($cams as $id=>$name) {
    printf(' <button class="button perc-cap" data-cam="%s">Capture %s</button>', esc_attr($id), esc_html($name));
  }
  echo '</div>';

  echo '<div id="perc-spin" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:9999">
          <div style="position:absolute;top:40%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:16px 24px;border-radius:10px">
            <span class="spinner is-active" style="float:none;margin:0"></span>
            <strong> Working… </strong>
          </div></div>';

  echo '<h2 style="margin-top:20px">Recent Captures</h2><div id="perc-caps">';
  $caps = get_option('perceptor_captures', []);
  if (is_array($caps) && $caps) {
    foreach (array_slice(array_reverse($caps), 0, 15) as $c) {
      $cid = sanitize_text_field($c['camera_id'] ?? '');
      $nm  = $cams[$cid] ?? $cid;
      $ts  = esc_html($c['started_at'] ?? '');
      $url = esc_url($c['url'] ?? '');
      echo '<div style="margin:8px 0">';
      printf('<strong>%s</strong> — %s<br>', esc_html($nm), $ts);
      if ($url) {
        printf('<video src="%s" controls playsinline style="max-width:480px;width:100%%;display:block"></video>', $url);
        printf('<a href="%s" download>Download</a>', $url);
      }
      echo '</div>';
    }
  } else echo '<em>No captures yet.</em>';
  echo '</div></div>';
  ?>
  <style>.p-dot{color:#c00;font-size:18px}.p-dot.ok{color:#2a8a2a}.p-cam{margin-left:12px}</style>
  <script>
  (function(){
    const ajax=<?php echo json_encode($ajax); ?>, nonce=<?php echo json_encode($nonce); ?>;
    const spin=document.getElementById('perc-spin');
    function markStatus(s){ if(s.ccpi) document.querySelector('[data-kind="ccpi"]').classList.add('ok');
      (s.cameras||[]).forEach(c=>{const el=document.querySelector('[data-cam="'+c.id+'"]'); if(el&&c.online) el.classList.add('ok');});
    }
    function fetchStatus(){ fetch(ajax+'?action=perceptor_status&_wpnonce='+nonce).then(r=>r.json()).then(markStatus).catch(()=>{}); }
    document.querySelectorAll('.perc-cap').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const cam=btn.dataset.cam, secs=document.getElementById('perc-secs').value||20;
        spin.style.display='block';
        const body='action=perceptor_capture&_wpnonce='+nonce+'&camera_id='+encodeURIComponent(cam)+'&seconds='+encodeURIComponent(secs);
        fetch(ajax,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
          .then(r=>r.json()).then(()=>{spin.style.display='none'; location.reload();}).catch(()=>{spin.style.display='none';});
      });
    });
    fetchStatus();
  })();
  </script>
  <?php
}
