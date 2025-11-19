<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Pobierz dane użytkownika
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_password') {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Walidacja
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $errors[] = "Wszystkie pola hasła są wymagane.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Nowe hasła nie są identyczne.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Nowe hasło musi mieć minimum 6 znaków.";
        } else {
            // Sprawdź stare hasło
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $current_hash = $stmt->fetchColumn();
            
            if (!password_verify($old_password, $current_hash)) {
                $errors[] = "Stare hasło jest nieprawidłowe.";
            } else {
                // Zmień hasło
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
                $stmt->execute(['pass' => $new_hash, 'id' => $user_id]);
                
                admin_log_activity($pdo, $user_id, 'Zmiana hasła', "user_id: {$user_id}");
                $success = "Hasło zostało zmienione pomyślnie.";
            }
        }
    } elseif ($action === 'change_name') {
        $new_name = trim($_POST['name'] ?? '');
        
        if (empty($new_name)) {
            $errors[] = "Imię i nazwisko nie może być puste.";
        } elseif (strlen($new_name) < 2) {
            $errors[] = "Imię i nazwisko musi mieć minimum 2 znaki.";
        } else {
            $old_name = $user['name'];
            $stmt = $pdo->prepare("UPDATE users SET name = :name WHERE id = :id");
            $stmt->execute(['name' => htmlspecialchars($new_name, ENT_QUOTES), 'id' => $user_id]);
            
            // Aktualizuj sesję
            $_SESSION['user_name'] = htmlspecialchars($new_name, ENT_QUOTES);
            
            admin_log_activity($pdo, $user_id, 'Zmiana imienia i nazwiska', "user_id: {$user_id}, old: {$old_name}, new: {$new_name}");
            $user['name'] = htmlspecialchars($new_name, ENT_QUOTES);
            $success = "Imię i nazwisko zostało zmienione pomyślnie.";
        }
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ustawienia konta — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container narrow">
  <h2>Ustawienia konta</h2>
  
  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  
  <?php if ($success): ?>
    <div class="alert alert-info"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>
  
  <div class="panel">
    <h3>Zmień imię i nazwisko</h3>
    <form method="post">
      <input type="hidden" name="action" value="change_name">
      <label>Imię i nazwisko
        <input type="text" name="name" value="<?=htmlspecialchars($user['name'] ?? '', ENT_QUOTES)?>" required>
      </label>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Zapisz zmiany</button>
      </div>
    </form>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Zmień hasło</h3>
    <form method="post">
      <input type="hidden" name="action" value="change_password">
      <label>Stare hasło
        <input type="password" name="old_password" required>
      </label>
      <label>Nowe hasło
        <input type="password" name="new_password" required minlength="6">
      </label>
      <label>Potwierdź nowe hasło
        <input type="password" name="confirm_password" required minlength="6">
      </label>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Zmień hasło</button>
      </div>
    </form>
  </div>
  
  <div class="panel" style="margin-top:18px">
    <h3>Informacje o koncie</h3>
    <p><strong>Email:</strong> <?=htmlspecialchars($user['email'] ?? '', ENT_QUOTES)?></p>
    <p class="muted">Zmiana adresu email nie jest obecnie dostępna. Skontaktuj się z administratorem.</p>
  </div>
  
  <div class="form-actions" style="margin-top:18px">
    <a class="btn" href="user_panel.php">Powrót do panelu</a>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
