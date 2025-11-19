<?php

// user_settings.php

// User Profile and Password Change Functionality

// Assuming we have a user session started
session_start();

// Database connection (replace with your own connection details)
$host = 'localhost';
$username = 'db_user';
$password = 'db_pass';
db = new PDO("mysql:host=$host;dbname=your_db_name", $username, $password);

// Function to get user details
function getUserDetails($userId) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to update user password
function changePassword($userId, $newPassword) {
    global $db;
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
    return $stmt->execute([':password' => $hashedPassword, ':id' => $userId]);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo 'User not logged in.';
    exit;
}

$userId = $_SESSION['user_id'];
$userDetails = getUserDetails($userId);

// Handle password change request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    if (changePassword($userId, $_POST['new_password'])) {
        echo 'Password changed successfully.';
    } else {
        echo 'Failed to change password.';
    }
}

// HTML form to display user details and password change
? >
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
</head>
<body>
    <h1>User Profile</h1>
    <p>Name: <?php echo htmlspecialchars($userDetails['name']); ?></p>
    <p>Email: <?php echo htmlspecialchars($userDetails['email']); ?></p>

    <h2>Change Password</h2>
    <form method="POST">
        <input type="password" name="new_password" placeholder="New Password" required>
        <button type="submit">Change Password</button>
    </form>
</body>
</html>