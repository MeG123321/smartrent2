<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_role('admin');

// Get all properties with owner information
$sql = "SELECT p.id, p.title, p.city, p.price, p.image, p.created_at, u.name as owner_name, u.email as owner_email 
        FROM properties p 
        LEFT JOIN users u ON p.owner_id = u.id 
        ORDER BY p.created_at DESC";
$stmt = $pdo->query($sql);
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
  <h2>Zarządzanie ofertami mieszkań</h2>
  
  <div class="panel">
    <div style="margin-bottom:16px">
      <a class="btn btn-primary" href="add_property.php">Dodaj nową ofertę</a>
      <a class="btn" href="admin_panel.php">Powrót do panelu</a>
    </div>

    <?php if (empty($properties)): ?>
      <p>Brak ofert w bazie danych.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
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
              <td>
                <a href="property_details.php?id=<?=urlencode($p['id'])?>">
                  <?=htmlspecialchars($p['title'])?>
                </a>
              </td>
              <td><?=htmlspecialchars($p['city'])?></td>
              <td><?=number_format((float)$p['price'], 2, ',', ' ')?> zł</td>
              <td>
                <?php if ($p['owner_name']): ?>
                  <?=htmlspecialchars($p['owner_name'])?><br>
                  <span class="muted"><?=htmlspecialchars($p['owner_email'])?></span>
                <?php else: ?>
                  <span class="muted">Brak właściciela</span>
                <?php endif; ?>
              </td>
              <td><?=htmlspecialchars($p['created_at'])?></td>
              <td>
                <a class="btn btn-small" href="edit_property.php?id=<?=urlencode($p['id'])?>">Edytuj</a>
                <form method="post" action="delete_property.php" style="display:inline" 
                      onsubmit="return confirm('Czy na pewno chcesz usunąć tę ofertę?');">
                  <input type="hidden" name="id" value="<?=htmlspecialchars($p['id'])?>">
                  <button class="btn btn-small btn-danger" type="submit">Usuń</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
