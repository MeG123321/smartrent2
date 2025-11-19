<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];

// Pobierz wszystkie nieruchomości użytkownika z liczbą wynajmów i przychodem
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.title,
        p.city,
        p.price,
        p.created_at,
        COUNT(r.id) AS rental_count,
        COALESCE(SUM(r.price), 0) AS total_revenue
    FROM properties p
    LEFT JOIN rentals r ON p.id = r.property_id
    WHERE p.owner_id = :user_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute(['user_id' => $user_id]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
  
  <p style="margin-bottom:16px">
    <a class="btn btn-primary" href="add_property.php">Dodaj nowe mieszkanie</a>
    <a class="btn" href="user_panel.php">Powrót do panelu</a>
  </p>

  <?php if (empty($properties)): ?>
    <div class="panel">
      <p>Nie masz jeszcze żadnych nieruchomości.</p>
      <p><a class="btn btn-primary" href="add_property.php">Dodaj swoją pierwszą ofertę</a></p>
    </div>
  <?php else: ?>
    <div class="panel">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Tytuł</th>
            <th>Miasto</th>
            <th>Cena</th>
            <th>Wynajmy</th>
            <th>Przychód</th>
            <th>Akcje</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($properties as $prop): ?>
            <tr>
              <td><?=htmlspecialchars($prop['id'])?></td>
              <td><?=htmlspecialchars($prop['title'])?></td>
              <td><?=htmlspecialchars($prop['city'])?></td>
              <td><?=format_price($prop['price'])?></td>
              <td><?=htmlspecialchars($prop['rental_count'])?></td>
              <td><?=number_format((float)$prop['total_revenue'], 2, ',', ' ')?> zł</td>
              <td>
                <a class="btn" href="property_panel.php?id=<?=urlencode($prop['id'])?>">Zarządzaj</a>
                <a class="btn" href="property_details.php?id=<?=urlencode($prop['id'])?>">Podgląd</a>
                <a class="btn" href="edit_property.php?id=<?=urlencode($prop['id'])?>">Edytuj</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="panel" style="margin-top:16px">
      <h3>Podsumowanie</h3>
      <p>Łączna liczba nieruchomości: <strong><?=count($properties)?></strong></p>
      <p>Łączna liczba wynajmów: <strong><?=array_sum(array_column($properties, 'rental_count'))?></strong></p>
      <p>Łączny przychód: <strong><?=number_format(array_sum(array_column($properties, 'total_revenue')), 2, ',', ' ')?> zł</strong></p>
    </div>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
