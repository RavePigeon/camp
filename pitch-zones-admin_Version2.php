<?php
/**
 * Admin area for Pitch Zones in the Campsite Management plugin.
 * Allows Add, Edit, and Delete of Pitch Zone records.
 */

if (!defined('ABSPATH')) exit;

// ------------------ Add Pitch Zones submenu -------------------
add_action('admin_menu', function() {
    add_submenu_page(
        'campsite-management',
        'Pitch Zones',
        'Pitch Zones',
        'manage_options',
        'campsite-pitch-zones',
        'campsite_pitch_zones_page'
    );
});

// ------------------ Pitch Zones Admin Page --------------------
function campsite_pitch_zones_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'pitch_zone_table';

    // ----- Handle Delete -----
    if (isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
        $id = intval($_GET['delete']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_pitch_zone_'.$id)) {
            $wpdb->delete($table, ['id' => $id]);
            echo '<div class="updated"><p>Pitch zone deleted.</p></div>';
        }
    }

    // ----- Handle Edit -----
    $editing = false;
    $edit_data = null;
    if (isset($_GET['edit'])) {
        $editing = true;
        $id = intval($_GET['edit']);
        $edit_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    // ----- Handle Add/Edit POST -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_pitch_zone'])) {
        $zone_code = sanitize_text_field($_POST['pitch_zone_code']);
        $zone_name = sanitize_text_field($_POST['pitch_zone_name']);
        $zone_description = sanitize_textarea_field($_POST['pitch_zone_description']);

        if ($editing && $edit_data) {
            $wpdb->update($table, [
                'pitch_zone_code' => $zone_code,
                'pitch_zone_name' => $zone_name,
                'pitch_zone_description' => $zone_description
            ], ['id' => $id], ['%s', '%s', '%s'], ['%d']);
            echo '<div class="updated"><p>Pitch zone updated.</p></div>';
            $editing = false;
            $edit_data = null;
        } else {
            $wpdb->insert($table, [
                'pitch_zone_code' => $zone_code,
                'pitch_zone_name' => $zone_name,
                'pitch_zone_description' => $zone_description
            ], ['%s', '%s', '%s']);
            echo '<div class="updated"><p>Pitch zone added.</p></div>';
        }
    }

    // ----- Edit Form -----
    if ($editing && $edit_data) {
        ?>
        <div class="wrap">
            <h1>Edit Pitch Zone</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="pitch_zone_code">Zone Code*</label></th>
                        <td><input type="text" name="pitch_zone_code" id="pitch_zone_code" value="<?php echo esc_attr($edit_data->pitch_zone_code); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="pitch_zone_name">Zone Name</label></th>
                        <td><input type="text" name="pitch_zone_name" id="pitch_zone_name" value="<?php echo esc_attr($edit_data->pitch_zone_name); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="pitch_zone_description">Description</label></th>
                        <td><textarea name="pitch_zone_description" id="pitch_zone_description"><?php echo esc_textarea($edit_data->pitch_zone_description); ?></textarea></td>
                    </tr>
                </table>
                <p>
                    <input type="submit" name="submit_pitch_zone" class="button button-primary" value="Update Pitch Zone">
                    <a href="<?php echo admin_url('admin.php?page=campsite-pitch-zones'); ?>" class="button">Cancel</a>
                </p>
            </form>
        </div>
        <?php
        return;
    }
    ?>

    <div class="wrap">
        <h1>Pitch Zones</h1>
        <h2>Add New Pitch Zone</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="pitch_zone_code">Zone Code*</label></th>
                    <td><input type="text" name="pitch_zone_code" id="pitch_zone_code" required></td>
                </tr>
                <tr>
                    <th><label for="pitch_zone_name">Zone Name</label></th>
                    <td><input type="text" name="pitch_zone_name" id="pitch_zone_name"></td>
                </tr>
                <tr>
                    <th><label for="pitch_zone_description">Description</label></th>
                    <td><textarea name="pitch_zone_description" id="pitch_zone_description"></textarea></td>
                </tr>
            </table>
            <p><input type="submit" name="submit_pitch_zone" class="button button-primary" value="Add Pitch Zone"></p>
        </form>

        <hr>

        <h2>All Pitch Zones</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Zone Code</th>
                    <th>Zone Name</th>
                    <th>Description</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $zones = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
                if ($zones) {
                    foreach ($zones as $zone): ?>
                        <tr>
                            <td><?php echo esc_html($zone->id); ?></td>
                            <td><?php echo esc_html($zone->pitch_zone_code); ?></td>
                            <td><?php echo esc_html($zone->pitch_zone_name); ?></td>
                            <td><?php echo esc_html($zone->pitch_zone_description); ?></td>
                            <td><?php echo esc_html($zone->created_at); ?></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=campsite-pitch-zones&edit='.$zone->id), 'edit_pitch_zone_'.$zone->id); ?>">Edit</a> |
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=campsite-pitch-zones&delete='.$zone->id), 'delete_pitch_zone_'.$zone->id); ?>" onclick="return confirm('Are you sure you want to delete this zone?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach;
                } else {
                    echo '<tr><td colspan="6">No pitch zones found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}