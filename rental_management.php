<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_role('admin');

$errors = [];
$success = '';

// Obsługa formularza wynajmu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_id = intval($_POST['property_id'] ?? 0);
    $tenant_id = intval($_POST['tenant_id'] ?? 0);
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $rent_amount = floatval($_POST['rent_amount'] ?? 0);
    $payment_day = intval($_POST['payment_day'] ?? 1);
    $notes = trim($_POST['notes'] ?? '');

    if (!$property_id) {
        $errors[] = "Wybierz mieszkanie.";
    }
    if (!$tenant_id) {
        $errors[] = "Wybierz najemcę.";
    }
    if (!$start_date) {
        $errors[] = "Podaj datę rozpoczęcia.";
    }
    if ($rent_amount <= 0) {
        $errors[] = "Podaj kwotę czynszu.";
    }

    if (empty($errors)) {
        try {
            // Sprawdź czy mieszkanie nie jest już wynajęte
            $stmt = $pdo->prepare("SELECT id FROM assignments WHERE property_id = :pid AND status = 'confirmed' LIMIT 1");
            $stmt->execute(['pid' => $property_id]);
            if ($stmt->fetchColumn()) {
                $errors[] = "To mieszkanie jest już wynajęte.";
            } else {
                // Utwórz przypisanie
                $stmt = $pdo->prepare("INSERT INTO assignments (property_id, tenant_id, assigned_by, status, start_date, end_date, notes, created_at) VALUES (:pid, :tid, :by, 'confirmed', :sd, :ed, :notes, NOW())");
                $stmt->execute([
                    'pid' => $property_id,
                    'tid' => $tenant_id,
                    'by' => $_SESSION['user_id'],
                    'sd' => $start_date ?: null,
                    'ed' => $end_date ?: null,
                    'notes' => $notes
                ]);
                $assignment_id = $pdo->lastInsertId();

                // Utwórz pierwsze płatności (następne miesiące)
                $due_date = date('Y-m-d', strtotime($start_date . " +1 month"));
                $due_date = date('Y-m', strtotime($due_date)) . '-' . str_pad($payment_day, 2, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare("INSERT INTO payments (assignment_id, due_date, amount, status, created_at) VALUES (:aid, :dd, :amt, 'due', NOW())");
                $stmt->execute([
                    'aid' => $assignment_id,
                    'dd' => $due_date,
                    'amt' => $rent_amount
                ]);

                // Dodaj log aktywności
                admin_log_activity($pdo, $_SESSION['user_id'], 'Wynajęto mieszkanie', "assignment_id:{$assignment_id}, property_id:{$property_id}, tenant_id:{$tenant_id}, rent:{$rent_amount}");

                $success = "Mieszkanie zostało wynajęte. Przypisanie ID: " . $assignment_id;
            }
        } catch (PDOException $e) {
            $errors[] = "Błąd bazy danych: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Pobierz dostępne mieszkania (nie wynajęte)
$stmt = $pdo->query("SELECT p.id, p.title, p.city, p.price, p.owner_id 
                     FROM properties p 
                     WHERE p.id NOT IN (SELECT property_id FROM assignments WHERE status = 'confirmed')
                     ORDER BY p.created_at DESC");
$available_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz użytkowników (potencjalnych najemców)
$stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'user' ORDER BY name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz aktywne wynajmy
$stmt = $pdo->query("SELECT a.id, a.property_id, a.tenant_id, a.start_date, a.end_date, a.created_at, a.status,
                            p.title AS property_title, p.city, u.name AS tenant_name
                     FROM assignments a
                     LEFT JOIN properties p ON a.property_id = p.id
                     LEFT JOIN users u ON a.tenant_id = u.id
                     WHERE a.status = 'confirmed'
                     ORDER BY a.created_at DESC
                     LIMIT 20");
$active_rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Zarządzanie wynajmem — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Zarządzanie wynajmem mieszkań</h2>

  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-info"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>

  <div class="grid" style="grid-template-columns:1fr 1fr;gap:18px">
    <section>
      <div class="panel">
        <h3>Wynajmij mieszkanie</h3>
        <form method="post">
          <label>Mieszkanie
            <select name="property_id" required>
              <option value="">-- Wybierz mieszkanie --</option>
              <?php foreach ($available_properties as $prop): ?>
                <option value="<?=intval($prop['id'])?>"><?=htmlspecialchars($prop['title'])?> - <?=htmlspecialchars($prop['city'])?> (<?=number_format($prop['price'],2,',',' ')?>zł)</option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>Najemca
            <select name="tenant_id" required>
              <option value="">-- Wybierz najemcę --</option>
              <?php foreach ($users as $u): ?>
                <option value="<?=intval($u['id'])?>"><?=htmlspecialchars($u['name'])?> (<?=htmlspecialchars($u['email'])?>)</option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>Data rozpoczęcia
            <input type="date" name="start_date" required>
          </label>

          <label>Data zakończenia (opcjonalnie)
            <input type="date" name="end_date">
          </label>

          <label>Wysokość czynszu (PLN)
            <input type="number" step="0.01" name="rent_amount" required>
          </label>

          <label>Dzień płatności (1-31)
            <input type="number" name="payment_day" value="1" min="1" max="31" required>
          </label>

          <label>Notatki
            <textarea name="notes" rows="3"></textarea>
          </label>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Wynajmij mieszkanie</button>
            <a class="btn" href="admin_panel.php">Anuluj</a>
          </div>
        </form>
      </div>
    </section>

    <section>
      <div class="panel">
        <h3>Aktywne wynajmy</h3>
        <?php if (!$active_rentals): ?>
          <p class="muted">Brak aktywnych wynajmów.</p>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Mieszkanie</th>
                <th>Najemca</th>
                <th>Od</th>
                <th>Akcje</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($active_rentals as $r): ?>
                <tr>
                  <td><?=htmlspecialchars($r['id'])?></td>
                  <td><?=htmlspecialchars($r['property_title'])?></td>
                  <td><?=htmlspecialchars($r['tenant_name'])?></td>
                  <td><?=htmlspecialchars($r['start_date'] ?? '—')?></td>
                  <td><a class="btn" href="management_assignment.php?id=<?=intval($r['id'])?>">Szczegóły</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <div class="panel" style="margin-top:18px">
    <h3>Informacje</h3>
    <p class="muted">System wynajmu pozwala na:</p>
    <ul class="muted">
      <li>Przypisanie mieszkania do najemcy</li>
      <li>Określenie terminu płatności (dzień miesiąca)</li>
      <li>Ustalenie wysokości czynszu</li>
      <li>Po wynajęciu mieszkanie znika z ofert dostępnych</li>
      <li>Mieszkania wynajęte są dostępne w archiwum przypisań</li>
    </ul>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
