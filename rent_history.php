<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/session-init.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_once 'includes/db-queries.php';
require_login();

$user_id = $_SESSION['user_id'];
$rents = get_user_rentals($pdo, $user_id);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Historia wynajmów — samrtrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Historia wynajmów</h2>

  <?php if (!$rents): ?>
    <div class="panel">Brak historii wynajmów.</div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>#</th><th>Mieszkanie</th><th>Okres</th><th>Cena</th><th>Data rezerwacji</th></tr></thead>
      <tbody>
      <?php foreach ($rents as $r): ?>
        <tr>
          <td><?=htmlspecialchars($r['id'])?></td>
          <td><?=htmlspecialchars($r['title'].' — '.$r['city'])?></td>
          <td><?=htmlspecialchars($r['start_date'])?> → <?=htmlspecialchars($r['end_date'])?></td>
          <td><?=format_price($r['price'])?></td>
          <td><?=htmlspecialchars($r['created_at'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>