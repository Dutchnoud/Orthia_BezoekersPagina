<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("mysql", "root", "Admin@123", "orthia");
if ($conn->connect_error) die("Verbinding mislukt: " . $conn->connect_error);

// Verwijder verlopen tijdslots (van gisteren en eerder)
$conn->query("DELETE FROM timeslots WHERE datum < CURDATE()");

// De vaste tijdslots die elke dag aangemaakt worden
$vaste_tijden = [
    ['09:00', '09:30'],
    ['09:30', '10:00'],
    ['10:00', '10:30'],
    ['10:30', '11:00'],
    ['11:00', '11:30'],
    ['11:30', '12:00'],
    ['12:00', '12:30'],
    ['12:30', '13:00'],
    ['13:00', '13:30'],
    ['13:30', '14:00'],
    ['14:00', '14:30'],
    ['14:30', '15:00'],
    ['15:00', '15:30'],
    ['15:30', '16:00'],
    ['16:00', '16:30'],
    ['16:30', '17:00'],
    ['17:00', '17:30'],
    ['17:30', '18:00'],
];

// Maak tijdslots aan voor de komende 7 dagen
for ($i = 0; $i < 7; $i++) {
    $datum = date('Y-m-d', strtotime("+$i days"));

    // Controleer of er al slots zijn voor deze datum
    $check = $conn->query("SELECT COUNT(*) as aantal FROM timeslots WHERE datum = '$datum'");
    $rij = $check->fetch_assoc();

    if ($rij['aantal'] == 0) {
        foreach ($vaste_tijden as $tijd) {
            $start = $tijd[0];
            $eind  = $tijd[1];
            $conn->query("INSERT INTO timeslots (datum, start_time, end_time, is_open)
                          VALUES ('$datum', '$start', '$eind', 1)");
        }
    }
}

$conn->close();

echo "Klaar! Tijdslots aangemaakt.";
?>
