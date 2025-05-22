<?php
add_action('admin_menu', 'campsite_admin_menu');
function campsite_admin_menu() {
    add_menu_page(
        'Campsite Management',
        'Campsite',
        'manage_options',
        'campsite-management',
        'campsite_admin_dashboard',
        'dashicons-admin-site',
        6
    );
    add_submenu_page(
        'campsite-management',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'campsite-management',
        'campsite_admin_dashboard'
    );
    add_submenu_page(
        'campsite-management',
        'View Guests',
        'View Guests',
        'manage_options',
        'campsite-view-guests',
        'campsite_view_guests'
    );
    add_submenu_page(
        'campsite-management',
        'Add Pitch Type',
        'Add Pitch Type',
        'manage_options',
        'campsite-add-pitch-type',
        'campsite_add_pitch_type_page'
    );
    add_submenu_page(
        'campsite-management',
        'Manage Pitch Types',
        'Manage Pitch Types',
        'manage_options',
        'campsite-manage-pitch-types',
        'campsite_manage_pitch_types_page'
    );
    add_submenu_page(
        'campsite-management',
        'Pitch Guest Fees',
        'Pitch Guest Fees',
        'manage_options',
        'campsite-pitch-guest-fees',
        'campsite_pitch_guest_fees_page'
    );
    add_submenu_page(
        'campsite-management',
        'Pitch Zones',
        'Pitch Zones',
        'manage_options',
        'campsite-pitch-zones',
        'campsite_pitch_zones_page'
    );
    add_submenu_page(
        'campsite-management',
        'Pitch List',
        'Pitch List',
        'manage_options',
        'campsite-pitch-list',
        'campsite_pitch_list_page'
    );
} // <---- THIS closes the campsite_admin_menu function

// Callback functions:
function campsite_admin_dashboard() { echo '<div class="wrap"><h1>Campsite Management Dashboard</h1></div>'; }
function campsite_view_guests() { echo '<div class="wrap"><h1>View Guests</h1></div>'; }
function campsite_add_pitch_type() { echo '<div class="wrap"><h1>Add Pitch Type</h1></div>'; }
function campsite_manage_pitch_types() { echo '<div class="wrap"><h1>Manage Pitch Types</h1></div>'; }
function campsite_pitch_guest_fees() { echo '<div class="wrap"><h1>Pitch Guest Fees</h1></div>'; }
function campsite_pitch_zones() { echo '<div class="wrap"><h1>Pitch Zones</h1></div>'; }
function campsite_pitch_list() { echo '<div class="wrap"><h1>Pitch List</h1></div>'; }