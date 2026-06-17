<?php
$conn = new mysqli("mysql", "root", "Admin@123", "orthia");
if ($conn->connect_error) die("Verbinding mislukt: " . $conn->connect_error);

$patienten = [];
$result = $conn->query("SELECT * FROM patienten");
while ($row = $result->fetch_assoc()) {
    $patienten[$row['bed_id']] = $row;
}

$planningen = [];
$result2 = $conn->query("SELECT * FROM planning ORDER BY datum ASC, tijd ASC");
while ($row = $result2->fetch_assoc()) {
    $planningen[$row['bed_id']][] = $row;
}

$conn->close();

function buildRows($start, $eind, $patienten, $planningen) {
    for ($i = $start; $i <= $eind; $i++) {
        $naam     = htmlspecialchars($patienten[$i]['naam'] ?? '');
        $leeftijd = htmlspecialchars($patienten[$i]['geboortedatum'] ?? '');
        $bezet    = !empty($naam);
        $afspraken = $planningen[$i] ?? [];
        $geboortedatum = isset($patienten[$i]['geboortedatum']) && $patienten[$i]['geboortedatum'] 
           ? date('d-m-Y', strtotime($patienten[$i]['geboortedatum'])) 
            : '';
        ?>
        <div class="row-item">
            <div class="cell narrow readonly"><?= $i ?></div>
            <div class="cell readonly"><?= $bezet ? $naam : '<span class="leeg">Leeg</span>' ?></div>
            <div class="cell narrow readonly"><?= $bezet ? $leeftijd : '' ?></div>
            <div class="cell planning-cell">
                <div class="planning-dropdown">
                    <button type="button" class="planning-toggle" onclick="toggleDropdown(<?= $i ?>)">
                        <?= count($afspraken) > 0 ? count($afspraken) . ' afspraak' . (count($afspraken) > 1 ? 'en' : '') : 'Geen afspraken' ?>
                        <span class="arrow">▾</span>
                    </button>
                    <div class="dropdown-list" id="list-<?= $i ?>" style="display:none;">
                        <?php if (empty($afspraken)): ?>
                            <div class="geen-afspraken">Geen afspraken gepland</div>
                        <?php endif; ?>
                        <?php foreach ($afspraken as $afspraak): ?>
                        <div class="afspraak-item">
                            <span><?= htmlspecialchars($afspraak['afspraak_naam']) ?></span>
                            <span><?= htmlspecialchars($afspraak['datum']) ?></span>
                            <span><?= htmlspecialchars(substr($afspraak['tijd'], 0, 5)) ?></span>
                            <a href="verwijder_afspraak.php?id=<?= $afspraak['id'] ?>" class="delete-btn" onclick="return confirm('Afspraak verwijderen?')">✕</a>
                        </div>
                        <?php endforeach; ?>
                        <div class="afspraak-nieuw">
                            <form method="POST" action="afspraak_toevoegen.php" style="display:contents;">
                                <input type="hidden" name="bed_id" value="<?= $i ?>" />
                                <input type="text" placeholder="Afspraak naam" name="afspraak_naam" />
                                <input type="date" name="datum" />
                                <input type="time" name="tijd" />
                                <button type="submit" class="add-btn">+</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($bezet): ?>
            <a href="verwijder_patient.php?bed_id=<?= $i ?>" class="verwijder-btn" onclick="return confirm('Patiënt verwijderen uit bed <?= $i ?>?')">✕</a>
            <?php else: ?>
            <div></div>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Overzichtspagina</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #f4f3ef; --surface: #ffffff; --border: #d6d3cc;
      --text: #1a1916; --text-muted: #8a877e; --accent: #2a2825;
      --accent-light: #e8e5df; --radius: 6px;
      --font-body: 'DM Sans', sans-serif; --font-head: 'Syne', sans-serif;
    }
    body {
      background: var(--bg); font-family: var(--font-body); color: var(--text);
      min-height: 100vh; display: flex; flex-direction: column;
      align-items: center; justify-content: flex-start; padding: 32px 16px; gap: 24px;
    }
    .page-wrapper {
      width: 100%; max-width: 1100px; background: var(--surface);
      border: 1.5px solid var(--border); border-radius: 12px; overflow: hidden;
      box-shadow: 0 4px 32px rgba(0,0,0,0.07);
    }
    .topbar {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 20px; border-bottom: 1.5px solid var(--border);
    }
    .topbar-title { font-family: var(--font-head); font-size: 1.1rem; }
    .content { display: grid; grid-template-columns: 1fr 1fr; min-height: 520px; }
    .panel { padding: 20px; }
    .panel:first-child { border-right: 1.5px solid var(--border); }
    .col-header {
      display: grid; grid-template-columns: 44px 1fr 110px 1fr 24px;
      gap: 8px; padding-bottom: 10px; border-bottom: 1.5px solid var(--border); margin-bottom: 10px;
    }
    .col-header span {
      font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.08em; color: var(--text-muted);
    }
    .row-list { display: flex; flex-direction: column; gap: 8px; }
    .row-item {
      display: grid; grid-template-columns: 44px 1fr 110px 1fr 24px;
      gap: 8px; align-items: center;
    }
    .cell {
      height: 38px; border: 1.5px solid var(--border); border-radius: var(--radius);
      background: var(--surface); padding: 0 10px; display: flex; align-items: center;
      font-size: 0.82rem; color: var(--text);
    }
    .cell.readonly { background: #faf9f6; color: var(--text); cursor: default; }
    .cell .leeg { color: var(--text-muted); font-style: italic; }
    .planning-cell { position: relative; height: auto; min-height: 38px; padding: 0; border: none; background: transparent; }
    .planning-dropdown { width: 100%; }
    .planning-toggle {
      width: 100%; height: 38px; border: 1.5px solid var(--border); border-radius: var(--radius);
      background: var(--surface); font-family: var(--font-body); font-size: 0.82rem;
      color: var(--text); cursor: pointer; padding: 0 10px;
      display: flex; align-items: center; justify-content: space-between;
      transition: background 0.15s;
    }
    .planning-toggle:hover { background: var(--accent-light); border-color: var(--accent); }
    .dropdown-list {
      position: absolute; top: 40px; right: 0; left: auto; z-index: 100;
      background: var(--surface); border: 1.5px solid var(--border);
      border-radius: var(--radius); box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      min-width: 320px;
    }
    .geen-afspraken {
      padding: 10px; font-size: 0.8rem; color: var(--text-muted); text-align: center;
    }
    .afspraak-item {
      display: grid; grid-template-columns: 1fr 90px 50px 24px;
      gap: 6px; padding: 8px 10px; border-bottom: 1px solid var(--border);
      font-size: 0.8rem; align-items: center;
    }
    .delete-btn {
      color: #cc0000; font-size: 0.75rem; cursor: pointer; text-decoration: none; text-align: center;
    }
    .delete-btn:hover { color: #ff0000; }
    .afspraak-nieuw {
      display: grid; grid-template-columns: 1fr 100px 70px 28px;
      gap: 6px; padding: 8px 10px; background: var(--accent-light);
      border-top: 1.5px solid var(--border);
      border-radius: 0 0 var(--radius) var(--radius); align-items: center;
    }
    .afspraak-nieuw input {
      height: 28px; border: 1.5px solid var(--border); border-radius: 4px;
      padding: 0 6px; font-family: var(--font-body); font-size: 0.78rem;
      background: var(--surface); outline: none; color: var(--text);
    }
    .afspraak-nieuw input:focus { border-color: var(--accent); }
    .add-btn {
      height: 28px; width: 28px; border: 1.5px solid var(--accent);
      border-radius: 4px; background: var(--accent); color: #fff;
      font-size: 1rem; cursor: pointer; display: flex;
      align-items: center; justify-content: center; padding: 0;
    }
    .add-btn:hover { background: #3d3a36; }
    .verwijder-btn {
      color: #cc0000; font-size: 0.9rem; cursor: pointer;
      text-decoration: none; text-align: center; font-weight: 600;
    }
    .verwijder-btn:hover { color: #ff0000; }

    /* ── TOEVOEG FORMULIER ── */
    .add-patient-form {
      width: 100%; max-width: 1100px; background: var(--surface);
      border: 1.5px solid var(--border); border-radius: 12px;
      padding: 20px 24px; box-shadow: 0 4px 32px rgba(0,0,0,0.07);
    }
    .add-patient-form h2 {
      font-family: var(--font-head); font-size: 1rem; margin-bottom: 16px;
    }
    .form-row { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
    .form-group { display: flex; flex-direction: column; gap: 4px; }
    .form-group label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); }
    .form-group input, .form-group select {
      height: 38px; border: 1.5px solid var(--border); border-radius: var(--radius);
      padding: 0 10px; font-family: var(--font-body); font-size: 0.82rem;
      background: var(--surface); color: var(--text); outline: none;
      min-width: 140px;
    }
    .form-group input:focus, .form-group select:focus { border-color: var(--accent); }
    .btn-toevoegen {
      height: 38px; padding: 0 20px; background: var(--accent); color: #fff;
      border: 1.5px solid var(--accent); border-radius: var(--radius);
      font-family: var(--font-body); font-size: 0.82rem; font-weight: 500;
      cursor: pointer; transition: background 0.15s;
    }
    .btn-toevoegen:hover { background: #3d3a36; }

    .success-banner {
      background: #d4edda; color: #155724; padding: 10px 20px;
      font-size: 0.85rem; border-bottom: 1.5px solid var(--border);
    }
  </style>
</head>
<body>

  <div class="page-wrapper">
    <div class="topbar">
      <span class="topbar-title">Overzicht</span>
    </div>

    <?php if (isset($_GET['opgeslagen'])): ?>
      <div class="success-banner">✓ Gegevens succesvol opgeslagen.</div>
    <?php endif; ?>

    <div class="content">
      <div class="panel">
        <div class="col-header">
          <span>Bed.ID</span><span>Naam</span><span>Geboortedatum</span><span>Planning</span><span></span>
        </div>
        <div class="row-list"><?php buildRows(1, 8, $patienten, $planningen); ?></div>
      </div>
      <div class="panel">
        <div class="col-header">
          <span>Bed.ID</span><span>Naam</span><span>Geboortedatum</span><span>Planning</span><span></span>
        </div>
        <div class="row-list"><?php buildRows(9, 16, $patienten, $planningen); ?></div>
      </div>
    </div>
  </div>

  <!-- PATIËNT TOEVOEGEN FORMULIER -->
  <div class="add-patient-form">
    <h2>Patiënt toevoegen / wijzigen</h2>
    <form method="POST" action="patient_opslaan.php">
      <div class="form-row">
        <div class="form-group">
          <label>Bed ID</label>
          <select name="bed_id">
            <?php for ($i = 1; $i <= 16; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?><?= isset($patienten[$i]) ? ' — ' . htmlspecialchars($patienten[$i]['naam']) : ' (leeg)' ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Naam</label>
          <input type="text" name="naam" placeholder="Naam patiënt" required />
        </div>
        <div class="form-group">
          <label>Geboortedatum</label>
          <input type="date" name="geboortedatum" />
        </div>
        <button type="submit" class="btn-toevoegen">Opslaan</button>
      </div>
    </form>
  </div>

    
<script>
  function toggleDropdown(id) {
    const list = document.getElementById('list-' + id);
    const isOpen = list.style.display !== 'none';
    document.querySelectorAll('.dropdown-list').forEach(d => d.style.display = 'none');
    if (!isOpen) list.style.display = 'block';
  }
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.planning-dropdown')) {
      document.querySelectorAll('.dropdown-list').forEach(d => d.style.display = 'none');
    }
  });
</script>
<div style="position: fixed; bottom: 16px; right: 16px; background: var(--accent); color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-family: var(--font-body);">
  v__VERSION__
</div>
</body>
</html>
