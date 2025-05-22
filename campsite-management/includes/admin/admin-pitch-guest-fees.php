<?php



// ----------- Admin: Pitch Guest Fees Table CRUD -----------
function campsite_pitch_guest_fees_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'pitch_guest_fees';

    // Handle Delete
    if (isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
        $id = intval($_GET['delete']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_pitch_guest_fee_' . $id)) {
            $wpdb->delete($table, ['id' => $id]);
            echo '<div class="updated"><p>Record deleted.</p></div>';
        }
    }

    // Handle Add
    if (isset($_POST['add_pitch_guest_fee'])) {
        $guest_code = sanitize_text_field($_POST['guest_code']);
        $guest_description = sanitize_textarea_field($_POST['guest_description']);
        $guest_price = floatval($_POST['guest_price']);

        if ($guest_code && $guest_price !== '') {
            $wpdb->insert($table, [
                'guest_code' => $guest_code,
                'guest_description' => $guest_description,
                'guest_price' => $guest_price
            ], ['%s','%s','%f']);
            echo '<div class="updated"><p>Record added.</p></div>';
        } else {
            echo '<div class="error"><p>Please fill in all required fields.</p></div>';
        }
    }

    // Handle Edit
    if (isset($_POST['edit_pitch_guest_fee'])) {
        $id = intval($_POST['id']);
        $guest_code = sanitize_text_field($_POST['guest_code']);
        $guest_description = sanitize_textarea_field($_POST['guest_description']);
        $guest_price = floatval($_POST['guest_price']);

        if ($guest_code && $guest_price !== '') {
            $wpdb->update($table, [
                'guest_code' => $guest_code,
                'guest_description' => $guest_description,
                'guest_price' => $guest_price
            ], ['id' => $id], ['%s','%s','%f'], ['%d']);
            echo '<div class="updated"><p>Record updated.</p></div>';
        } else {
            echo '<div class="error"><p>Please fill in all required fields.</p></div>';
        }
    }

    // Edit form
    if (isset($_GET['edit'])) {
        $id = intval($_GET['edit']);
        $fee = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($fee) {
            ?>
            <h2>Edit Pitch Guest Fee</h2>
            <form method="post">
                <input type="hidden" name="id" value="<?php echo esc_attr($fee->id); ?>">
                <table class="form-table">
                    <tr>
                        <th><label for="guest_code">Guest Code*</label></th>
                        <td><input type="text" name="guest_code" id="guest_code" value="<?php echo esc_attr($fee->guest_code); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="guest_description">Description</label></th>
                        <td><textarea name="guest_description" id="guest_description"><?php echo esc_textarea($fee->guest_description); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="guest_price">Price*</label></th>
                        <td><input type="number" name="guest_price" id="guest_price" value="<?php echo esc_attr($fee->guest_price); ?>" step="0.01" required></td>
                    </tr>
                </table>
                <p>
                    <input type="submit" name="edit_pitch_guest_fee" class="button button-primary" value="Update Record">
                    <a href="<?php echo admin_url('admin.php?page=campsite-pitch-guest-fees'); ?>" class="button">Cancel</a>
                </p>
            </form>
            <?php
            return;
        }
    }

    // Add new form
    ?>
    <div class="wrap">
        <h1>Pitch Guest Fees</h1>
        <h2>Add New</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="guest_code">Guest Code*</label></th>
                    <td><input type="text" name="guest_code" id="guest_code" required></td>
                </tr>
                <tr>
                    <th><label for="guest_description">Description</label></th>
                    <td><textarea name="guest_description" id="guest_description"></textarea></td>
                </tr>
                <tr>
                    <th><label for="guest_price">Price*</label></th>
                    <td><input type="number" name="guest_price" id="guest_price" step="0.01" required></td>
                </tr>
            </table>
            <p><input type="submit" name="add_pitch_guest_fee" class="button button-primary" value="Add Record"></p>
        </form>
        <hr>
        <h2>All Records</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Guest Code</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $fees = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
            foreach ($fees as $fee): ?>
                <tr>
                    <td><?php echo esc_html($fee->id); ?></td>
                    <td><?php echo esc_html($fee->guest_code); ?></td>
                    <td><?php echo esc_html($fee->guest_description); ?></td>
                    <td>£<?php echo esc_html($fee->guest_price); ?></td>
                    <td><?php echo esc_html($fee->created_at); ?></td>
                    <td>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=campsite-pitch-guest-fees&edit='.$fee->id), 'edit_pitch_guest_fee_'.$fee->id); ?>">Edit</a> |
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=campsite-pitch-guest-fees&delete='.$fee->id), 'delete_pitch_guest_fee_'.$fee->id); ?>" onclick="return confirm('Delete this record?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
