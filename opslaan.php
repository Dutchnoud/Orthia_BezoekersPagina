<?php
$conn = new mysqli("mysql", "root", "Admin@123", "orthia");

if ($conn->connect_error) {
    die("Verbinding mislukt: " . $conn->connect_error);
}

$bed_ids    = $_POST['bed_id'];
$namen      = $_POST['naam'];
$leeftijden = $_POST['leeftijd'];

for ($i = 0; $i < count($bed_ids); $i++) {
    $bed_id   = $conn->real_escape_string($bed_ids[$i]);
    $naam     = $conn->real_escape_string($namen[$i]);
    $leeftijd = $conn->real_escape_string($leeftijden[$i]);

    if ($naam !== "") {
        $sql = "INSERT INTO patienten (bed_id, naam, leeftijd)
                VALUES ('$bed_id', '$naam', '$leeftijd')
                ON DUPLICATE KEY UPDATE naam='$naam', leeftijd='$leeftijd'";
        $conn->query($sql);
    }
}

$conn->close();
header("Location: index.php?opgeslagen=1");
exit;
?>