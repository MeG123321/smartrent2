<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];
$rental_id = intval($_GET['rental_id'] ?? 0);
$errors = [];
$success = '';

// Pobierz informacje o wynajmie jeśli podano
$rental_info = null;
$property_id = null;
if ($rental_id > 0) {
    $stmt = $pdo->prepare("SELECT r.*, p.title AS property_title, p.city 
                           FROM rentals r 
                           LEFT JOIN properties p ON r.property_id = p.id 
                           WHERE r.id = :rid AND r.user_id = :uid 
                           LIMIT 1");
    $stmt->execute(['rid' => $rental_id, 'uid' => $user_id]);
    $rental_info = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($rental_info) {
        $property_id = intval($rental_info['property_id']);
    }
}

// Obsługa formularza zgłoszenia usterki
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $issue_type = trim($_POST['issue_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $report_property_id = intval($_POST['property_id'] ?? 0);

    if (empty($issue_type)) {
        $errors[] = "Wybierz rodzaj usterki.";
    }
    if (empty($description)) {
        $errors[] = "Podaj opis usterki.";
    }
    if ($report_property_id <= 0) {
        $errors[] = "Nie można określić nieruchomości.";
    }

    if (empty($errors)) {
        try {
            // Utwórz tytuł na podstawie typu
            $issue_types_pl = [
                'hydraulika' => 'Usterka hydrauliczna',
                'elektryka' => 'Usterka elektryczna',
                'ogrzewanie' => 'Problem z ogrzewaniem',
                'klimatyzacja' => 'Problem z klimatyzacją',
                'drzwi_okna' => 'Problem z drzwiami/oknami',
                'meble' => 'Problem z meblami',
                'inne' => 'Inne zgłoszenie'
            ];
            $title = $issue_types_pl[$issue_type] ?? 'Zgłoszenie usterki';

            // Dodaj zgłoszenie do maintenance_reports
            $stmt = $pdo->prepare("INSERT INTO maintenance_reports (property_id, reported_by, title, description, status, created_at) 
                                   VALUES (:pid, :by, :title, :desc, 'open', NOW())");
            $stmt->execute([
                'pid' => $report_property_id,
                'by' => $user_id,
                'title' => $title,
                'desc' => $description
            ]);

            $report_id = (int)$pdo->lastInsertId();

            // Log aktywności
            admin_log_activity($pdo, $user_id, 'Zgłoszono usterkę', "report_id:{$report_id}, property_id:{$report_property_id}, type:{$issue_type}");

            $success = "Zgłoszenie usterki zostało utworzone pomyślnie!";
        } catch (PDOException $e) {
            $errors[] = "Błąd bazy danych: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Pobierz historię zgłoszeń użytkownika
$stmt = $pdo->prepare("SELECT mr.id, mr.title, mr.description, mr.status, mr.created_at, mr.updated_at, p.title AS property_title, p.city 
                       FROM maintenance_reports mr 
                       LEFT JOIN properties p ON mr.property_id = p.id 
                       WHERE mr.reported_by = :uid 
                       ORDER BY mr.created_at DESC");
$stmt->execute(['uid' => $user_id]);
$maintenance_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz dostępne nieruchomości użytkownika (z aktywnych wynajmów)
$stmt = $pdo->prepare("SELECT DISTINCT p.id, p.title, p.city 
                       FROM rentals r 
                       JOIN properties p ON r.property_id = p.id 
                       WHERE r.user_id = :uid 
                       ORDER BY p.title");
$stmt->execute(['uid' => $user_id]);
$user_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Zgłoszenia usterek — <?=htmlspecialchars(APP_NAME)?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Zgłoszenia usterek</h2>

  <?php if ($rental_info): ?>
    <div class="alert alert-info">
      Zgłoszenie dla: <strong><?=htmlspecialchars($rental_info['property_title'])?></strong> w <?=htmlspecialchars($rental_info['city'])?>
    </div>
  <?php endif; ?>

  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-info"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>

  <div class="panel">
    <h3>Zgłoś usterkę</h3>
    <form method="post">
      <?php if ($property_id): ?>
        <input type="hidden" name="property_id" value="<?=intval($property_id)?>">
      <?php else: ?>
        <label>Wybierz nieruchomość
          <select name="property_id" required>
            <option value="">-- Wybierz --</option>
            <?php foreach ($user_properties as $prop): ?>
              <option value="<?=intval($prop['id'])?>">
                <?=htmlspecialchars($prop['title'])?> - <?=htmlspecialchars($prop['city'])?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php endif; ?>

      <label>Rodzaj usterki
        <select name="issue_type" required>
          <option value="">-- Wybierz rodzaj usterki --</option>
          <option value="hydraulika">Hydraulika</option>
          <option value="elektryka">Elektryka</option>
          <option value="ogrzewanie">Ogrzewanie</option>
          <option value="klimatyzacja">Klimatyzacja</option>
          <option value="drzwi_okna">Drzwi/Okna</option>
          <option value="meble">Meble</option>
          <option value="inne">Inne</option>
        </select>
      </label>

      <label>Opis usterki
        <textarea name="description" rows="6" required placeholder="Opisz szczegółowo problem..."></textarea>
      </label>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit" name="submit_request">Wyślij zgłoszenie</button>
        <a class="btn" href="rental_management.php">Powrót</a>
      </div>
    </form>
  </div>

  <h3 style="margin-top:24px">Historia zgłoszeń</h3>
  <div class="panel">
    <?php if (empty($maintenance_history)): ?>
      <p>Nie masz jeszcze żadnych zgłoszeń.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Tytuł</th>
            <th>Nieruchomość</th>
            <th>Status</th>
            <th>Data zgłoszenia</th>
            <th>Ostatnia aktualizacja</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($maintenance_history as $report): 
            $status_class = '';
            switch($report['status']) {
              case 'open': $status_class = 'status-open'; break;
              case 'in_progress': $status_class = 'status-progress'; break;
              case 'resolved': $status_class = 'status-resolved'; break;
              case 'closed': $status_class = 'status-closed'; break;
            }
            $status_labels = [
              'open' => 'Otwarte',
              'in_progress' => 'W trakcie',
              'resolved' => 'Rozwiązane',
              'closed' => 'Zamknięte'
            ];
          ?>
            <tr>
              <td><?=intval($report['id'])?></td>
              <td>
                <strong><?=htmlspecialchars($report['title'])?></strong><br>
                <span class="muted" style="font-size:0.9em"><?=htmlspecialchars(substr($report['description'], 0, 80))?><?=(strlen($report['description']) > 80 ? '...' : '')?></span>
              </td>
              <td><?=htmlspecialchars($report['property_title'] ?? '—')?><br><span class="muted"><?=htmlspecialchars($report['city'] ?? '')?></span></td>
              <td><span class="<?=htmlspecialchars($status_class)?>"><?=htmlspecialchars($status_labels[$report['status']] ?? $report['status'])?></span></td>
              <td><?=htmlspecialchars($report['created_at'])?></td>
              <td><?=htmlspecialchars($report['updated_at'] ?? '—')?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
<style>
.status-open { color: #ff9800; font-weight: 600; }
.status-progress { color: #2196f3; font-weight: 600; }
.status-resolved { color: #4caf50; font-weight: 600; }
.status-closed { color: #9e9e9e; font-weight: 600; }
</style>
</body>
</html>
