<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Obsługa formularza wynajmu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rent_property'])) {
    $property_id = intval($_POST['property_id'] ?? 0);
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $payment_due_date = trim($_POST['payment_due_date'] ?? '');

    if ($property_id <= 0) {
        $errors[] = "Wybierz mieszkanie.";
    }
    if (empty($start_date)) {
        $errors[] = "Podaj datę rozpoczęcia.";
    }
    if (empty($end_date)) {
        $errors[] = "Podaj datę zakończenia.";
    }
    if ($price <= 0) {
        $errors[] = "Cena musi być większa od 0.";
    }
    if ($start_date && $end_date && $start_date >= $end_date) {
        $errors[] = "Data zakończenia musi być późniejsza niż data rozpoczęcia.";
    }

    if (empty($errors)) {
        try {
            // Sprawdź, czy nieruchomość istnieje
            $stmt = $pdo->prepare("SELECT id, title FROM properties WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $property_id]);
            $property = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$property) {
                $errors[] = "Wybrana nieruchomość nie istnieje.";
            } else {
                // Dodaj wynajem
                $stmt = $pdo->prepare("INSERT INTO rentals (user_id, property_id, start_date, end_date, price, created_at) VALUES (:uid, :pid, :start, :end, :price, NOW())");
                $stmt->execute([
                    'uid' => $user_id,
                    'pid' => $property_id,
                    'start' => $start_date,
                    'end' => $end_date,
                    'price' => $price
                ]);

                $rental_id = (int)$pdo->lastInsertId();

                // Log aktywności
                admin_log_activity($pdo, $user_id, 'Utworzono wynajem', "rental_id:{$rental_id}, property_id:{$property_id}, start:{$start_date}, end:{$end_date}");

                $success = "Wynajem został utworzony pomyślnie!";
            }
        } catch (PDOException $e) {
            $errors[] = "Błąd bazy danych: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Pobierz dostępne nieruchomości
$stmt = $pdo->query("SELECT id, title, city, price FROM properties ORDER BY created_at DESC");
$available_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz wynajmy użytkownika
$stmt = $pdo->prepare("SELECT r.id, r.property_id, r.start_date, r.end_date, r.price, r.created_at, p.title, p.city 
                       FROM rentals r 
                       LEFT JOIN properties p ON r.property_id = p.id 
                       WHERE r.user_id = :uid 
                       ORDER BY r.created_at DESC");
$stmt->execute(['uid' => $user_id]);
$user_rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Zarządzanie wynajmem — <?=htmlspecialchars(APP_NAME)?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Zarządzanie wynajmem</h2>

  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-info"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>

  <div class="panel">
    <h3>Wynajmij mieszkanie</h3>
    <form method="post">
      <label>Wybierz mieszkanie
        <select name="property_id" required>
          <option value="">-- Wybierz --</option>
          <?php foreach ($available_properties as $prop): ?>
            <option value="<?=intval($prop['id'])?>" data-price="<?=floatval($prop['price'])?>">
              <?=htmlspecialchars($prop['title'])?> - <?=htmlspecialchars($prop['city'])?> (<?=number_format(floatval($prop['price']), 2, ',', ' ')?> zł)
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Data rozpoczęcia
        <input type="date" name="start_date" required min="<?=date('Y-m-d')?>">
      </label>

      <label>Data zakończenia
        <input type="date" name="end_date" required min="<?=date('Y-m-d')?>">
      </label>

      <label>Cena wynajmu (PLN)
        <input type="number" step="0.01" name="price" required min="0.01" placeholder="Cena">
      </label>

      <label>Data płatności
        <input type="date" name="payment_due_date" required>
      </label>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit" name="rent_property">Wynajmij</button>
        <a class="btn" href="user_panel.php">Powrót</a>
      </div>
    </form>
  </div>

  <h3 style="margin-top:24px">Moje wynajmy</h3>
  <div class="panel">
    <?php if (empty($user_rentals)): ?>
      <p>Nie masz jeszcze żadnych wynajmów.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Mieszkanie</th>
            <th>Miasto</th>
            <th>Okres</th>
            <th>Cena</th>
            <th>Data utworzenia</th>
            <th>Akcje</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($user_rentals as $rental): ?>
            <tr>
              <td><?=intval($rental['id'])?></td>
              <td><?=htmlspecialchars($rental['title'] ?? '—')?></td>
              <td><?=htmlspecialchars($rental['city'] ?? '—')?></td>
              <td><?=htmlspecialchars($rental['start_date'])?> → <?=htmlspecialchars($rental['end_date'])?></td>
              <td><?=number_format(floatval($rental['price']), 2, ',', ' ')?> zł</td>
              <td><?=htmlspecialchars($rental['created_at'])?></td>
              <td>
                <a class="btn" href="maintenance_request.php?rental_id=<?=intval($rental['id'])?>">Zgłoś usterkę</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
<script>
// Automatycznie uzupełnij cenę po wybraniu nieruchomości
document.addEventListener('DOMContentLoaded', function() {
    const propertySelect = document.querySelector('select[name="property_id"]');
    const priceInput = document.querySelector('input[name="price"]');
    
    if (propertySelect && priceInput) {
        propertySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            if (price) {
                priceInput.value = parseFloat(price).toFixed(2);
            }
        });
    }
});
</script>
</body>
</html>
