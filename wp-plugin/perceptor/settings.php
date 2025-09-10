<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Perceptor Settings page (endpoint + secret).
 * Saves to options: perceptor_endpoint, perceptor_secret.
 */
function perceptor_settings_page(){
  $notice = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can('manage_options')) {
    check_admin_referer('perceptor_settings');
    $ep = esc_url_raw($_POST['perceptor_endpoint'] ?? '');
    $sc = sanitize_text_field($_POST['perceptor_secret'] ?? '');
    update_option('perceptor_endpoint', $ep, false);
    update_option('perceptor_secret', $sc, false);
    $notice = '<div class="updated"><p>Settings saved.</p></div>';
  }
  $endpoint = esc_attr(get_option('perceptor_endpoint', ''));
  $secret   = esc_attr(get_option('perceptor_secret', ''));
  ?>
  <div class="wrap">
    <h1>Perceptor Settings</h1>
    <?php echo $notice; ?>
    <form method="post">
      <?php wp_nonce_field('perceptor_settings'); ?>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="perceptor_endpoint">Endpoint (REST base)</label></th>
          <td>
            <input name="perceptor_endpoint" id="perceptor_endpoint" type="url" class="regular-text" value="<?php echo $endpoint; ?>" placeholder="https://thebeartraxs.com/wp-json/perceptor/v1/upload">
            <p class="description">Used by the Pi to upload clips/snapshots and preview chunks.</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="perceptor_secret">Shared Secret</label></th>
          <td>
            <input name="perceptor_secret" id="perceptor_secret" type="text" class="regular-text" value="<?php echo $secret; ?>" placeholder="supersecretstring123">
            <p class="description">Must match the secret in <code>/etc/perceptor.env</code> on the Pi.</p>
          </td>
        </tr>
      </table>
      <p class="submit"><button class="button button-primary">Save Changes</button></p>
    </form>
  </div>
  <?php
}
