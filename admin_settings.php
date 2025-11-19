<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_role('admin');

$errors = [];
$success = '';

// Pobierz ustawienia
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// Domyślne wartości
$defaults = [
    'site_name' => APP_NAME,
    'site_description' => 'System zarządzania wynajmem mieszkań',
    'contact_email' => 'kontakt@smartrent.pl',
    'contact_phone' => '+48 123 456 789',
    'address' => 'ul. Przykładowa 1, 00-000 Warszawa'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');
    $site_description = trim($_POST['site_description'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($site_name)) {
        $errors[] = "Nazwa serwisu jest wymagana.";
    }
    
    if (!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Nieprawidłowy format email kontaktowy.";
    }
    
    if (empty($errors)) {
        $settings_to_save = [
            'site_name' => htmlspecialchars($site_name, ENT_QUOTES),
            'site_description' => htmlspecialchars($site_description, ENT_QUOTES),
            'contact_email' => htmlspecialchars($contact_email, ENT_QUOTES),
            'contact_phone' => htmlspecialchars($contact_phone, ENT_QUOTES),
            'address' => htmlspecialchars($address, ENT_QUOTES)
        ];
        
        foreach ($settings_to_save as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, updated_at) 
                                   VALUES (:key, :value, NOW()) 
                                   ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = NOW()");
            $stmt->execute(['key' => $key, 'value' => $value, 'value2' => $value]);
        }
        
        admin_log_activity($pdo, $_SESSION['user_id'], 'Aktualizacja ustawień serwisu', "settings updated");
        $settings = $settings_to_save;
        $success = "Ustawienia zostały zapisane pomyślnie.";
    }
}
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
<main class="container narrow">
  <h2>Ustawienia serwisu</h2>
  
  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  
  <?php if ($success): ?>
    <div class="alert alert-info"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>
  
  <div class="panel">
    <h3>Podstawowe informacje</h3>
    <form method="post">
      <label>Nazwa serwisu
        <input type="text" name="site_name" value="<?=htmlspecialchars($settings['site_name'] ?? '', ENT_QUOTES)?>" required>
      </label>
      
      <label>Opis serwisu
        <textarea name="site_description" rows="4"><?=htmlspecialchars($settings['site_description'] ?? '', ENT_QUOTES)?></textarea>
      </label>
      
      <label>Email kontaktowy
        <input type="email" name="contact_email" value="<?=htmlspecialchars($settings['contact_email'] ?? '', ENT_QUOTES)?>">
      </label>
      
      <label>Telefon kontaktowy
        <input type="text" name="contact_phone" value="<?=htmlspecialchars($settings['contact_phone'] ?? '', ENT_QUOTES)?>">
      </label>
      
      <label>Adres
        <textarea name="address" rows="2"><?=htmlspecialchars($settings['address'] ?? '', ENT_QUOTES)?></textarea>
      </label>
      
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Zapisz ustawienia</button>
        <a class="btn" href="admin_panel.php">Anuluj</a>
      </div>
    </form>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
