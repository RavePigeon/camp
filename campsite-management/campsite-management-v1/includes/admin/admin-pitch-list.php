<?php

// ========================== PITCH ZONES ADMIN PAGE ==========================
function campsite_pitch_zones_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'pitch_zone_table';

    // Handle Delete
    if (isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
        $id = intval($_GET['delete']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_pitch_zone_' . $id)) {
            $wpdb->delete($table, ['id' => $id]);
            echo '<div class="updated"><p>Pitch zone deleted.</p></div>';
        }
    }

    // Handle Add
    if (isset($_POST['add_pitch_zone'])) {
        $zone_code = sanitize_text_field($_POST['pitch_zone_code']);
        $zone_name = sanitize_text_field($_POST['pitch_zone_name']);
        $zone_description = sanitize_textarea_field($_POST['pitch_zone_description']);

        if ($zone_code) {
            $wpdb->insert($table, [
                'pitch_zone_code' => $zone_code,
                'pitch_zone_name' => $zone_name,
                'pitch_zone_description' => $zone_description
            ], ['%s', '%s', '%s']);
            echo '<div class="updated"><p>Pitch zone added.</p></div>';
        } else {
            echo '<div class="error"><p>Please enter a Zone Code.</p></div>';
        }
    }

    // Handle Edit
    if (isset($_POST['edit_pitch_zone'])) {
        $id = intval($_POST['id']);
        $zone_code = sanitize_text_field($_POST['pitch_zone_code']);
        $zone_name = sanitize_text_field($_POST['pitch_zone_name']);
        $zone_description = sanitize_textarea_field($_POST['pitch_zone_description']);

        if ($zone_code) {
            $wpdb->update($table, [
                'pitch_zone_code' => $zone_code,
                'pitch_zone_name' => $zone_name,
                'pitch_zone_description' => $zone_description
            ], ['id' => $id], ['%s','%s','%s'], ['%d']);
            echo '<div class="updated"><p>Pitch zone updated.</p></div>';
        } else {
            echo '<div class="error"><p>Please enter a Zone Code.</p></div>';
        }
    }

    // Edit form
    if (isset($_GET['edit'])) {
        $id = intval($_GET['edit']);
        $zone = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($zone) {
            ?>
            <div class="wrap">
            <h2>Edit Pitch Zone</h2>
            <form method="post">
                <input type="hidden" name="id" value="<?php echo esc_attr($zone->id); ?>">
                <table class="form-table">
                    <tr>
                        <th><label for="pitch_zone_code">Zone Code*</label></th>
                        <td><input type="text" name="pitch_zone_code" id="pitch_zone_code" value="<?php echo esc_attr($zone->pitch_zone_code); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="pitch_zone_name">Zone Name</label></th>
                        <td><input type="text" name="pitch_zone_name" id="pitch_zone_name" value="<?php echo esc_attr($zone->pitch_zone_name); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="pitch_zone_description">Description</label></th>
                        <td><textarea name="pitch_zone_description" id="pitch_zone_description"><?php echo esc_textarea($zone->pitch_zone_description); ?></textarea></td>
                    </tr>
                </table>
                <p>
                    <input type="submit" name="edit_pitch_zone" class="button button-primary" value="Update Pitch Zone">
                    <a href="<?php echo admin_url('admin.php?page=campsite-pitch-zones'); ?>" class="button">Cancel</a>
                </p>
            </form>
            </div>
            <?php
            return;
        }
    }

    // Add new form and list
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
            <p><input type="submit" name="add_pitch_zone" class="button button-primary" value="Add Pitch Zone"></p>
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

// ========================== PITCH LIST ADMIN PAGE ==========================
function campsite_pitch_list_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'pitch_list';
    $zone_table = $wpdb->prefix . 'pitch_zone_table';

    // Handle Delete
    if (isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
        $id = intval($_GET['delete']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_pitch_list_' . $id)) {
            $wpdb->delete($table, ['id' => $id]);
            echo '<div class="updated"><p>Pitch deleted.</p></div>';
        }
    }

    // Handle Add
    if (isset($_POST['add_pitch_list'])) {
        $zone_id = intval($_POST['zone_id']);
        $number = intval($_POST['number']);
        if ($zone_id && $number > 0 && $number < 999) {
            $wpdb->insert($table, [
                'zone_id' => $zone_id,
                'number' => $number
            ], ['%d', '%d']);
            echo '<div class="updated"><p>Pitch added.</p></div>';
        } else {
            echo '<div class="error"><p>Please select a zone and enter a valid number (&lt; 999).</p></div>';
        }
    }

    // Handle Edit
    if (isset($_POST['edit_pitch_list'])) {
        $id = intval($_POST['id']);
        $zone_id = intval($_POST['zone_id']);
        $number = intval($_POST['number']);
        if ($zone_id && $number > 0 && $number < 999) {
            $wpdb->update($table, [
                'zone_id' => $zone_id,
                'number' => $number
            ], ['id' => $id], ['%d', '%d'], ['%d']);
            echo '<div class="updated"><p>Pitch updated.</p></div>';
        } else {
            echo '<div class="error"><p>Please select a zone and enter a valid number (&lt; 999).</p></div>';
        }
    }

    // Fetch zones for dropdown
    $zones = $wpdb->get_results("SELECT id, pitch_zone_name FROM $zone_table ORDER BY pitch_zone_name ASC");

    // Edit form
    if (isset($_GET['edit'])) {
        $id = intval($_GET['edit']);
        $pitch = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($pitch) {
            ?>
            <div class="wrap">
            <h2>Edit Pitch</h2>
            <form method="post">
                <input type="hidden" name="id" value="<?php echo esc_attr($pitch->id); ?>">
                <table class="form-table">
                    <tr>
                        <th><label for="zone_id">Zone*</label></th>
                        <td>
                            <select name="zone_id" id="zone_id" required>
                                <option value="">Select zone</option>
                                <?php foreach ($zones as $zone): ?>
                                    <option value="<?php echo esc_attr($zone->id); ?>" <?php selected($pitch->zone_id, $zone->id); ?>>
                                        <?php echo esc_html($zone->pitch_zone_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="number">Pitch Number*</label></th>
                        <td><input type="number" name="number" id="number" value="<?php echo esc_attr($pitch->number); ?>" min="1" max="998" required></td>
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

    // Add form and list
    ?>
    <div class="wrap">
        <h1>Pitch List</h1>
        <h2>Add New Pitch</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="zone_id">Zone*</label></th>
                    <td>
                        <select name="zone_id" id="zone_id" required>
                            <option value="">Select zone</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?php echo esc_attr($zone->id); ?>">
                                    <?php echo esc_html($zone->pitch_zone_name); ?>
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
                    <th>Pitch Number</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pitches = $wpdb->get_results(
                    "SELECT pl.*, z.pitch_zone_name 
                    FROM $table pl 
                    LEFT JOIN $zone_table z ON pl.zone_id = z.id 
                    ORDER BY pl.created_at DESC"
                );
                if ($pitches) {
                    foreach ($pitches as $pitch): ?>
                        <tr>
                            <td><?php echo esc_html($pitch->id); ?></td>
                            <td><?php echo esc_html($pitch->pitch_zone_name); ?></td>
                            <td><?php echo esc_html($pitch->number); ?></td>
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