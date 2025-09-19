<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
function perceptor_dashboard_page() {
    if (!current_user_can('edit_posts')) wp_die('Nope.');
    $names = get_option('perceptor_camera_names', []);
    $length = intval(get_option('perceptor_capture_length', 20));
    $nonce = wp_create_nonce('perceptor_ajax');
    ?>
    <div class="wrap">
      <h1>Perceptor Dashboard</h1>
      <p>Status: <span id="pc-status">Checking...</span></p>

      <h2>Capture Length</h2>
      <input type="number" id="pc-length" value="<?php echo esc_attr($length); ?>" min="1" max="300"> seconds

      <h2>Capture</h2>
      <div id="pc-buttons" style="display:flex;flex-wrap:wrap;gap:8px;">
        <?php for ($i=1;$i<=6;$i++):
            $label = $names[$i] ?: "Camera $i"; ?>
            <button class="button pc-capture" style="flex:1 1 120px" data-camera="<?php echo esc_attr($i); ?>">
              <?php echo esc_html($label); ?>
            </button>
        <?php endfor; ?>
      </div>

      <h2>Recent Captures</h2>
      <div id="pc-recent"><em>Loading...</em></div>
    </div>

    <!-- Modal overlay spinner -->
    <div id="pc-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;align-items:center;justify-content:center;">
      <div style="background:#fff;padding:20px;border-radius:6px;text-align:center;min-width:220px;">
        <div style="margin-bottom:12px;">
          <span style="display:inline-block;border:3px solid rgba(0,0,0,0.12);border-top:3px solid #0073aa;border-radius:50%;width:36px;height:36px;animation:pcspin 1s linear infinite;"></span>
        </div>
        <div id="pc-modal-text">Capturing and uploading — please wait.</div>
      </div>
    </div>

    <style>@keyframes pcspin{to{transform:rotate(360deg)}} #pc-recent ul{list-style:none;padding:0;margin:0} #pc-recent li{padding:8px 0;border-bottom:1px solid #eee}</style>

    <script>
    const pcNonce = "<?php echo esc_js($nonce); ?>";
    function showModal(txt){ if(txt) document.getElementById('pc-modal-text').textContent=txt; document.getElementById('pc-modal').style.display='flex'; document.querySelectorAll('.pc-capture').forEach(b=>b.disabled=true); document.getElementById('pc-length').disabled=true; }
    function hideModal(){ document.getElementById('pc-modal').style.display='none'; document.querySelectorAll('.pc-capture').forEach(b=>b.disabled=false); document.getElementById('pc-length').disabled=false; }

    function extractTopAnchor(html){
      const d=document.createElement('div'); d.innerHTML=html;
      const a=d.querySelector('a'); if(a) return a.href||a.textContent.trim();
      const li=d.querySelector('li'); return li?li.textContent.trim():'';
    }

    async function pcRecent(){
      try{
        const r = await fetch(ajaxurl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'perceptor_recent', nonce:pcNonce})});
        if(!r.ok) throw new Error('recent failed');
        const html = await r.text();
        document.getElementById('pc-recent').innerHTML = html || '<p>No captures yet.</p>';
        window.pc_latest = extractTopAnchor(html);
      }catch(e){
        document.getElementById('pc-recent').textContent = 'Error loading recent captures.';
        console.error(e);
      }
    }

    async function pcCapture(cam){
      const len = parseInt(document.getElementById('pc-length').value || 20,10);
      // baseline
      await pcRecent();
      const before = window.pc_latest || '';
      showModal('Requesting capture…');
      try{
        const r = await fetch(ajaxurl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'perceptor_capture', camera:cam, duration:len, nonce:pcNonce})});
        const j = await r.json();
        if(!j || !j.success) {
          hideModal();
          document.getElementById('pc-recent').textContent = 'Capture request failed.';
          return;
        }
      }catch(e){
        hideModal(); document.getElementById('pc-recent').textContent = 'Capture request error.'; return;
      }

      // poll recent until new top appears or timeout
      const start = Date.now();
      const timeout = (len * 1000) + 120000; // duration + 120s buffer
      showModal('Waiting for capture to finish and upload...');
      return new Promise((resolve)=>{
        const iv = setInterval(async ()=>{
          try{
            const r = await fetch(ajaxurl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'perceptor_recent', nonce:pcNonce})});
            if(r.ok){
              const html = await r.text();
              const top = extractTopAnchor(html);
              if(top && top !== before){
                document.getElementById('pc-recent').innerHTML = html;
                window.pc_latest = top;
                clearInterval(iv); hideModal(); resolve(true);
                return;
              }
            }
          }catch(e){ console.error('poll error', e); }
          if(Date.now() - start > timeout){
            clearInterval(iv);
            hideModal();
            document.getElementById('pc-recent').textContent = 'Timeout waiting for capture/upload; check worker logs.';
            resolve(false);
          }
        }, 3000);
      });
    }

    document.addEventListener('DOMContentLoaded', function(){
      pcRecent();
      document.querySelectorAll('.pc-capture').forEach(btn=> btn.addEventListener('click', ()=> pcCapture(btn.dataset.camera)));
    });
    </script>
    <?php
}
