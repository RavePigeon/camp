<?php
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
            <li><a href="<?php echo admin_url('admin.php?page=campsite-pitch-list'); ?>">Pitch List</a></li>
        </ul>
    </div>
    <?php
}