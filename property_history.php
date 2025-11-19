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

// Get complete rental history with detailed statistics
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.start_date,
        r.end_date,
        r.price,
        r.created_at,
        u.name AS tenant_name,
        u.email AS tenant_email,
        DATEDIFF(r.end_date, r.start_date) AS rental_days
    FROM rentals r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.property_id = :pid
    ORDER BY r.created_at DESC
");
$stmt->execute(['pid' => $property_id]);
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate detailed statistics
$totalRentals = count($rentals);
$totalRevenue = 0;
$totalDays = 0;
$activeRentals = 0;
$completedRentals = 0;
$upcomingRentals = 0;
$currentDate = date('Y-m-d');

foreach ($rentals as $rental) {
    $totalRevenue += (float)$rental['price'];
    $totalDays += (int)$rental['rental_days'];
    
    if ($rental['start_date'] <= $currentDate && $rental['end_date'] >= $currentDate) {
        $activeRentals++;
    } elseif ($rental['end_date'] < $currentDate) {
        $completedRentals++;
    } elseif ($rental['start_date'] > $currentDate) {
        $upcomingRentals++;
    }
}

$avgPrice = $totalRentals > 0 ? $totalRevenue / $totalRentals : 0;
$avgDays = $totalRentals > 0 ? $totalDays / $totalRentals : 0;

// Group rentals by year-month for chart data
$monthlyData = [];
foreach ($rentals as $rental) {
    $month = date('Y-m', strtotime($rental['created_at']));
    if (!isset($monthlyData[$month])) {
        $monthlyData[$month] = ['count' => 0, 'revenue' => 0];
    }
    $monthlyData[$month]['count']++;
    $monthlyData[$month]['revenue'] += (float)$rental['price'];
}
krsort($monthlyData);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Historia nieruchomości — <?=htmlspecialchars($property['title'])?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Pełna historia: <?=htmlspecialchars($property['title'])?></h2>

  <div class="grid" style="grid-template-columns:1fr 1fr 1fr;gap:12px">
    <div class="panel">
      <h4>Łączne wynajmy</h4>
      <p style="font-size:2rem;font-weight:700;color:var(--accent)"><?=intval($totalRentals)?></p>
    </div>
    <div class="panel">
      <h4>Łączny przychód</h4>
      <p style="font-size:2rem;font-weight:700;color:var(--accent-2)"><?=number_format($totalRevenue, 0, ',', ' ')?> zł</p>
    </div>
    <div class="panel">
      <h4>Średnia cena</h4>
      <p style="font-size:2rem;font-weight:700;color:var(--accent)"><?=number_format($avgPrice, 0, ',', ' ')?> zł</p>
    </div>
  </div>

  <div class="grid" style="grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;margin-top:12px">
    <div class="panel">
      <h4>Aktywne</h4>
      <p style="font-size:1.5rem;font-weight:600"><?=intval($activeRentals)?></p>
    </div>
    <div class="panel">
      <h4>Zakończone</h4>
      <p style="font-size:1.5rem;font-weight:600"><?=intval($completedRentals)?></p>
    </div>
    <div class="panel">
      <h4>Nadchodzące</h4>
      <p style="font-size:1.5rem;font-weight:600"><?=intval($upcomingRentals)?></p>
    </div>
    <div class="panel">
      <h4>Średni czas</h4>
      <p style="font-size:1.5rem;font-weight:600"><?=number_format($avgDays, 0)?> dni</p>
    </div>
  </div>

  <?php if (!empty($monthlyData)): ?>
    <div class="panel" style="margin-top:12px">
      <h3>Statystyki miesięczne</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Miesiąc</th>
            <th>Liczba wynajmów</th>
            <th>Przychód</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($monthlyData as $month => $data): ?>
            <tr>
              <td><?=htmlspecialchars($month)?></td>
              <td><?=htmlspecialchars($data['count'])?></td>
              <td><?=number_format($data['revenue'], 2, ',', ' ')?> zł</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="panel" style="margin-top:12px">
    <h3>Szczegółowa historia wynajmów</h3>
    <?php if (empty($rentals)): ?>
      <p>Brak historii wynajmów dla tej nieruchomości.</p>
    <?php else: ?>
      <div style="overflow-x:auto">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Najemca</th>
              <th>Data rozpoczęcia</th>
              <th>Data zakończenia</th>
              <th>Liczba dni</th>
              <th>Cena</th>
              <th>Status</th>
              <th>Data rezerwacji</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rentals as $rental): 
              $status = 'Zakończony';
              $statusClass = 'muted';
              if ($rental['start_date'] <= $currentDate && $rental['end_date'] >= $currentDate) {
                $status = 'Aktywny';
                $statusClass = '';
              } elseif ($rental['start_date'] > $currentDate) {
                $status = 'Nadchodzący';
                $statusClass = '';
              }
            ?>
              <tr>
                <td><?=htmlspecialchars($rental['id'])?></td>
                <td>
                  <strong><?=htmlspecialchars($rental['tenant_name'] ?? 'Nieznany')?></strong><br>
                  <span class="muted"><?=htmlspecialchars($rental['tenant_email'] ?? '')?></span>
                </td>
                <td><?=htmlspecialchars($rental['start_date'])?></td>
                <td><?=htmlspecialchars($rental['end_date'])?></td>
                <td><?=htmlspecialchars($rental['rental_days'])?> dni</td>
                <td><?=number_format((float)$rental['price'], 2, ',', ' ')?> zł</td>
                <td><span class="<?=$statusClass?>"><?=$status?></span></td>
                <td><?=htmlspecialchars($rental['created_at'])?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="panel" style="margin-top:12px">
    <p>
      <a class="btn" href="property_panel.php?id=<?=urlencode($property_id)?>">Powrót do panelu</a>
      <a class="btn" href="my_properties.php">Moje mieszkania</a>
    </p>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
