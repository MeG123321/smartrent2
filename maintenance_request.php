<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Pobierz wynajęte mieszkania przez użytkownika
$stmt = $pdo->prepare("SELECT a.id AS assignment_id, a.property_id, p.title, p.city, p.description
                       FROM assignments a
                       JOIN properties p ON a.property_id = p.id
                       WHERE a.tenant_id = :uid AND a.status = 'confirmed'
                       ORDER BY a.created_at DESC");
$stmt->execute(['uid' => $user_id]);
$rented_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Typy usterek
$issue_types = [
    'plumbing' => 'Hydraulika',
    'electrical' => 'Elektryka',
    'heating' => 'Ogrzewanie',
    'windows' => 'Okna/Drzwi',
    'appliances' => 'Urządzenia',
    'structure' => 'Konstrukcja',
    'other' => 'Inne'
];

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $property_id = intval($_POST['property_id'] ?? 0);
    $issue_type = trim($_POST['issue_type'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$assignment_id && !$property_id) {
        $errors[] = "Wybierz wynajęte mieszkanie.";
    }
    if (!$issue_type || !isset($issue_types[$issue_type])) {
        $errors[] = "Wybierz typ usterki.";
    }
    if (!$title) {
        $errors[] = "Wpisz tytuł zgłoszenia.";
    }
    if (!$description) {
        $errors[] = "Opisz usterkę.";
    }

    // Sprawdź czy użytkownik wynajmuje to mieszkanie
    if ($assignment_id) {
        $stmt = $pdo->prepare("SELECT property_id FROM assignments WHERE id = :aid AND tenant_id = :uid AND status = 'confirmed' LIMIT 1");
        $stmt->execute(['aid' => $assignment_id, 'uid' => $user_id]);
        $check = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$check) {
            $errors[] = "Nie wynajmujesz tego mieszkania.";
        } else {
            $property_id = $check['property_id'];
        }
    }

    if (empty($errors)) {
        try {
            // Dodaj typ usterki do tytułu
            $full_title = "[" . htmlspecialchars($issue_types[$issue_type]) . "] " . $title;

            $stmt = $pdo->prepare("INSERT INTO maintenance_reports (assignment_id, property_id, reported_by, title, description, status, created_at) VALUES (:aid, :pid, :by, :title, :desc, 'open', NOW())");
            $stmt->execute([
                'aid' => $assignment_id ?: null,
                'pid' => $property_id,
                'by' => $user_id,
                'title' => $full_title,
                'desc' => $description
            ]);

            $report_id = $pdo->lastInsertId();

            // Log aktywności
            require_once 'includes/admin_functions.php';
            admin_log_activity($pdo, $user_id, 'Zgłoszono usterkę', "report_id:{$report_id}, type:{$issue_type}, property_id:{$property_id}");

            $success = "Zgłoszenie usterki zostało utworzone. ID zgłoszenia: " . $report_id;
        } catch (PDOException $e) {
            $errors[] = "Błąd bazy danych: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Pobierz ostatnie zgłoszenia użytkownika
$stmt = $pdo->prepare("SELECT mr.id, mr.title, mr.description, mr.status, mr.created_at, p.title AS property_title
                       FROM maintenance_reports mr
                       LEFT JOIN properties p ON mr.property_id = p.id
                       WHERE mr.reported_by = :uid
                       ORDER BY mr.created_at DESC
                       LIMIT 10");
$stmt->execute(['uid' => $user_id]);
$my_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Zgłoś usterkę — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Zgłoszenie usterki</h2>

  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-info"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>

  <div class="grid" style="grid-template-columns:1fr 1fr;gap:18px">
    <section>
      <div class="panel">
        <h3>Nowe zgłoszenie</h3>
        
        <?php if (empty($rented_properties)): ?>
          <p class="muted">Nie wynajmujesz żadnego mieszkania. Zgłoszenia usterek są dostępne tylko dla wynajętych mieszkań.</p>
          <p><a class="btn" href="user_panel.php">Wróć do panelu</a></p>
        <?php else: ?>
          <form method="post">
            <label>Wynajęte mieszkanie
              <select name="assignment_id" required onchange="this.form.property_id.value = this.options[this.selectedIndex].getAttribute('data-property-id')">
                <option value="">-- Wybierz mieszkanie --</option>
                <?php foreach ($rented_properties as $prop): ?>
                  <option value="<?=intval($prop['assignment_id'])?>" data-property-id="<?=intval($prop['property_id'])?>"><?=htmlspecialchars($prop['title'])?> - <?=htmlspecialchars($prop['city'])?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <input type="hidden" name="property_id" value="">

            <label>Typ usterki
              <select name="issue_type" required>
                <option value="">-- Wybierz typ --</option>
                <?php foreach ($issue_types as $key => $label): ?>
                  <option value="<?=htmlspecialchars($key)?>"><?=htmlspecialchars($label)?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>Tytuł zgłoszenia
              <input type="text" name="title" required placeholder="np. Przeciekający kran w łazience">
            </label>

            <label>Opis usterki
              <textarea name="description" rows="6" required placeholder="Opisz szczegółowo problem..."></textarea>
            </label>

            <div class="form-actions">
              <button class="btn btn-primary" type="submit">Wyślij zgłoszenie</button>
              <a class="btn" href="user_panel.php">Anuluj</a>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </section>

    <section>
      <div class="panel">
        <h3>Twoje zgłoszenia</h3>
        <?php if (!$my_reports): ?>
          <p class="muted">Brak zgłoszeń.</p>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Tytuł</th>
                <th>Status</th>
                <th>Data</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($my_reports as $rep): ?>
                <tr>
                  <td><?=htmlspecialchars($rep['id'])?></td>
                  <td>
                    <?=htmlspecialchars($rep['title'])?>
                    <div class="muted" style="font-size:0.85rem"><?=htmlspecialchars($rep['property_title'])?></div>
                  </td>
                  <td>
                    <?php
                    $status_labels = [
                      'open' => 'Otwarte',
                      'in_progress' => 'W toku',
                      'resolved' => 'Rozwiązane',
                      'closed' => 'Zamknięte'
                    ];
                    echo htmlspecialchars($status_labels[$rep['status']] ?? $rep['status']);
                    ?>
                  </td>
                  <td><?=htmlspecialchars($rep['created_at'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <div class="panel" style="margin-top:18px">
        <h4>Typy usterek</h4>
        <ul class="muted">
          <?php foreach ($issue_types as $label): ?>
            <li><?=htmlspecialchars($label)?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
