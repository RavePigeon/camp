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
