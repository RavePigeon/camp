<?php
/**
 * Plugin Name: Campsite Management Plugin
 * Description: ...
 * Version: 1.5
 * Author: ...
 */

if (!defined('ABSPATH')) exit;

// Load database setup
require_once plugin_dir_path(__FILE__) . 'includes/db/db-create-tables.php';
require_once plugin_dir_path(__FILE__) . 'includes/db/db-query-functions.php';

// Helpers
require_once plugin_dir_path(__FILE__) . 'includes/helpers/utility-functions.php';

// General functions and menu setup
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

// Admin pages (only for admin)
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-dashboard.php';
    require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-pitch-zones.php';
    require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-pitch-list.php';
    require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-settings.php';
    // Add others as needed
}

// Public pages (only for frontend)
if (!is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'includes/public/public-bookings.php';
}