<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];

// Fetch user's properties with rental statistics
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.title,
        p.city,
        p.price,
        p.description,
        p.created_at,
        COUNT(r.id) AS rentals_count,
        COALESCE(SUM(r.price), 0) AS total_revenue
    FROM properties p
    LEFT JOIN rentals r ON p.id = r.property_id
    WHERE p.owner_id = :uid
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute(['uid' => $user_id]);
$myProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalProperties = count($myProperties);
$totalRevenue = 0;
$totalRentals = 0;
foreach ($myProperties as $prop) {
    $totalRevenue += (float)$prop['total_revenue'];
    $totalRentals += (int)$prop['rentals_count'];
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Moje mieszkania — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Moje mieszkania</h2>

  <div class="panel">
    <h3>Podsumowanie</h3>
    <p>Liczba mieszkań: <strong><?=intval($totalProperties)?></strong></p>
    <p>Łączna liczba wynajmów: <strong><?=intval($totalRentals)?></strong></p>
    <p>Łączny przychód: <strong><?=number_format($totalRevenue, 2, ',', ' ')?> zł</strong></p>
    <p><a class="btn btn-primary" href="add_property.php">Dodaj nową nieruchomość</a></p>
  </div>

  <?php if (empty($myProperties)): ?>
    <div class="panel" style="margin-top:12px">
      <p>Nie masz jeszcze żadnych mieszkań. <a href="add_property.php">Dodaj pierwszą nieruchomość</a></p>
    </div>
  <?php else: ?>
    <div class="panel" style="margin-top:12px;overflow-x:auto">
      <h3>Lista moich mieszkań</h3>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tytuł</th>
            <th>Miasto</th>
            <th>Cena</th>
            <th>Liczba wynajmów</th>
            <th>Przychód</th>
            <th>Data dodania</th>
            <th>Akcje</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($myProperties as $prop): ?>
            <tr>
              <td><?=htmlspecialchars($prop['id'])?></td>
              <td><?=htmlspecialchars($prop['title'])?></td>
              <td><?=htmlspecialchars($prop['city'])?></td>
              <td><?=number_format((float)$prop['price'], 2, ',', ' ')?> zł</td>
              <td><?=htmlspecialchars($prop['rentals_count'])?></td>
              <td><?=number_format((float)$prop['total_revenue'], 2, ',', ' ')?> zł</td>
              <td><?=htmlspecialchars($prop['created_at'])?></td>
              <td>
                <a class="btn btn-sm" href="property_panel.php?id=<?=urlencode($prop['id'])?>">Panel</a>
                <a class="btn btn-sm" href="edit_property.php?id=<?=urlencode($prop['id'])?>">Edytuj</a>
                <a class="btn btn-sm" href="property_details.php?id=<?=urlencode($prop['id'])?>">Zobacz</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
