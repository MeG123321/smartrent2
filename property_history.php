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
    die('Nie masz uprawnień do przeglądania historii tej nieruchomości.');
}

// Filtry dat
$from = isset($_GET['from']) ? $_GET['from'] : null;
$to = isset($_GET['to']) ? $_GET['to'] : null;

// Budowanie zapytania z filtrami
$where = "r.property_id = :property_id";
$params = ['property_id' => $property_id];

if ($from) {
    $where .= " AND r.created_at >= :from";
    $params['from'] = $from . ' 00:00:00';
}
if ($to) {
    $where .= " AND r.created_at <= :to";
    $params['to'] = $to . ' 23:59:59';
}

// Pobierz wszystkie wynajmy dla tej nieruchomości
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.start_date,
        r.end_date,
        r.price,
        r.created_at,
        u.name AS tenant_name,
        u.email AS tenant_email,
        CASE 
            WHEN r.end_date < CURDATE() THEN 'Zakończony'
            WHEN r.start_date > CURDATE() THEN 'Przyszły'
            ELSE 'Aktywny'
        END AS status
    FROM rentals r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE $where
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statystyki dla wybranego okresu
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_count,
        COALESCE(SUM(price), 0) AS total_revenue,
        COALESCE(AVG(price), 0) AS avg_price,
        COUNT(CASE WHEN end_date >= CURDATE() THEN 1 END) AS active_count,
        COUNT(CASE WHEN end_date < CURDATE() THEN 1 END) AS completed_count
    FROM rentals r
    WHERE $where
");
$stmt->execute($params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Obsługa eksportu CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="historia_wynajmow_' . $property_id . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Historia wynajmów dla: ' . $property['title']]);
    fputcsv($output, []);
    fputcsv($output, ['ID', 'Najemca', 'Email', 'Data rozpoczęcia', 'Data zakończenia', 'Cena', 'Status', 'Data rezerwacji']);
    
    foreach ($rentals as $rental) {
        fputcsv($output, [
            $rental['id'],
            $rental['tenant_name'] ?? '—',
            $rental['tenant_email'] ?? '—',
            $rental['start_date'],
            $rental['end_date'],
            $rental['price'],
            $rental['status'],
            $rental['created_at']
        ]);
    }
    
    fputcsv($output, []);
    fputcsv($output, ['Statystyki']);
    fputcsv($output, ['Łączna liczba wynajmów', $stats['total_count']]);
    fputcsv($output, ['Łączny przychód', number_format((float)$stats['total_revenue'], 2, '.', '')]);
    fputcsv($output, ['Średnia cena wynajmu', number_format((float)$stats['avg_price'], 2, '.', '')]);
    fputcsv($output, ['Wynajmy aktywne', $stats['active_count']]);
    fputcsv($output, ['Wynajmy zakończone', $stats['completed_count']]);
    
    fclose($output);
    exit;
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Historia wynajmów — <?=htmlspecialchars($property['title'])?> — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Historia wynajmów: <?=htmlspecialchars($property['title'])?></h2>
  
  <p style="margin-bottom:16px">
    <a class="btn" href="property_panel.php?id=<?=urlencode($property_id)?>">Powrót do panelu</a>
    <a class="btn" href="my_properties.php">Moje mieszkania</a>
  </p>

  <div class="panel">
    <h3>Filtruj według daty</h3>
    <form method="get" style="display:flex;gap:8px;align-items:center">
      <input type="hidden" name="id" value="<?=htmlspecialchars($property_id)?>">
      <label>Od <input type="date" name="from" value="<?=htmlspecialchars($from ?? '')?>"></label>
      <label>Do <input type="date" name="to" value="<?=htmlspecialchars($to ?? '')?>"></label>
      <button class="btn btn-primary" type="submit">Filtruj</button>
      <a class="btn" href="property_history.php?id=<?=urlencode($property_id)?>">Wyczyść</a>
      <?php if (!empty($rentals)): ?>
        <a class="btn" href="?id=<?=urlencode($property_id)?>&from=<?=urlencode($from ?? '')?>&to=<?=urlencode($to ?? '')?>&export=csv">Eksportuj CSV</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="grid" style="grid-template-columns:repeat(5,1fr);gap:12px;margin:18px 0">
    <div class="panel">
      <h4>Łącznie</h4>
      <p style="font-size:1.4rem;font-weight:700"><?=htmlspecialchars($stats['total_count'])?></p>
      <p class="muted">wynajmów</p>
    </div>
    <div class="panel">
      <h4>Aktywne</h4>
      <p style="font-size:1.4rem;font-weight:700"><?=htmlspecialchars($stats['active_count'])?></p>
      <p class="muted">w trakcie</p>
    </div>
    <div class="panel">
      <h4>Zakończone</h4>
      <p style="font-size:1.4rem;font-weight:700"><?=htmlspecialchars($stats['completed_count'])?></p>
      <p class="muted">zamknięte</p>
    </div>
    <div class="panel">
      <h4>Przychód</h4>
      <p style="font-size:1.4rem;font-weight:700"><?=number_format((float)$stats['total_revenue'], 0, ',', ' ')?> zł</p>
      <p class="muted">łącznie</p>
    </div>
    <div class="panel">
      <h4>Średnia</h4>
      <p style="font-size:1.4rem;font-weight:700"><?=number_format((float)$stats['avg_price'], 0, ',', ' ')?> zł</p>
      <p class="muted">za wynajem</p>
    </div>
  </div>

  <div class="panel">
    <h3>Kompletna historia wynajmów</h3>
    <?php if (empty($rentals)): ?>
      <p>Brak wynajmów dla wybranego okresu.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Najemca</th>
            <th>Email</th>
            <th>Okres wynajmu</th>
            <th>Cena</th>
            <th>Status</th>
            <th>Data rezerwacji</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rentals as $rental): ?>
            <tr>
              <td><?=htmlspecialchars($rental['id'])?></td>
              <td><?=htmlspecialchars($rental['tenant_name'] ?? '—')?></td>
              <td><?=htmlspecialchars($rental['tenant_email'] ?? '—')?></td>
              <td><?=htmlspecialchars($rental['start_date'])?> → <?=htmlspecialchars($rental['end_date'])?></td>
              <td><?=number_format((float)$rental['price'], 2, ',', ' ')?> zł</td>
              <td>
                <?php
                $statusClass = '';
                switch ($rental['status']) {
                    case 'Aktywny':
                        $statusClass = 'badge badge-success';
                        break;
                    case 'Przyszły':
                        $statusClass = 'badge badge-info';
                        break;
                    case 'Zakończony':
                        $statusClass = 'badge badge-secondary';
                        break;
                }
                ?>
                <span class="<?=$statusClass?>"><?=htmlspecialchars($rental['status'])?></span>
              </td>
              <td><?=htmlspecialchars($rental['created_at'])?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
