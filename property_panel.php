<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$property_id) {
    header('Location: my_properties.php');
    exit;
}

// Weryfikuj, że użytkownik jest właścicielem nieruchomości
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id AND owner_id = :owner_id");
$stmt->execute(['id' => $property_id, 'owner_id' => $user_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    die('Nie masz uprawnień do zarządzania tą nieruchomością.');
}

// Pobierz ostatnie wynajmy dla tej nieruchomości
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.start_date,
        r.end_date,
        r.price,
        r.created_at,
        u.name AS tenant_name,
        u.email AS tenant_email
    FROM rentals r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.property_id = :property_id
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute(['property_id' => $property_id]);
$recent_rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz statystyki
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_rentals,
        COALESCE(SUM(price), 0) AS total_revenue,
        COUNT(CASE WHEN end_date >= CURDATE() THEN 1 END) AS active_rentals
    FROM rentals 
    WHERE property_id = :property_id
");
$stmt->execute(['property_id' => $property_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel zarządzania — <?=htmlspecialchars($property['title'])?> — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Panel zarządzania: <?=htmlspecialchars($property['title'])?></h2>
  
  <p style="margin-bottom:16px">
    <a class="btn" href="my_properties.php">Powrót do moich mieszkań</a>
    <a class="btn" href="property_details.php?id=<?=urlencode($property_id)?>">Podgląd oferty</a>
    <a class="btn" href="edit_property.php?id=<?=urlencode($property_id)?>">Edytuj ofertę</a>
    <a class="btn btn-primary" href="property_history.php?id=<?=urlencode($property_id)?>">Pełna historia</a>
  </p>

  <div class="grid" style="grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:18px">
    <div class="panel">
      <h3>Łączne wynajmy</h3>
      <p style="font-size:1.6rem;font-weight:700"><?=htmlspecialchars($stats['total_rentals'])?></p>
      <p class="muted">Wszystkie rezerwacje</p>
    </div>
    <div class="panel">
      <h3>Aktywne wynajmy</h3>
      <p style="font-size:1.6rem;font-weight:700"><?=htmlspecialchars($stats['active_rentals'])?></p>
      <p class="muted">Obecne rezerwacje</p>
    </div>
    <div class="panel">
      <h3>Łączny przychód</h3>
      <p style="font-size:1.6rem;font-weight:700"><?=number_format((float)$stats['total_revenue'], 2, ',', ' ')?> zł</p>
      <p class="muted">Z wszystkich wynajmów</p>
    </div>
  </div>

  <div class="panel">
    <h3>Szczegóły nieruchomości</h3>
    <table class="table">
      <tr>
        <td><strong>Tytuł:</strong></td>
        <td><?=htmlspecialchars($property['title'])?></td>
      </tr>
      <tr>
        <td><strong>Miasto:</strong></td>
        <td><?=htmlspecialchars($property['city'])?></td>
      </tr>
      <tr>
        <td><strong>Cena miesięczna:</strong></td>
        <td><?=format_price($property['price'])?></td>
      </tr>
      <tr>
        <td><strong>Data dodania:</strong></td>
        <td><?=htmlspecialchars($property['created_at'])?></td>
      </tr>
    </table>
  </div>

  <div class="panel" style="margin-top:16px">
    <h3>Ostatnie wynajmy (10 najnowszych)</h3>
    <?php if (empty($recent_rentals)): ?>
      <p>Brak wynajmów dla tej nieruchomości.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Najemca</th>
            <th>Email</th>
            <th>Okres</th>
            <th>Cena</th>
            <th>Data rezerwacji</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_rentals as $rental): ?>
            <tr>
              <td><?=htmlspecialchars($rental['id'])?></td>
              <td><?=htmlspecialchars($rental['tenant_name'] ?? '—')?></td>
              <td><?=htmlspecialchars($rental['tenant_email'] ?? '—')?></td>
              <td><?=htmlspecialchars($rental['start_date'])?> → <?=htmlspecialchars($rental['end_date'])?></td>
              <td><?=number_format((float)$rental['price'], 2, ',', ' ')?> zł</td>
              <td><?=htmlspecialchars($rental['created_at'])?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="center" style="margin-top:12px">
        <a class="btn" href="property_history.php?id=<?=urlencode($property_id)?>">Zobacz pełną historię</a>
      </p>
    <?php endif; ?>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
