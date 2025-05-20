<?php
/**
 * Plugin Name: Campsite Management Plugin
 * Description: A plugin to manage campsite pitches, guests, pitch fees, and bookings with Pitchup.com and iCal integration.
 * Version: 1.3
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
        </ul>
    </div>
    <?php
}

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

// ----------- Admin: View Guests -----------
function campsite_view_guests_callback() {
    global $wpdb;
    $guests_table = $wpdb->prefix . 'campsite_guests';
    $guests = $wpdb->get_results("SELECT * FROM $guests_table ORDER BY created_at DESC");
    echo '<div class="wrap"><h1>Guest Records</h1>';
    if ($guests) {
        echo '<table class="widefat fixed" cellspacing="0"><thead><tr>
            <th>ID</th><th>First Name</th><th>Last Name</th>
            <th>Email</th><th>Phone</th><th>Date Created</th>
            </tr></thead><tbody>';
        foreach ($guests as $guest) {
            echo '<tr>
                <td>' . esc_html($guest->id) . '</td>
                <td>' . esc_html($guest->first_name) . '</td>
                <td>' . esc_html($guest->last_name) . '</td>
                <td>' . esc_html($guest->email) . '</td>
                <td>' . esc_html($guest->phone) . '</td>
                <td>' . esc_html($guest->created_at) . '</td>
            </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No guest records found.</p>';
    }
    echo '</div>';
}

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

// ----------- (Optional: Booking and iCal Integration) -----------
/* ... Keep your original booking/Pitchup/iCal code here if you need it ... */


// iCal Feed Endpoint
add_action('init', function () {
    add_rewrite_rule('^campsite-ical/?', 'index.php?campsite_ical=1', 'top');
    add_rewrite_tag('%campsite_ical%', '1');
    add_rewrite_tag('%pitch_id%', '([0-9]+)');
});

add_action('template_redirect', function () {
    if (get_query_var('campsite_ical')) {
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="bookings.ics"');

        $pitch_id = get_query_var('pitch_id');
        $bookings = new WP_Query([
            'post_type' => 'booking',
            'posts_per_page' => -1,
            'meta_query' => [
                ['key' => 'pitch_id', 'value' => $pitch_id, 'compare' => '=']
            ]
        ]);

        echo "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//Campsite Plugin//EN\n";

        while ($bookings->have_posts()) {
            $bookings->the_post();
            $checkin = get_post_meta(get_the_ID(), 'check_in_date', true);
            $checkout = get_post_meta(get_the_ID(), 'check_out_date', true);
            $uid = get_the_ID() . '@yourdomain.com';
            echo "BEGIN:VEVENT\n";
            echo "UID:$uid\n";
            echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\n";
            echo "DTSTART;VALUE=DATE:" . date('Ymd', strtotime($checkin)) . "\n";
            echo "DTEND;VALUE=DATE:" . date('Ymd', strtotime($checkout)) . "\n";
            echo "SUMMARY:Booking #" . get_the_ID() . "\n";
            echo "END:VEVENT\n";
        }

        echo "END:VCALENDAR";
        exit;
    }
});
