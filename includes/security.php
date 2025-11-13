<?php
/**
 * Security helper functions
 * Provides CSRF protection and other security utilities
 */

/**
 * Generate a CSRF token and store it in session
 * @return string The generated token
 */
function generate_csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from request
 * @param string|null $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verify_csrf_token(?string $token): bool {
    if (!isset($_SESSION['csrf_token']) || !$token) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token input field HTML
 * @return string HTML input field with CSRF token
 */
function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
}

/**
 * Validate file upload for images
 * @param array $file The $_FILES array element
 * @param int $maxSize Maximum file size in bytes (default 5MB)
 * @return array Array with 'success' (bool) and 'error' (string) or 'filename' (string)
 */
function validate_image_upload(array $file, int $maxSize = 5242880): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => "Błąd przesyłania pliku (kod: {$file['error']})."];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $maxMB = round($maxSize / 1024 / 1024, 1);
        return ['success' => false, 'error' => "Plik jest za duży. Maksymalny rozmiar to {$maxMB}MB."];
    }
    
    // Verify actual image type
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => "Plik nie jest prawidłowym obrazem."];
    }
    
    // Check allowed MIME types
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($imageInfo['mime'], $allowedMimes)) {
        return ['success' => false, 'error' => "Nieprawidłowy format obrazu. Dozwolone: JPG, PNG, WEBP."];
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'error' => "Nieprawidłowe rozszerzenie pliku."];
    }
    
    // Generate unique filename
    $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    
    return ['success' => true, 'filename' => $filename, 'extension' => $ext];
}

/**
 * Sanitize filename for safe storage
 * @param string $filename Original filename
 * @return string Sanitized filename
 */
function sanitize_filename(string $filename): string {
    // Remove any path components
    $filename = basename($filename);
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return $filename;
}

/**
 * Check if user has permission to access resource
 * @param string $resource Resource name
 * @param string|null $action Action to perform
 * @return bool True if permitted, false otherwise
 */
function has_permission(string $resource, ?string $action = null): bool {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    $role = $_SESSION['user_role'];
    
    // Admin has all permissions
    if ($role === 'admin') {
        return true;
    }
    
    // Define role-based permissions
    $permissions = [
        'user' => [
            'properties' => ['view', 'list'],
            'rentals' => ['view', 'create'],
            'messages' => ['view', 'create', 'reply'],
            'tickets' => ['view', 'create'],
        ],
        'owner' => [
            'properties' => ['view', 'list', 'create', 'edit', 'delete'],
            'rentals' => ['view'],
            'messages' => ['view', 'create', 'reply'],
            'assignments' => ['view', 'create'],
        ],
    ];
    
    if (!isset($permissions[$role][$resource])) {
        return false;
    }
    
    if ($action === null) {
        return true;
    }
    
    return in_array($action, $permissions[$role][$resource]);
}
