<?php
$conn = new mysqli("mysql", "root", "Admin@123", "orthia");
if ($conn->connect_error) die("Verbinding mislukt: " . $conn->connect_error);

$bed_id = (int)$_GET['bed_id'];

if ($bed_id > 0) {
    $conn->query("DELETE FROM patienten WHERE bed_id = $bed_id");
}

$conn->close();
header("Location: index.php");
exit;
?>
