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
  <title>Pomoc administracyjna — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Pomoc administracyjna</h2>
  <div class="panel">
    <h3>Przewodnik dla administratorów</h3>
    <p>Ta sekcja zawiera szczegółową dokumentację systemu zarządzania SmartRent.</p>
    
    <h4>Zarządzanie użytkownikami</h4>
    <ul class="muted">
      <li>Użyj sekcji "Zarządzaj użytkownikami" aby przeglądać, edytować i usuwać konta</li>
      <li>Możesz zmienić rolę użytkownika (admin/user) poprzez formularz edycji</li>
    </ul>
    
    <h4>Zarządzanie ofertami</h4>
    <ul class="muted">
      <li>Dodawaj nowe oferty przez formularz "Dodaj ofertę"</li>
      <li>Edytuj istniejące oferty z listy właściwości</li>
      <li>Oferty można przypisać najemcom przez system wiadomości</li>
    </ul>
    
    <h4>Raporty i logi</h4>
    <ul class="muted">
      <li>Raporty pozwalają na eksport danych w formacie CSV</li>
      <li>Logi aktywności śledzą wszystkie ważne operacje w systemie</li>
    </ul>
    
    <p><a class="btn" href="admin_panel.php">Wróć do panelu administratora</a></p>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
