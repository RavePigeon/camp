<?php
if (!function_exists('campsite_create_or_update_tables')) {
    function campsite_create_or_update_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $guests_table         = $wpdb->prefix . 'campsite_guests';
        $pitch_types_table    = $wpdb->prefix . 'campsite_pitch_types';
        $pitches_table        = $wpdb->prefix . 'campsite_pitches';
        $bookings_table       = $wpdb->prefix . 'campsite_bookings';
        $pitch_guest_fees_table = $wpdb->prefix . 'pitch_guest_fees';
        $pitch_zone_table     = $wpdb->prefix . 'pitch_zone_table';
        $pitch_list_table     = $wpdb->prefix . 'pitch_list';

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

        dbDelta("CREATE TABLE $pitch_list_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            zone_id INT NOT NULL,
            number INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (zone_id) REFERENCES $pitch_zone_table(id)
        );");
    }
}
register_activation_hook(__FILE__, 'campsite_create_or_update_tables');