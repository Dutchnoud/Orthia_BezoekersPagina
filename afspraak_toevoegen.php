<?php
$conn = new mysqli("mysql", "root", "Admin@123", "orthia");
if ($conn->connect_error) die("Verbinding mislukt: " . $conn->connect_error);

$bed_id   = $conn->real_escape_string($_POST['bed_id']);
$afspraak = $conn->real_escape_string($_POST['afspraak_naam'] ?? '');
$datum    = $conn->real_escape_string($_POST['datum'] ?? '');
$tijd     = $conn->real_escape_string($_POST['tijd'] ?? '');

if ($afspraak !== "" && $bed_id !== "") {
    $conn->query("INSERT INTO planning (bed_id, afspraak_naam, datum, tijd)
                  VALUES ('$bed_id', '$afspraak', '$datum', '$tijd')");
}

$conn->close();
header("Location: index.php?opgeslagen=1");
exit;
?>