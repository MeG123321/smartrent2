<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];
$property_id = (int)($_GET['id'] ?? 0);

if (!$property_id) {
    header('Location: my_properties.php');
    exit;
}

// Verify property ownership
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :pid AND owner_id = :uid");
$stmt->execute(['pid' => $property_id, 'uid' => $user_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    die('Nie masz uprawnień do tej nieruchomości lub nie istnieje.');
}

// Get rental history for this property
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
    WHERE r.property_id = :pid
    ORDER BY r.created_at DESC
");
$stmt->execute(['pid' => $property_id]);
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalRentals = count($rentals);
$totalRevenue = 0;
foreach ($rentals as $rental) {
    $totalRevenue += (float)$rental['price'];
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel nieruchomości — <?=htmlspecialchars($property['title'])?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Panel zarządzania: <?=htmlspecialchars($property['title'])?></h2>

  <div class="grid" style="grid-template-columns:1fr 320px;gap:18px">
    <section>
      <div class="panel">
        <h3>Informacje o nieruchomości</h3>
        <p><strong>ID:</strong> <?=htmlspecialchars($property['id'])?></p>
        <p><strong>Tytuł:</strong> <?=htmlspecialchars($property['title'])?></p>
        <p><strong>Miasto:</strong> <?=htmlspecialchars($property['city'])?></p>
        <p><strong>Cena:</strong> <?=number_format((float)$property['price'], 2, ',', ' ')?> zł / miesiąc</p>
        <p><strong>Data dodania:</strong> <?=htmlspecialchars($property['created_at'])?></p>
        <p>
          <a class="btn" href="edit_property.php?id=<?=urlencode($property['id'])?>">Edytuj</a>
          <a class="btn" href="property_details.php?id=<?=urlencode($property['id'])?>">Zobacz szczegóły</a>
          <a class="btn" href="my_properties.php">Powrót do listy</a>
        </p>
      </div>

      <h3 style="margin-top:12px">Historia wynajmów</h3>
      <div class="panel">
        <?php if (empty($rentals)): ?>
          <p>Brak historii wynajmów dla tej nieruchomości.</p>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Najemca</th>
                <th>Okres</th>
                <th>Cena</th>
                <th>Data rezerwacji</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rentals as $rental): ?>
                <tr>
                  <td><?=htmlspecialchars($rental['id'])?></td>
                  <td>
                    <?=htmlspecialchars($rental['tenant_name'] ?? 'Nieznany')?><br>
                    <span class="muted"><?=htmlspecialchars($rental['tenant_email'] ?? '')?></span>
                  </td>
                  <td><?=htmlspecialchars($rental['start_date'])?> → <?=htmlspecialchars($rental['end_date'])?></td>
                  <td><?=number_format((float)$rental['price'], 2, ',', ' ')?> zł</td>
                  <td><?=htmlspecialchars($rental['created_at'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p style="margin-top:12px">
            <a class="btn" href="property_history.php?id=<?=urlencode($property_id)?>">Zobacz pełną historię</a>
          </p>
        <?php endif; ?>
      </div>
    </section>

    <aside>
      <div class="panel">
        <h4>Statystyki</h4>
        <p>Liczba wynajmów: <strong><?=intval($totalRentals)?></strong></p>
        <p>Łączny przychód: <strong><?=number_format($totalRevenue, 2, ',', ' ')?> zł</strong></p>
        <?php if ($totalRentals > 0): ?>
          <p>Średnia cena: <strong><?=number_format($totalRevenue / $totalRentals, 2, ',', ' ')?> zł</strong></p>
        <?php endif; ?>
      </div>

      <div class="panel" style="margin-top:12px">
        <h4>Akcje</h4>
        <p><a class="btn" href="assign_property.php?id=<?=urlencode($property_id)?>">Przypisz nieruchomość</a></p>
        <p><a class="btn" href="delete_property.php?id=<?=urlencode($property_id)?>" onclick="return confirm('Czy na pewno chcesz usunąć tę nieruchomość?')">Usuń nieruchomość</a></p>
      </div>
    </aside>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
