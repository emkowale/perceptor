<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Perceptor Dashboard (compact)
 * Shows quick status and links. Safe to expand later.
 */
function perceptor_dashboard_page(){
  $endpoint = esc_html(get_option('perceptor_endpoint','(not set)'));
  $secret_set = get_option('perceptor_secret','') ? 'yes' : 'no';
  $cams = ['camera1','camera2','camera3','camera4','camera5','camera6'];
  ?>
  <div class="wrap">
    <h1>Perceptor Dashboard</h1>
    <p>Endpoint: <code><?php echo $endpoint; ?></code> &nbsp;|&nbsp; Secret set: <strong><?php echo $secret_set; ?></strong></p>
    <p>
      <a class="button" href="<?php echo admin_url('admin.php?page=perceptor-preview'); ?>">Live Preview</a>
      <a class="button" href="<?php echo admin_url('admin.php?page=perceptor-settings'); ?>">Settings</a>
    </p>
    <style>
      .pc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;max-width:1100px}
      .pc-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:10px}
      .pc-h{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
      .pc-img{width:100%;aspect-ratio:16/9;background:#111;border-radius:8px}
    </style>
    <div class="pc-grid">
      <?php foreach($cams as $c): 
        $snap = esc_url(get_option('perceptor_snapshot_'.$c,''));
        $src = $snap ? $snap.'?t='.time() : '';
      ?>
      <div class="pc-card">
        <div class="pc-h">
          <strong><?php echo esc_html($c); ?></strong>
          <a class="button button-small" href="<?php echo admin_url('admin.php?page=perceptor-preview'); ?>">Preview</a>
        </div>
        <?php if ($src): ?>
          <img class="pc-img" src="<?php echo $src; ?>" alt="">
        <?php else: ?>
          <div class="pc-img"></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php
}
