<?php
// This is a scaffold for a shortcode front-end booking form.
// Assumes you have a database connection $pdo (PDO) already set up.

function get_zones($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT zone FROM pitch_allocation");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function get_available_pitches($pdo, $arrival, $nights, $zone) {
    $departure = date('Y-m-d', strtotime("$arrival +$nights days"));

    $sql = "SELECT pitch_code FROM pitch_allocation
            WHERE zone = :zone
            AND NOT (
                (arrival_date >= :departure) OR (departure_date <= :arrival)
            )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':zone' => $zone,
        ':arrival' => $arrival,
        ':departure' => $departure
    ]);
    $booked_pitches = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Replace this with your master pitch list source:
    $all_pitches = get_all_pitches_in_zone($pdo, $zone); // e.g. from another table

    // Filter out booked pitches
    $available = array_diff($all_pitches, $booked_pitches);
    return $available;
}

// Handle form
$zones = get_zones($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $arrival = $_POST['arrival_date'];
    $nights = intval($_POST['nights']);
    $zone = $_POST['zone'];
    $available_pitches = get_available_pitches($pdo, $arrival, $nights, $zone);
}
?>

<form method="POST">
    <label>Arrival Date: <input type="date" name="arrival_date" required></label><br>
    <label>Number of Nights: <input type="number" name="nights" min="1" required></label><br>
    <label>Zone:
        <select name="zone" required>
            <?php foreach ($zones as $z): ?>
                <option value="<?= htmlspecialchars($z) ?>"><?= htmlspecialchars($z) ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>
    <button type="submit">Check Availability</button>
</form>

<?php if (isset($available_pitches)): ?>
    <h3>Available Pitches:</h3>
    <ul>
        <?php foreach ($available_pitches as $pitch): ?>
            <li><?= htmlspecialchars($pitch) ?></li>
        <?php endforeach; ?>
        <?php if (empty($available_pitches)): ?>
            <li>No pitches available for the selected dates and zone.</li>
        <?php endif; ?>
    </ul>
<?php endif; ?>

<?php
// Dummy function for demo; replace with actual pitch zone source
function get_all_pitches_in_zone($pdo, $zone) {
    // Example: fetch from a separate pitches table
    $stmt = $pdo->prepare("SELECT pitch_code FROM pitches WHERE zone = :zone");
    $stmt->execute([':zone' => $zone]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>