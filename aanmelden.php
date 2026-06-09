<?php
$conn = new mysqli("mysql", "root", "Admin@123", "orthia");
if ($conn->connect_error) die("Verbinding mislukt: " . $conn->connect_error);

$fout = "";
$stap = 1;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Stap 2: tijdslot gekozen, afspraak opslaan
    if (isset($_POST['time_slot_id'])) {
        $naam_bezoeker = $conn->real_escape_string($_POST['naam_bezoeker']);
        $telefoon      = $conn->real_escape_string($_POST['telefoon']);
        $patient_naam  = $conn->real_escape_string($_POST['patient_naam']);
        $time_slot_id  = (int)$_POST['time_slot_id'];

        // Zoek bed_id van de patiënt
        $result = $conn->query("SELECT bed_id FROM patienten WHERE naam = '$patient_naam' LIMIT 1");
        $patient = $result->fetch_assoc();

        if ($patient) {
            $bed_id = $patient['bed_id'];

            $slot = $conn->query("SELECT * FROM timeslots WHERE id_tijdslot = $time_slot_id AND is_open = 1")->fetch_assoc();

            if ($slot) {
                $afspraak_naam = $naam_bezoeker . " (" . $telefoon . ")";
                $datum = $slot['datum'];
                $tijd  = $slot['start_time'];

                $conn->query("INSERT INTO planning (bed_id, afspraak_naam, datum, tijd)
                              VALUES ('$bed_id', '$afspraak_naam', '$datum', '$tijd')");

                $conn->query("UPDATE timeslots SET is_open = 0 WHERE id_tijdslot = $time_slot_id");

                $conn->close();
                header("Location: bevestiging.php");
                exit;
            } else {
                $fout = "Dit tijdslot is niet meer beschikbaar.";
            }
        } else {
            $fout = "Patiëntnaam niet gevonden. Controleer de naam en probeer opnieuw.";
        }

        $stap = 2;
        $naam_bezoeker_val = htmlspecialchars($_POST['naam_bezoeker']);
        $telefoon_val      = htmlspecialchars($_POST['telefoon']);
        $patient_naam_val  = htmlspecialchars($_POST['patient_naam']);

    // Stap 1: gegevens ingevuld, ga naar tijdslot kiezen
    } elseif (isset($_POST['patient_naam'])) {
        $naam_bezoeker = $conn->real_escape_string($_POST['naam_bezoeker']);
        $telefoon      = $conn->real_escape_string($_POST['telefoon']);
        $patient_naam  = $conn->real_escape_string($_POST['patient_naam']);

        $result = $conn->query("SELECT bed_id FROM patienten WHERE naam = '$patient_naam' LIMIT 1");

        if ($result->num_rows > 0) {
            $stap = 2;
            $naam_bezoeker_val = htmlspecialchars($naam_bezoeker);
            $telefoon_val      = htmlspecialchars($telefoon);
            $patient_naam_val  = htmlspecialchars($patient_naam);
        } else {
            $fout = "Patiëntnaam niet gevonden. Controleer de naam en probeer opnieuw.";
            $stap = 1;
        }
    }
}

// Haal open tijdslots op gegroepeerd per datum
$slots_per_datum = [];
$slots_result = $conn->query("SELECT * FROM timeslots WHERE is_open = 1 ORDER BY datum ASC, start_time ASC");
while ($slot = $slots_result->fetch_assoc()) {
    $slots_per_datum[$slot['datum']][] = $slot;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Bezoek aanmelden</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #f4f3ef; --surface: #ffffff; --border: #d6d3cc;
      --text: #1a1916; --text-muted: #8a877e; --accent: #2a2825;
      --accent-light: #e8e5df; --radius: 8px;
      --font-body: 'DM Sans', sans-serif; --font-head: 'Syne', sans-serif;
    }
    body {
      background: var(--bg); font-family: var(--font-body); color: var(--text);
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      padding: 32px 16px;
    }
    .card {
      background: var(--surface); border: 1.5px solid var(--border);
      border-radius: 16px; padding: 40px; width: 100%; max-width: 480px;
      box-shadow: 0 4px 32px rgba(0,0,0,0.07);
    }
    .card h1 { font-family: var(--font-head); font-size: 1.4rem; margin-bottom: 8px; }
    .card p.subtitel { color: var(--text-muted); font-size: 0.88rem; margin-bottom: 28px; }
    .stap-indicator { display: flex; gap: 8px; margin-bottom: 28px; }
    .stap-dot { height: 4px; flex: 1; border-radius: 2px; background: var(--border); }
    .stap-dot.actief { background: var(--accent); }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); }
    input[type="text"], input[type="tel"], select {
      height: 44px; border: 1.5px solid var(--border); border-radius: var(--radius);
      padding: 0 14px; font-family: var(--font-body); font-size: 0.9rem;
      color: var(--text); outline: none; transition: border-color 0.15s;
      background: var(--surface); width: 100%;
    }
    input:focus, select:focus { border-color: var(--accent); }
    .fout {
      background: #fde8e8; color: #8b1a1a; border: 1.5px solid #f5c6c6;
      border-radius: var(--radius); padding: 10px 14px; font-size: 0.85rem;
      margin-bottom: 16px;
    }
    .slot-lijst { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; margin-top: 12px; }
    .slot-optie {
      display: flex; align-items: center; gap: 12px;
      border: 1.5px solid var(--border); border-radius: var(--radius);
      padding: 12px 16px; cursor: pointer; transition: border-color 0.15s, background 0.15s;
    }
    .slot-optie:hover { border-color: var(--accent); background: var(--accent-light); }
    .slot-optie input[type="radio"] { accent-color: var(--accent); width: 16px; height: 16px; }
    .slot-optie span { font-size: 0.9rem; font-weight: 500; }
    .geen-slots { color: var(--text-muted); font-size: 0.88rem; text-align: center; padding: 20px 0; }
    .btn {
      width: 100%; height: 46px; background: var(--accent); color: #fff;
      border: none; border-radius: var(--radius); font-family: var(--font-body);
      font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: background 0.15s;
      margin-top: 8px;
    }
    .btn:hover { background: #3d3a36; }
    .terug {
      display: block; text-align: center; margin-top: 14px;
      color: var(--text-muted); font-size: 0.85rem; cursor: pointer;
      text-decoration: underline; background: none; border: none;
      font-family: var(--font-body);
    }
  </style>
</head>
<body>
<div class="card">

  <?php if ($stap == 1): ?>
    <h1>Bezoek aanmelden</h1>
    <p class="subtitel">Vul uw gegevens in en de naam van de patiënt die u wilt bezoeken.</p>

    <div class="stap-indicator">
      <div class="stap-dot actief"></div>
      <div class="stap-dot"></div>
    </div>

    <?php if ($fout): ?>
      <div class="fout"><?= $fout ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Uw naam</label>
        <input type="text" name="naam_bezoeker" placeholder="Volledige naam" required />
      </div>
      <div class="form-group">
        <label>Telefoonnummer</label>
        <input type="tel" name="telefoon" placeholder="06 12345678" required />
      </div>
      <div class="form-group">
        <label>Naam patiënt</label>
        <input type="text" name="patient_naam" placeholder="Naam van de patiënt" required />
      </div>
      <button type="submit" class="btn">Volgende →</button>
    </form>

  <?php elseif ($stap == 2): ?>
    <h1>Kies een tijdslot</h1>
    <p class="subtitel">Selecteer eerst een datum en daarna een bezoekmoment.</p>

    <div class="stap-indicator">
      <div class="stap-dot actief"></div>
      <div class="stap-dot actief"></div>
    </div>

    <?php if ($fout): ?>
      <div class="fout"><?= $fout ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="naam_bezoeker" value="<?= $naam_bezoeker_val ?>" />
      <input type="hidden" name="telefoon" value="<?= $telefoon_val ?>" />
      <input type="hidden" name="patient_naam" value="<?= $patient_naam_val ?>" />

      <?php if (!empty($slots_per_datum)): ?>

        <div class="form-group">
          <label>Datum</label>
          <select id="datum-select" onchange="toonSlots(this.value)">
            <option value="">-- Kies een datum --</option>
            <?php foreach ($slots_per_datum as $datum => $slots): ?>
              <option value="<?= $datum ?>"><?= date('d-m-Y', strtotime($datum)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php foreach ($slots_per_datum as $datum => $slots): ?>
        <div class="slot-lijst" id="slots-<?= $datum ?>" style="display:none;">
          <?php foreach ($slots as $slot): ?>
          <label class="slot-optie">
            <input type="radio" name="time_slot_id" value="<?= $slot['id_tijdslot'] ?>" required />
            <span><?= substr($slot['start_time'], 0, 5) ?> – <?= substr($slot['end_time'], 0, 5) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="btn">Bezoek bevestigen</button>

      <?php else: ?>
        <div class="geen-slots">Geen tijdslots beschikbaar op dit moment.</div>
      <?php endif; ?>
    </form>

    <form method="POST">
      <button type="submit" class="terug">← Terug</button>
    </form>

  <?php endif; ?>

</div>

<script>
  function toonSlots(datum) {
    document.querySelectorAll('.slot-lijst').forEach(d => d.style.display = 'none');
    if (datum) {
      const lijst = document.getElementById('slots-' + datum);
      if (lijst) lijst.style.display = 'flex';
    }
  }
</script>

</body>
</html>
