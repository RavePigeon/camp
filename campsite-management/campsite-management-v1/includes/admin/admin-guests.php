<?php>

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