<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_role('admin');

$errors = [];
$success = '';

// Pobierz dostępne mieszkania (nie wynajęte)
$stmt = $pdo->query("SELECT p.id, p.title, p.city, p.price, p.owner_id, u.name as owner_name 
                     FROM properties p 
                     LEFT JOIN users u ON p.owner_id = u.id 
                     WHERE p.id NOT IN (SELECT property_id FROM rentals WHERE end_date >= CURDATE())
                     ORDER BY p.created_at DESC");
$available_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz użytkowników (najemców)
$stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'user' ORDER BY name ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz aktualne wynajmy
$stmt = $pdo->query("SELECT r.id, r.start_date, r.end_date, r.price, r.created_at,
                            p.title as property_title, p.city,
                            u.name as tenant_name, u.email as tenant_email
                     FROM rentals r
                     JOIN properties p ON r.property_id = p.id
                     JOIN users u ON r.user_id = u.id
                     WHERE r.end_date >= CURDATE()
                     ORDER BY r.created_at DESC");
$active_rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_rental') {
        $property_id = intval($_POST['property_id'] ?? 0);
        $user_id = intval($_POST['user_id'] ?? 0);
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $rental_price = floatval($_POST['rental_price'] ?? 0);
        $payment_day = intval($_POST['payment_day'] ?? 1);
        
        // Walidacja
        if (!$property_id || !$user_id) {
            $errors[] = "Wybierz mieszkanie i najemcę.";
        } elseif (empty($start_date) || empty($end_date)) {
            $errors[] = "Podaj daty rozpoczęcia i zakończenia wynajmu.";
        } elseif ($rental_price <= 0) {
            $errors[] = "Cena wynajmu musi być większa od zera.";
        } elseif (strtotime($start_date) >= strtotime($end_date)) {
            $errors[] = "Data zakończenia musi być późniejsza niż data rozpoczęcia.";
        } elseif ($payment_day < 1 || $payment_day > 31) {
            $errors[] = "Dzień płatności musi być między 1 a 31.";
        } else {
            // Sprawdź czy mieszkanie jest dostępne
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals 
                                  WHERE property_id = :pid 
                                  AND end_date >= CURDATE()");
            $stmt->execute(['pid' => $property_id]);
            $is_rented = (int)$stmt->fetchColumn();
            
            if ($is_rented > 0) {
                $errors[] = "To mieszkanie jest już wynajęte.";
            } else {
                // Utwórz wynajem
                $stmt = $pdo->prepare("INSERT INTO rentals (user_id, property_id, start_date, end_date, price, created_at) 
                                      VALUES (:uid, :pid, :start, :end, :price, NOW())");
                $stmt->execute([
                    'uid' => $user_id,
                    'pid' => $property_id,
                    'start' => $start_date,
                    'end' => $end_date,
                    'price' => $rental_price
                ]);
                
                $rental_id = (int)$pdo->lastInsertId();
                
                // Pobierz nazwę mieszkania dla logu
                $stmt = $pdo->prepare("SELECT title FROM properties WHERE id = :id");
                $stmt->execute(['id' => $property_id]);
                $property_title = $stmt->fetchColumn();
                
                admin_log_activity($pdo, $_SESSION['user_id'], 'Utworzono wynajem', 
                                  "rental_id: {$rental_id}, property: {$property_title}, tenant_id: {$user_id}, price: {$rental_price}");
                
                $success = "Wynajem został utworzony pomyślnie. Mieszkanie zniknęło z listy dostępnych.";
                
                // Odśwież listę dostępnych mieszkań
                $stmt = $pdo->query("SELECT p.id, p.title, p.city, p.price, p.owner_id, u.name as owner_name 
                                    FROM properties p 
                                    LEFT JOIN users u ON p.owner_id = u.id 
                                    WHERE p.id NOT IN (SELECT property_id FROM rentals WHERE end_date >= CURDATE())
                                    ORDER BY p.created_at DESC");
                $available_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Odśwież listę wynajmów
                $stmt = $pdo->query("SELECT r.id, r.start_date, r.end_date, r.price, r.created_at,
                                            p.title as property_title, p.city,
                                            u.name as tenant_name, u.email as tenant_email
                                    FROM rentals r
                                    JOIN properties p ON r.property_id = p.id
                                    JOIN users u ON r.user_id = u.id
                                    WHERE r.end_date >= CURDATE()
                                    ORDER BY r.created_at DESC");
                $active_rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Zarządzanie wynajmami — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Zarządzanie wynajmami</h2>
  
  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  
  <?php if ($success): ?>
    <div class="alert alert-info"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>
  
  <div class="panel">
    <h3>Utwórz nowy wynajem</h3>
    <form method="post">
      <input type="hidden" name="action" value="create_rental">
      
      <label>Wybierz mieszkanie
        <select name="property_id" required style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,0.04);background:transparent;color:var(--text)">
          <option value="">-- Wybierz mieszkanie --</option>
          <?php foreach ($available_properties as $prop): ?>
            <option value="<?=intval($prop['id'])?>">
              <?=htmlspecialchars($prop['title'])?> - <?=htmlspecialchars($prop['city'])?> 
              (<?=number_format((float)$prop['price'], 0, ',', ' ')?> zł/mies.)
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      
      <label>Wybierz najemcę
        <select name="user_id" required style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,0.04);background:transparent;color:var(--text)">
          <option value="">-- Wybierz najemcę --</option>
          <?php foreach ($users as $user): ?>
            <option value="<?=intval($user['id'])?>">
              <?=htmlspecialchars($user['name'])?> (<?=htmlspecialchars($user['email'])?>)
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <label>Data rozpoczęcia
          <input type="date" name="start_date" required>
        </label>
        
        <label>Data zakończenia
          <input type="date" name="end_date" required>
        </label>
      </div>
      
      <label>Cena wynajmu (PLN/miesiąc)
        <input type="number" step="0.01" name="rental_price" required min="0.01">
      </label>
      
      <label>Dzień płatności w miesiącu (1-31)
        <input type="number" name="payment_day" value="1" required min="1" max="31">
        <span class="muted" style="font-size:0.9rem">Dzień miesiąca, w którym przypada termin płatności</span>
      </label>
      
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Utwórz wynajem</button>
      </div>
    </form>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Aktywne wynajmy</h3>
    <?php if (empty($active_rentals)): ?>
      <p class="muted">Brak aktywnych wynajmów.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Mieszkanie</th>
            <th>Najemca</th>
            <th>Okres</th>
            <th>Cena</th>
            <th>Utworzono</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($active_rentals as $r): ?>
            <tr>
              <td><?=htmlspecialchars($r['id'])?></td>
              <td><?=htmlspecialchars($r['property_title'])?> (<?=htmlspecialchars($r['city'])?>)</td>
              <td><?=htmlspecialchars($r['tenant_name'])?><br><span class="muted"><?=htmlspecialchars($r['tenant_email'])?></span></td>
              <td><?=htmlspecialchars($r['start_date'])?> → <?=htmlspecialchars($r['end_date'])?></td>
              <td><?=number_format((float)$r['price'], 2, ',', ' ')?> zł</td>
              <td><?=htmlspecialchars($r['created_at'])?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Zgłoszenia usterek</h3>
    <p class="muted">Najemcy mogą zgłaszać usterki przez system zgłoszeń support.</p>
    <p><a class="btn" href="admin_tickets.php">Przejdź do zgłoszeń</a></p>
  </div>
  
  <div class="form-actions" style="margin-top:18px">
    <a class="btn" href="admin_panel.php">Powrót do panelu</a>
    <a class="btn" href="rent_history.php">Historia wszystkich wynajmów</a>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
