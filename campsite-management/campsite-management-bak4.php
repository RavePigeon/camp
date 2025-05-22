
<?php
/**
 * Plugin Name: Campsite Management Plugin
 * Description: A plugin to manage campsite pitches, guests, pitch fees, and bookings with Pitchup.com and iCal integration.
 * Version: 1.5
 * Author: RavePigeon
 */

if (!defined('ABSPATH')) exit;

// Activation Hook: Create or Update Database Tables
register_activation_hook(__FILE__, 'campsite_create_or_update_tables');

function campsite_create_or_update_tables() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $guests_table = $wpdb->prefix . 'campsite_guests';
    $pitch_types_table = $wpdb->prefix . 'campsite_pitch_types';
    $pitches_table = $wpdb->prefix . 'campsite_pitches';
    $bookings_table = $wpdb->prefix . 'campsite_bookings';
    $pitch_guest_fees_table = $wpdb->prefix . 'pitch_guest_fees';
    $pitch_zone_table = $wpdb->prefix . 'pitch_zone_table';

    dbDelta("CREATE TABLE $guests_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    dbDelta("CREATE TABLE $pitch_types_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_name VARCHAR(100) NOT NULL,
        description TEXT,
        price_per_night DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    dbDelta("CREATE TABLE $pitches_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pitch_number VARCHAR(50) NOT NULL,
        pitch_type_id INT NOT NULL,
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pitch_type_id) REFERENCES $pitch_types_table(id)
    );");

    dbDelta("CREATE TABLE $bookings_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        guest_id INT NOT NULL,
        pitch_id INT NOT NULL,
        booking_reference VARCHAR(100) UNIQUE NOT NULL,
        check_in_date DATE NOT NULL,
        check_out_date DATE NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        uid VARCHAR(255) UNIQUE NOT NULL,
        dtstamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (guest_id) REFERENCES $guests_table(id),
        FOREIGN KEY (pitch_id) REFERENCES $pitches_table(id)
    );");

    dbDelta("CREATE TABLE $pitch_guest_fees_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        guest_code VARCHAR(50) NOT NULL,
        guest_description TEXT,
        guest_price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    dbDelta("CREATE TABLE $pitch_zone_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pitch_zone_code VARCHAR(50) NOT NULL,
        pitch_zone_name TEXT,
        pitch_zone_description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");
}

// Admin Menu
add_action('admin_menu', function() {
    // Main menu
    add_menu_page('Campsite Management', 'Campsite Management', 'manage_options', 'campsite-management', 'campsite_admin_dashboard', 'dashicons-admin-site-alt3', 3);
    // Submenus
    add_submenu_page('campsite-management', 'View Guests', 'View Guests', 'manage_options', 'campsite-view-guests', 'campsite_view_guests_callback');
    add_submenu_page('campsite-management', 'Add Pitch Type', 'Add Pitch Type', 'manage_options', 'campsite-add-pitch-type', 'campsite_add_pitch_type_page');
    add_submenu_page('campsite-management', 'Manage Pitch Types', 'Manage Pitch Types', 'manage_options', 'campsite-manage-pitch-types', 'campsite_manage_pitch_types_page');
    add_submenu_page('campsite-management', 'Pitch Guest Fees', 'Pitch Guest Fees', 'manage_options', 'campsite-pitch-guest-fees', 'campsite_pitch_guest_fees_page');
    add_submenu_page('campsite-management', 'Pitch Zones', 'Pitch Zones', 'manage_options', 'campsite-pitch-zones', 'campsite_pitch_zones_page');
});

// Admin Dashboard (default page)
function campsite_admin_dashboard() {
    ?>
    <div class="wrap">
        <h1>Campsite Management Dashboard</h1>
        <ul>
            <li><a href="<?php echo admin_url('admin.php?page=campsite-view-guests'); ?>">View Guests</a></li>
            <li><a href="<?php echo admin_url('admin.php?page=campsite-add-pitch-type'); ?>">Add Pitch Type</a></li>
            <li><a href="<?php echo admin_url('admin.php?page=campsite-manage-pitch-types'); ?>">Manage Pitch Types</a></li>
            <li><a href="<?php echo admin_url('admin.php?page=campsite-pitch-guest-fees'); ?>">Pitch Guest Fees</a></li>
            <li><a href="<?php echo admin_url('admin.php?page=campsite-pitch-zones'); ?>">Pitch Zones</a></li>
        </ul>
    </div>
    <?php
}

// ----------- Pitch Zones Admin View -----------
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

// ----------- Rest of your plugin code unchanged (Guest Form, Pitch Types, Fees, iCal, etc) -----------

// ----------- Front-End Guest Form (Shortcode) -----------
function campsite_guest_form_shortcode() {
    ob_start();
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['campsite_guest_form_submitted'])) {
        global $wpdb;
        $guests_table = $wpdb->prefix . 'campsite_guests';

        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name  = sanitize_text_field($_POST['last_name']);
        $email      = sanitize_email($_POST['email']);
        $phone      = sanitize_text_field($_POST['phone']);

        if (empty($first_name) || empty($last_name) || empty($email)) {
            echo '<p style="color:red;">Please fill in all required fields.</p>';
        } else {
            $result = $wpdb->insert(
                $guests_table,
                ['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'phone' => $phone],
                ['%s', '%s', '%s', '%s']
            );
            if ($result) {
                echo '<p style="color:green;">Thank you for your enquiry! We will contact you soon.</p>';
            } else {
                echo '<p style="color:red;">There was an error saving your enquiry. Please try again.</p>';
            }
        }
    }
    ?>
    <form method="post">
        <label>First Name*: <input type="text" name="first_name" required></label><br>
        <label>Last Name*: <input type="text" name="last_name" required></label><br>
        <label>Email*: <input type="email" name="email" required></label><br>
        <label>Phone: <input type="text" name="phone"></label><br>
        <input type="hidden" name="campsite_guest_form_submitted" value="1">
        <button type="submit">Send Enquiry</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('campsite_guest_form', 'campsite_guest_form_shortcode');

// ... (rest of your original code for guests, pitch types, fees, iCal, etc.)

?>