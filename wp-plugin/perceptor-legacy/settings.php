<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** Settings page for Perceptor plugin */
add_action('admin_menu', function () {
    add_options_page(
        'Perceptor Settings',
        'Perceptor',
        'manage_options',
        'perceptor',
        'perceptor_settings_page'
    );
});

function perceptor_settings_page() {
    if (!current_user_can('manage_options')) return;

    // Handle form save
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('perceptor_settings')) {
        $names = [];
        for ($i=1; $i<=6; $i++) {
            $names[$i] = sanitize_text_field($_POST["camera_name_$i"] ?? '');
        }
        update_option('perceptor_camera_names', $names);

        if (!empty($_POST['clear_all_videos'])) {
            $deleted = perceptor_clear_all_videos();
            echo '<div class="updated"><p>Deleted '.$deleted.' videos.</p></div>';
        } else {
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
    }

    $names = get_option('perceptor_camera_names', []);

    ?>
    <div class="wrap">
        <h1>Perceptor Settings</h1>
        <form method="post">
            <?php wp_nonce_field('perceptor_settings'); ?>

            <h2>Camera Names</h2>
            <table class="form-table">
                <tbody>
                <?php for ($i=1; $i<=6; $i++): ?>
                    <tr>
                        <th scope="row">Camera <?php echo $i; ?> Name</th>
                        <td>
                            <input type="text" name="camera_name_<?php echo $i; ?>" value="<?php echo esc_attr($names[$i] ?? ''); ?>" class="regular-text" />
                        </td>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>

            <h2>Maintenance</h2>
            <p>
                <label>
                    <input type="checkbox" name="clear_all_videos" value="1">
                    Clear All Videos (delete all Perceptor media + recent list)
                </label>
            </p>

            <?php submit_button('Save Changes'); ?>
        </form>
    </div>
    <?php
}

/** Delete all Perceptor-uploaded videos */
function perceptor_clear_all_videos(): int {
    $attachments = get_posts([
        'post_type'   => 'attachment',
        'post_status' => 'inherit',
        'numberposts' => -1,
        'meta_key'    => 'perceptor_camera', // <-- FIXED
    ]);
    $deleted = 0;
    foreach ($attachments as $att) {
        if (wp_delete_attachment($att->ID, true)) $deleted++;
    }
    delete_option('perceptor_recent');
    return $deleted;
}
