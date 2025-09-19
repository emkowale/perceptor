<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** Live Preview page (snapshot-based, <=100 lines) */
function perceptor_preview_page(){
  $names = get_option('perceptor_camera_names', []);
  $cams = [];
  for ($i=1;$i<=6;$i++){
    $label = trim($names[$i] ?? '');
    $cams[$i] = $label !== '' ? $label : "Camera $i";
  }
  ?>
  <div class="wrap">
    <h1>Perceptor — Live Preview</h1>
    <div style="display:flex;gap:12px;align-items:center;margin:12px 0">
      <label for="cam">Camera:</label>
      <select id="cam">
        <?php foreach($cams as $key=>$label): ?>
          <option value="<?php echo esc_attr("camera{$key}"); ?>"><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
      </select>
      <button class="button button-primary" id="btnStart">Start Preview</button>
      <button class="button" id="btnStop">Stop</button>
      <span id="status" style="margin-left:10px;color:#555"></span>
    </div>

    <div style="max-width:960px">
      <img id="player" src="" alt="Live preview" style="width:100%;background:#000;border-radius:12px;min-height:320px;object-fit:cover" />
    </div>
  </div>

  <script>
  const rest=(p,o={})=>fetch((ajaxurl||'').replace('admin-ajax.php','rest/')+p,Object.assign({credentials:'same-origin'},o));
  let pollIv=null;
  async function start(){
    const camera=document.getElementById('cam').value, s=document.getElementById('status');
    s.textContent='Requesting snapshot stream...';
    const r = await rest('perceptor/v1/preview_start',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({camera})});
    const j = await r.json().catch(()=>null);
    if(!j||!j.ok){ s.textContent='Error starting preview'; return; }
    s.textContent='Waiting for snapshot...';
    // start polling for snapshot_url
    pollIv = setInterval(async ()=>{
      try{
        const u = await (await rest('perceptor/v1/preview_url?camera='+encodeURIComponent(camera))).json();
        if(u && u.ok && u.snapshot_url){
          const img = document.getElementById('player');
          img.src = u.snapshot_url + '?t=' + Date.now();
          s.textContent = 'Live preview';
          // keep updating image every 2s
        }
      }catch(e){ console.error(e); }
    }, 2000);
  }
  function stop(){
    if(pollIv) { clearInterval(pollIv); pollIv=null; }
    document.getElementById('status').textContent='Stopped';
    document.getElementById('player').src='';
  }
  document.getElementById('btnStart').addEventListener('click',start);
  document.getElementById('btnStop').addEventListener('click',stop);
  </script>
  <?php
}
