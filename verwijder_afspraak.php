<?php
$conn = new mysqli("mysql", "root", "Admin@123", "orthia");
if ($conn->connect_error) die("Verbinding mislukt: " . $conn->connect_error);

$id = (int)$_GET['id'];
$conn->query("DELETE FROM planning WHERE id = $id");

$conn->close();
header("Location: index.php");
exit;
?>
