<?php
// ========================== PITCH LIST ADMIN PAGE WITH BULK CREATOR ==========================
function campsite_pitch_list_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'pitch_list';
    $zone_table = $wpdb->prefix . 'pitch_zone_table';

    // =================== Bulk Pitch Creator ===================
    $bulk_message = '';
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['bulk_pitch_creator_nonce']) &&
        wp_verify_nonce($_POST['bulk_pitch_creator_nonce'], 'bulk_pitch_creator_action')
    ) {
        $zone_id = isset($_POST['zone_id']) ? intval($_POST['zone_id']) : 0;
        $num_pitches = isset($_POST['num_pitches']) ? intval($_POST['num_pitches']) : 0;

        $zone = $wpdb->get_row($wpdb->prepare("SELECT pitch_zone_code, pitch_zone_name FROM $zone_table WHERE id = %d", $zone_id));
        if ($zone && $num_pitches > 0) {
            // Find the highest pitch number for this zone and prefix
            $max_existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT MAX(CAST(SUBSTRING(pitch_name, LENGTH(%s)+1) AS UNSIGNED)) FROM $table WHERE zone_id = %d AND pitch_name LIKE %s",
                    $zone->pitch_zone_code,
                    $zone_id,
                    $zone->pitch_zone_code . '%'
                )
            );
            $start = $max_existing ? intval($max_existing) + 1 : 1;

            $created = 0;
            for ($i = $start; $i < $start + $num_pitches; $i++) {
                $pitch_name = $zone->pitch_zone_code . $i;
                $inserted = $wpdb->insert(
                    $table,
                    [
                        'zone_id' => $zone_id,
                        'pitch_name' => $pitch_name,
                        'created_at' => current_time('mysql')
                    ]
                );
                if ($inserted !== false) {
                    $created++;
                }
            }
            $bulk_message = '<div class="updated notice is-dismissible"><p>Successfully created ' . $created . ' pitches for zone <strong>' . esc_html($zone->pitch_zone_name) . '</strong> (' . esc_html($zone->pitch_zone_code) . '), starting from ' . esc_html($zone->pitch_zone_code . $start) . '.</p></div>';
        } else {
            $bulk_message = '<div class="error notice is-dismissible"><p>Error: Invalid zone or number of pitches.</p></div>';
        }
    }

    // =================== Handle Delete ===================
    if (isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
        $id = intval($_GET['delete']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_pitch_list_' . $id)) {
            $wpdb->delete($table, ['id' => $id]);
            echo '<div class="updated"><p>Pitch deleted.</p></div>';
        }
    }

    // =================== Handle Add ===================
    if (isset($_POST['add_pitch_list'])) {
        check_admin_referer('add_pitch_list_action', 'add_pitch_list_nonce');
        $zone_id = intval($_POST['zone_id']);
        $number = intval($_POST['number']);
        // Get zone code for prefix
        $zone = $wpdb->get_row($wpdb->prepare("SELECT pitch_zone_code FROM $zone_table WHERE id = %d", $zone_id));
        if ($zone_id && $number > 0 && $number < 999 && $zone) {
            $pitch_name = $zone->pitch_zone_code . $number;
            $wpdb->insert($table, [
                'zone_id' => $zone_id,
                'pitch_name' => $pitch_name,
                'created_at' => current_time('mysql')
            ]);
            echo '<div class="updated"><p>Pitch added.</p></div>';
        } else {
            echo '<div class="error"><p>Please select a zone and enter a valid number (&lt; 999).</p></div>';
        }
    }

    // =================== Handle Edit ===================
    if (isset($_POST['edit_pitch_list'])) {
        check_admin_referer('edit_pitch_list_action_' . intval($_POST['id']), 'edit_pitch_list_nonce');
        $id = intval($_POST['id']);
        $zone_id = intval($_POST['zone_id']);
        $number = intval($_POST['number']);
        $zone = $wpdb->get_row($wpdb->prepare("SELECT pitch_zone_code FROM $zone_table WHERE id = %d", $zone_id));
        if ($zone_id && $number > 0 && $number < 999 && $zone) {
            $pitch_name = $zone->pitch_zone_code . $number;
            $wpdb->update($table, [
                'zone_id' => $zone_id,
                'pitch_name' => $pitch_name
            ], ['id' => $id], ['%d', '%s'], ['%d']);
            echo '<div class="updated"><p>Pitch updated.</p></div>';
        } else {
            echo '<div class="error"><p>Please select a zone and enter a valid number (&lt; 999).</p></div>';
        }
    }

    // =================== Fetch zones for dropdown ===================
    $zones = $wpdb->get_results("SELECT id, pitch_zone_name, pitch_zone_code FROM $zone_table ORDER BY pitch_zone_name ASC");

    // =================== Edit form ===================
    if (isset($_GET['edit'])) {
        $id = intval($_GET['edit']);
        $pitch = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($pitch) {
            // Split pitch_name into prefix and number (for edit)
            preg_match('/^([A-Za-z]+)([0-9]+)$/', $pitch->pitch_name, $matches);
            $prefix = $matches[1] ?? '';
            $number = $matches[2] ?? '';
            ?>
            <div class="wrap">
            <h2>Edit Pitch</h2>
            <form method="post">
                <?php wp_nonce_field('edit_pitch_list_action_' . esc_attr($pitch->id), 'edit_pitch_list_nonce'); ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($pitch->id); ?>">
                <table class="form-table">
                    <tr>
                        <th><label for="zone_id">Zone*</label></th>
                        <td>
                            <select name="zone_id" id="zone_id" required>
                                <option value="">Select zone</option>
                                <?php foreach ($zones as $zone): ?>
                                    <option value="<?php echo esc_attr($zone->id); ?>" <?php selected($pitch->zone_id, $zone->id); ?>>
                                        <?php echo esc_html($zone->pitch_zone_name . " (" . $zone->pitch_zone_code . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="number">Pitch Number*</label></th>
                        <td>
                            <input type="number" name="number" id="number"
                                value="<?php echo esc_attr($number); ?>"
                                min="1" max="998" required>
                        </td>
                    </tr>
                </table>
                <p>
                    <input type="submit" name="edit_pitch_list" class="button button-primary" value="Update Pitch">
                    <a href="<?php echo admin_url('admin.php?page=campsite-pitch-list'); ?>" class="button">Cancel</a>
                </p>
            </form>
            </div>
            <?php
            return;
        }
    }

    // =================== Bulk Pitch Creator form (top) ===================
    ?>
    <div class="wrap">
        <h1>Pitch List</h1>

        <h2>Bulk Pitch Creator</h2>
        <?php if ($bulk_message) echo $bulk_message; ?>
        <form method="post">
            <?php wp_nonce_field('bulk_pitch_creator_action', 'bulk_pitch_creator_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="zone_id_bulk">Select Zone</label></th>
                    <td>
                        <select name="zone_id" id="zone_id_bulk" required>
                            <option value="">-- Select Zone --</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?php echo esc_attr($zone->id); ?>">
                                    <?php echo esc_html($zone->pitch_zone_name . " (" . $zone->pitch_zone_code . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="num_pitches">Number of Pitches</label></th>
                    <td>
                        <input type="number" min="1" max="1000" name="num_pitches" id="num_pitches" required>
                        <p class="description">Enter how many pitches to create (e.g., 100 will create P1, P2, ... P100). Will start at next available number for the selected zone.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Create Pitches'); ?>
        </form>
        <hr>
        <h2>Add New Pitch</h2>
        <form method="post">
            <?php wp_nonce_field('add_pitch_list_action', 'add_pitch_list_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="zone_id_single">Zone*</label></th>
                    <td>
                        <select name="zone_id" id="zone_id_single" required>
                            <option value="">Select zone</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?php echo esc_attr($zone->id); ?>">
                                    <?php echo esc_html($zone->pitch_zone_name . " (" . $zone->pitch_zone_code . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="number">Pitch Number*</label></th>
                    <td><input type="number" name="number" id="number" min="1" max="998" required></td>
                </tr>
            </table>
            <p><input type="submit" name="add_pitch_list" class="button button-primary" value="Add Pitch"></p>
        </form>
        <hr>
        <h2>All Pitches</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Zone</th>
                    <th>Pitch Name</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pitches = $wpdb->get_results(
                    "SELECT pl.*, z.pitch_zone_name, z.pitch_zone_code
                     FROM $table pl
                     LEFT JOIN $zone_table z ON pl.zone_id = z.id
                     ORDER BY pl.created_at DESC"
                );
                if ($pitches) {
                    foreach ($pitches as $pitch): ?>
                        <tr>
                            <td><?php echo esc_html($pitch->id); ?></td>
                            <td><?php echo esc_html($pitch->pitch_zone_name . " (" . $pitch->pitch_zone_code . ")"); ?></td>
                            <td><?php echo esc_html($pitch->pitch_name); ?></td>
                            <td><?php echo esc_html($pitch->created_at); ?></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=campsite-pitch-list&edit='.$pitch->id), 'edit_pitch_list_'.$pitch->id); ?>">Edit</a> |
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=campsite-pitch-list&delete='.$pitch->id), 'delete_pitch_list_'.$pitch->id); ?>" onclick="return confirm('Are you sure you want to delete this pitch?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach;
                } else {
                    echo '<tr><td colspan="5">No pitches found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}