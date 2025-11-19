<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_role('admin');

// Pobierz wszystkie oferty
$stmt = $pdo->query("SELECT p.id, p.title, p.city, p.price, p.image, p.created_at, p.owner_id, u.name as owner_name 
                     FROM properties p 
                     LEFT JOIN users u ON p.owner_id = u.id 
                     ORDER BY p.created_at DESC");
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Zarządzanie ofertami — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Zarządzanie ofertami</h2>
  
  <div class="panel" style="margin-bottom:18px">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <p class="muted">Łącznie ofert: <strong><?=count($properties)?></strong></p>
      <a class="btn btn-primary" href="add_property.php">Dodaj nową ofertę</a>
    </div>
  </div>
  
  <?php if (empty($properties)): ?>
    <div class="panel">
      <p>Brak ofert w systemie.</p>
    </div>
  <?php else: ?>
    <div class="panel">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tytuł</th>
            <th>Miasto</th>
            <th>Cena</th>
            <th>Właściciel</th>
            <th>Data dodania</th>
            <th>Akcje</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($properties as $p): ?>
            <tr>
              <td><?=htmlspecialchars($p['id'])?></td>
              <td><?=htmlspecialchars($p['title'])?></td>
              <td><?=htmlspecialchars($p['city'])?></td>
              <td><?=number_format((float)$p['price'], 2, ',', ' ')?> zł</td>
              <td><?=htmlspecialchars($p['owner_name'] ?? 'Brak')?></td>
              <td><?=htmlspecialchars($p['created_at'])?></td>
              <td>
                <a class="btn" href="edit_property.php?id=<?=intval($p['id'])?>" style="margin-right:4px">Edytuj</a>
                <a class="btn btn-secondary" href="delete_property.php?id=<?=intval($p['id'])?>" onclick="return confirm('Czy na pewno chcesz usunąć tę ofertę?')">Usuń</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  
  <div class="form-actions" style="margin-top:18px">
    <a class="btn" href="admin_panel.php">Powrót do panelu</a>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
