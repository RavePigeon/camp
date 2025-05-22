<?php

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