<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/session-init.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_once 'includes/db-queries.php';

// Input
$q = trim((string)($_GET['q'] ?? ''));
$city = trim((string)($_GET['city'] ?? ''));

// Use centralized query function
try {
    $props = get_properties($pdo, $q, $city);
} catch (PDOException $e) {
    http_response_code(500);
    echo "<h2>Błąd serwera</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Oferty — samrtrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Lista mieszkań</h2>

  <form class="filters" method="get" action="property_list.php">
    <input type="search" name="q" placeholder="Szukaj (tytuł, opis)" value="<?=htmlspecialchars($q, ENT_QUOTES)?>">
    <input type="text" name="city" placeholder="Miasto" value="<?=htmlspecialchars($city, ENT_QUOTES)?>">
    <button class="btn" type="submit">Szukaj</button>
    <a class="btn btn-ghost" href="property_list.php">Wyczyść</a>
  </form>

  <?php if (empty($props)): ?>
    <p>Brak ofert do wyświetlenia.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($props as $p): ?>
        <?php
          // Use centralized image helper
          $imgSrcEsc = get_image_url($p['image'] ?? null);
          $title = htmlspecialchars($p['title'] ?? '', ENT_QUOTES);
          $cityOut = htmlspecialchars($p['city'] ?? '', ENT_QUOTES);
          $idOut = urlencode($p['id']);
        ?>
        <article class="card">
          <a href="property_details.php?id=<?=$idOut?>">
            <div class="card-img" style="background-image:url('<?=$imgSrcEsc?>')"></div>
            <div class="card-body">
              <h3><?=htmlspecialchars(shorten($p['title'] ?? ''), ENT_QUOTES)?></h3>
              <p class="muted"><?=$cityOut?></p>
              <div class="price"><?=htmlspecialchars(format_price($p['price']))?> / miesiąc</div>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>