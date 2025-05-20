<?php
/**
 * Plugin Name: Campsite Management Plugin
 * Description: A plugin to manage campsite pitches, guests, and bookings with Pitchup.com and iCal integration.
 * Version: 1.2
 * Author: RavePigeon
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Activation Hook: Create or Update Database Tables
register_activation_hook(__FILE__, 'campsite_create_or_update_tables');

function campsite_create_or_update_tables() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $guests_table = $wpdb->prefix . 'campsite_guests';
    $pitch_types_table = $wpdb->prefix . 'campsite_pitch_types';
    $pitches_table = $wpdb->prefix . 'campsite_pitches';
    $bookings_table = $wpdb->prefix . 'campsite_bookings';

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
        max_occupancy INT NOT NULL,
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
}
// CSS for admin form
// Enqueue custom styles for the admin page
function campsite_enqueue_admin_styles($hook) {
    // Only load CSS on the campsite management page
    if ($hook != 'toplevel_page_campsite-management') {
        return;
    }

    wp_enqueue_style('campsite-admin-style', plugin_dir_url(__FILE__) . 'css/admin-style.css');
}
add_action('admin_enqueue_scripts', 'campsite_enqueue_admin_styles');

add_action('admin_menu', 'campsite_add_admin_menu');
function campsite_add_admin_menu() {
    add_menu_page('Campsite Management', 'Campsite Management', 'manage_options', 'campsite-management', 'campsite_admin_dashboard', 'dashicons-admin-site-alt3', 3);
}

function campsite_admin_dashboard() {
    ?>
    <div class="wrap">
        <h1>Campsite Management Dashboard</h1>
        <form method="post" action="">
            <label for="guest">Select Guest:</label>
            <select name="guest" id="guest">
                <?php
                $guests = get_posts(['post_type' => 'guest', 'numberposts' => -1]);
                foreach ($guests as $guest) {
                    echo "<option value='{$guest->ID}'>{$guest->post_title}</option>";
                }
                ?>
            </select>

            <label for="pitch">Select Pitch:</label>
            <select name="pitch" id="pitch">
                <?php
                $pitches = get_posts(['post_type' => 'pitch', 'numberposts' => -1]);
                foreach ($pitches as $pitch) {
                    echo "<option value='{$pitch->ID}'>{$pitch->post_title}</option>";
                }
                ?>
            </select>

            <label for="check_in_date">Check-in Date:</label>
            <input type="date" name="check_in_date" id="check_in_date" required>

            <label for="check_out_date">Check-out Date:</label>
            <input type="date" name="check_out_date" id="check_out_date" required>

            <button type="submit" name="allocate_pitch">Allocate Pitch</button>
        </form>
        <?php
        if (isset($_POST['allocate_pitch'])) {
            $guest_id = sanitize_text_field($_POST['guest']);
            $pitch_id = sanitize_text_field($_POST['pitch']);
            $check_in_date = sanitize_text_field($_POST['check_in_date']);
            $check_out_date = sanitize_text_field($_POST['check_out_date']);

            if (strtotime($check_in_date) >= strtotime($check_out_date)) {
                echo "<p style='color: red;'>Check-out date must be after check-in date.</p>";
                return;
            }

            $overlapping = new WP_Query([
                'post_type' => 'booking',
                'posts_per_page' => -1,
                'meta_query' => [
                    'relation' => 'AND',
                    ['key' => 'pitch_id', 'value' => $pitch_id],
                    ['key' => 'check_out_date', 'value' => $check_in_date, 'compare' => '>'],
                    ['key' => 'check_in_date', 'value' => $check_out_date, 'compare' => '<']
                ]
            ]);

            if ($overlapping->found_posts > 0) {
                echo "<p style='color: red;'>This pitch is already booked during the selected period.</p>";
                return;
            }

            $booking_id = wp_insert_post([
                'post_type' => 'booking',
                'post_title' => "Booking for Guest {$guest_id}",
                'post_status' => 'publish',
                'meta_input' => [
                    'guest_id' => $guest_id,
                    'pitch_id' => $pitch_id,
                    'check_in_date' => $check_in_date,
                    'check_out_date' => $check_out_date
                ]
            ]);

            if ($booking_id) {
                campsite_sync_with_pitchup($pitch_id, $check_in_date, $check_out_date);
                echo "<p>Booking successfully created with ID: {$booking_id}</p>";
            } else {
                echo "<p>Failed to create booking. Please try again.</p>";
            }
        }
        ?>
    </div>
    <?php
}
// [campsite_guest_form] shortcode to display guest inquiry form
function campsite_guest_form_shortcode() {
    ob_start();
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['campsite_guest_form_submitted'])) {
        global $wpdb;
        $guests_table = $wpdb->prefix . 'campsite_guests';

        // Sanitize inputs
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name  = sanitize_text_field($_POST['last_name']);
        $email      = sanitize_email($_POST['email']);
        $phone      = sanitize_text_field($_POST['phone']);

        // Basic validation
        if (empty($first_name) || empty($last_name) || empty($email)) {
            echo '<p style="color:red;">Please fill in all required fields.</p>';
        } else {
            // Insert guest into the DB
            $result = $wpdb->insert(
                $guests_table,
                array(
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                    'email'      => $email,
                    'phone'      => $phone,
                ),
                array('%s', '%s', '%s', '%s')
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

// Add submenu page under Campsite Management for viewing guests
add_action('admin_menu', function() {
    add_submenu_page(
        'campsite-management',           // Parent slug
        'View Guests',                   // Page title
        'View Guests',                   // Menu title
        'manage_options',                // Capability
        'campsite-view-guests',          // Menu slug
        'campsite_view_guests_callback'  // Function to display the page
    );
});

// Callback function to display guests
function campsite_view_guests_callback() {
    global $wpdb;
    $guests_table = $wpdb->prefix . 'campsite_guests';
    $guests = $wpdb->get_results("SELECT * FROM $guests_table ORDER BY created_at DESC");

    echo '<div class="wrap"><h1>Guest Records</h1>';
    if ($guests) {
        echo '<table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Date Created</th>
                </tr>
            </thead>
            <tbody>';
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


// 1. Add submenu under "Campsite Management" for Pitch Types
add_action('admin_menu', function() {
    add_submenu_page(
        'campsite-management',          // Parent slug
        'Add Pitch Type',               // Page title
        'Add Pitch Type',               // Menu title
        'manage_options',               // Capability
        'campsite-add-pitch-type',      // Menu slug
        'campsite_add_pitch_type_page'  // Callback
    );
});

// 2. Callback function to display and handle the form
function campsite_add_pitch_type_page() {
    global $wpdb;
    $pitch_types_table = $wpdb->prefix . 'campsite_pitch_types';
    $message = '';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campsite_add_pitch_type_submit'])) {
        $type_name      = sanitize_text_field($_POST['type_name']);
        $description    = sanitize_textarea_field($_POST['description']);
        $max_occupancy  = intval($_POST['max_occupancy']);
        $price_per_night = floatval($_POST['price_per_night']);

        if ($type_name && $max_occupancy && $price_per_night) {
            $result = $wpdb->insert(
                $pitch_types_table,
                [
                    'type_name'        => $type_name,
                    'description'      => $description,
                    'max_occupancy'    => $max_occupancy,
                    'price_per_night'  => $price_per_night,
                ],
                ['%s', '%s', '%d', '%f']
            );
            if ($result) {
                $message = '<p style="color:green;">Pitch type added successfully!</p>';
            } else {
                $message = '<p style="color:red;">Failed to add pitch type. Please try again.</p>';
            }
        } else {
            $message = '<p style="color:red;">Please complete all required fields.</p>';
        }
    }

    // Form markup
    ?>
    <div class="wrap">
        <h1>Add Pitch Type</h1>
        <?php echo $message; ?>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="type_name">Pitch Code*</label></th>
                    <td><input type="text" name="type_name" id="type_name" required></td>
                </tr>
                <tr>
                    <th><label for="description">Description</label></th>
                    <td><textarea name="description" id="description" rows="3"></textarea></td>
                </tr>
                <tr>
                    <th><label for="price_per_night">Basic Pitch Fee (£)*</label></th>
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

// Add "Manage Pitch Types" submenu
add_action('admin_menu', function() {
    add_submenu_page(
        'campsite-management',
        'Manage Pitch Types',
        'Manage Pitch Types',
        'manage_options',
        'campsite-manage-pitch-types',
        'campsite_manage_pitch_types_page'
    );
});

// Display/manage pitch types
function campsite_manage_pitch_types_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'campsite_pitch_types';

    // Handle delete
    if (isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
        $id = intval($_GET['delete']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_pitch_type_'.$id)) {
            $wpdb->delete($table, ['id' => $id]);
            echo '<div class="updated"><p>Pitch type deleted.</p></div>';
        }
    }

    // Handle edit form submission
    if (isset($_POST['edit_pitch_type_submit'])) {
        $id = intval($_POST['id']);
        $type_name = sanitize_text_field($_POST['type_name']);
        $description = sanitize_textarea_field($_POST['description']);
        $max_occupancy = intval($_POST['max_occupancy']);
        $price_per_night = floatval($_POST['price_per_night']);
        $wpdb->update(
            $table,
            [
                'type_name' => $type_name,
                'description' => $description,
                'max_occupancy' => $max_occupancy,
                'price_per_night' => $price_per_night
            ],
            ['id' => $id],
            ['%s', '%s', '%d', '%f'],
            ['%d']
        );
        echo '<div class="updated"><p>Pitch type updated.</p></div>';
    }

    // Show edit form if editing
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
                        <th><label for="max_occupancy">Max Occupancy*</label></th>
                        <td><input type="number" name="max_occupancy" id="max_occupancy" value="<?php echo esc_attr($pitch_type->max_occupancy); ?>" min="1" required></td>
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

    // List all pitch types
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
                    <th>Max Occupancy</th>
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
                    <td><?php echo esc_html($pt->max_occupancy); ?></td>
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

// Pitchup API Sync
function campsite_sync_with_pitchup($pitch_id, $check_in, $check_out) {
    $api_key = 'YOUR_API_KEY'; // Replace with your actual Pitchup API key
    $url = 'https://api.pitchup.com/availability';

    $body = json_encode([
        'pitch_id' => $pitch_id,
        'check_in_date' => $check_in,
        'check_out_date' => $check_out
    ]);

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => $body
    ]);
}

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
