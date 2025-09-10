<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** Live Preview page with HLS.js (<=100 lines) */
function perceptor_preview_page(){
  $cams = ['camera1','camera2','camera3','camera4','camera5','camera6']; ?>
  <div class="wrap">
    <h1>Perceptor — Live Preview</h1>
    <div style="display:flex;gap:12px;align-items:center;margin:12px 0">
      <label for="cam">Camera:</label>
      <select id="cam"><?php foreach($cams as $c){ echo '<option value="'.esc_attr($c).'">'.esc_html($c).'</option>'; } ?></select>
      <button class="button button-primary" id="btnStart">Start Preview</button>
      <button class="button" id="btnStop">Stop</button>
      <span id="status" style="margin-left:10px;color:#555"></span>
    </div>
    <video id="player" controls autoplay muted playsinline style="width:100%;max-width:960px;background:#000;border-radius:12px;min-height:320px"></video>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
      const rest=(p,o={})=>fetch((ajaxurl||'').replace('admin-ajax.php','rest/')+p,Object.assign({credentials:'same-origin'},o));
      let hls=null;
      async function start(){
        const camera=document.getElementById('cam').value, s=document.getElementById('status'), v=document.getElementById('player');
        s.textContent='Starting session...';
        const r=await rest('perceptor/v1/preview_start',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({camera})});
        const j=await r.json().catch(()=>null); if(!j||!j.ok){ s.textContent='Error starting'; return; }
        s.textContent='Waiting for stream...';
        const u=await (await rest('perceptor/v1/preview_url?camera='+encodeURIComponent(camera))).json().catch(()=>null);
        if(!u||!u.ok||!u.playlist_url){ s.textContent='No playlist yet'; return; }
        const url=u.playlist_url+'?t='+Date.now();
        if(window.Hls&&Hls.isSupported()){ hls=new Hls({lowLatencyMode:true,backBufferLength:10}); hls.loadSource(url); hls.attachMedia(v); hls.on(Hls.Events.MANIFEST_PARSED,()=>v.play()); }
        else if(v.canPlayType('application/vnd.apple.mpegurl')){ v.src=url; v.play(); }
      }
      async function stop(){
        const camera=document.getElementById('cam').value, v=document.getElementById('player'); 
        await rest('perceptor/v1/preview_stop',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({camera})});
        if(hls){ hls.destroy(); hls=null; } v.pause(); v.removeAttribute('src'); v.load();
        document.getElementById('status').textContent='Stopped.';
      }
      document.getElementById('btnStart').addEventListener('click',start);
      document.getElementById('btnStop').addEventListener('click',stop);
    </script>
  </div><?php
}
