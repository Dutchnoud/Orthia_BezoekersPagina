<?php
$conn = new mysqli("mysql", "root", "Admin@123", "orthia");
if ($conn->connect_error) die("Verbinding mislukt: " . $conn->connect_error);

$bed_id       = (int)$_POST['bed_id'];
$naam         = $conn->real_escape_string($_POST['naam']);
$geboortedatum = $conn->real_escape_string($_POST['geboortedatum'] ?? '');

if ($naam !== "" && $bed_id > 0) {
    $conn->query("INSERT INTO patienten (bed_id, naam, geboortedatum)
                  VALUES ('$bed_id', '$naam', '$geboortedatum')
                  ON DUPLICATE KEY UPDATE naam='$naam', geboortedatum='$geboortedatum'");
}

$conn->close();
header("Location: index.php?opgeslagen=1");
exit;
?>