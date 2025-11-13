<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_role('admin');
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ustawienia serwisu — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Ustawienia serwisu</h2>
  <div class="panel">
    <p>Ta sekcja jest w trakcie budowy. Tutaj będą dostępne globalne ustawienia aplikacji.</p>
    <p><a class="btn" href="admin_panel.php">Wróć do panelu administratora</a></p>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
