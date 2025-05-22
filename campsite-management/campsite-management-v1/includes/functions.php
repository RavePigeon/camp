<?php



// ========================== ADMIN MENU ==========================
add_action('admin_menu', function() {
    // Main menu
    add_menu_page('Campsite Management', 'Campsite Management', 'manage_options', 'campsite-management', 'campsite_admin_dashboard', 'dashicons-admin-site-alt3', 3);
    // Your existing submenu pages (leave as they are)
    add_submenu_page('campsite-management', 'View Guests', 'View Guests', 'manage_options', 'campsite-view-guests', 'campsite_view_guests_callback');
    add_submenu_page('campsite-management', 'Add Pitch Type', 'Add Pitch Type', 'manage_options', 'campsite-add-pitch-type', 'campsite_add_pitch_type_page');
    add_submenu_page('campsite-management', 'Manage Pitch Types', 'Manage Pitch Types', 'manage_options', 'campsite-manage-pitch-types', 'campsite_manage_pitch_types_page');
    add_submenu_page('campsite-management', 'Pitch Guest Fees', 'Pitch Guest Fees', 'manage_options', 'campsite-pitch-guest-fees', 'campsite_pitch_guest_fees_page');
    // NEW: Pitch Zones page
    add_submenu_page('campsite-management', 'Pitch Zones', 'Pitch Zones', 'manage_options', 'campsite-pitch-zones', 'campsite_pitch_zones_page');
});

// ========================== DASHBOARD PAGE ==========================
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
