<?php

function campsite_add_pitch_type_page() {
    echo '<div class="wrap"><h1>Add Pitch Type</h1>';
    echo '<form method="post">';
    echo '<label>Type Name*: <input type="text" name="type_name" required></label><br>';
    echo '<label>Description: <textarea name="description"></textarea></label><br>';
    echo '<label>Price Per Night (£)*: <input type="number" name="price_per_night" min="0" step="0.01" required></label><br>';
    echo '<button type="submit">Add Pitch Type</button>';
    echo '</form></div>';
}
