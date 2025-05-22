<?php
// ----------- Admin: Add Pitch Type (No Max Occupancy) -----------
function campsite_add_pitch_type_page() {
    global $wpdb;
    $pitch_types_table = $wpdb->prefix . 'campsite_pitch_types';
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campsite_add_pitch_type_submit'])) {
        $type_name      = sanitize_text_field($_POST['type_name']);
        $description    = sanitize_textarea_field($_POST['description']);
        $price_per_night = floatval($_POST['price_per_night']);
        if ($type_name && $price_per_night) {
            $result = $wpdb->insert(
                $pitch_types_table,
                [
                    'type_name'        => $type_name,
                    'description'      => $description,
                    'price_per_night'  => $price_per_night,
                ],
                ['%s', '%s', '%f']
            );
            $message = $result ? '<p style="color:green;">Pitch type added successfully!</p>' : '<p style="color:red;">Failed to add pitch type. Please try again.</p>';
        } else {
            $message = '<p style="color:red;">Please complete all required fields.</p>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Add Pitch Type</h1>
        <?php echo $message; ?>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="type_name">Type Name*</label></th>
                    <td><input type="text" name="type_name" id="type_name" required></td>
                </tr>
                <tr>
                    <th><label for="description">Description</label></th>
                    <td><textarea name="description" id="description" rows="3"></textarea></td>
                </tr>
                <tr>
                    <th><label for="price_per_night">Price Per Night (£)*</label></th>
                    <td><input type="number" name="price_per_night" id="price_per_night" min="0" step="0.01" required></td>
                </tr>
            </table>
            <p>
                <input type="submit" name="campsite_add_pitch_type_submit" class="button button-primary" value="Add Pitch Type">
            </p>
        </form>
    </div>
    <?php
}

// ----------- Admin: Manage (View/Edit/Delete) Pitch Types -----------
function campsite_manage_pitch_types_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'campsite_pitch_types';

    if (isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
        $id = intval($_GET['delete']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_pitch_type_'.$id)) {
            $wpdb->delete($table, ['id' => $id]);
            echo '<div class="updated"><p>Pitch type deleted.</p></div>';
        }
    }
    if (isset($_POST['edit_pitch_type_submit'])) {
        $id = intval($_POST['id']);
        $type_name = sanitize_text_field($_POST['type_name']);
        $description = sanitize_textarea_field($_POST['description']);
        $price_per_night = floatval($_POST['price_per_night']);
        $wpdb->update(
            $table,
            [
                'type_name' => $type_name,
                'description' => $description,
                'price_per_night' => $price_per_night
            ],
            ['id' => $id],
            ['%s', '%s', '%f'],
            ['%d']
        );
        echo '<div class="updated"><p>Pitch type updated.</p></div>';
    }
    if (isset($_GET['edit'])) {
        $id = intval($_GET['edit']);
        $pitch_type = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($pitch_type) {
            ?>
            <h2>Edit Pitch Type</h2>
            <form method="post">
                <input type="hidden" name="id" value="<?php echo esc_attr($pitch_type->id); ?>">
                <table class="form-table">
                    <tr>
                        <th><label for="type_name">Type Name*</label></th>
                        <td><input type="text" name="type_name" id="type_name" value="<?php echo esc_attr($pitch_type->type_name); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="description">Description</label></th>
                        <td><textarea name="description" id="description"><?php echo esc_textarea($pitch_type->description); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="price_per_night">Price Per Night (£)*</label></th>
                        <td><input type="number" name="price_per_night" id="price_per_night" value="<?php echo esc_attr($pitch_type->price_per_night); ?>" min="0" step="0.01" required></td>
                    </tr>
                </table>
                <p>
                    <input type="submit" name="edit_pitch_type_submit" class="button button-primary" value="Update Pitch Type">
                    <a href="<?php echo admin_url('admin.php?page=campsite-manage-pitch-types'); ?>" class="button">Cancel</a>
                </p>
            </form>
            <?php
        }
        return;
    }
    $pitch_types = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1>Pitch Types</h1>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type Name</th>
                    <th>Description</th>
                    <th>Price Per Night</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pitch_types as $pt): ?>
                <tr>
                    <td><?php echo esc_html($pt->id); ?></td>
                    <td><?php echo esc_html($pt->type_name); ?></td>
                    <td><?php echo esc_html($pt->description); ?></td>
                    <td>£<?php echo esc_html($pt->price_per_night); ?></td>
                    <td><?php echo esc_html($pt->created_at); ?></td>
                    <td>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=campsite-manage-pitch-types&edit='.$pt->id), 'edit_pitch_type_'.$pt->id); ?>">Edit</a> |
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=campsite-manage-pitch-types&delete='.$pt->id), 'delete_pitch_type_'.$pt->id); ?>" onclick="return confirm('Are you sure you want to delete this pitch type?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
