<?php
/**
 * Database query functions to eliminate duplication
 */

/**
 * Get property by ID with owner information
 * @param PDO $pdo Database connection
 * @param int $id Property ID
 * @return array|false Property data or false if not found
 */
function get_property_by_id(PDO $pdo, int $id) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name AS owner_name, u.email AS owner_email, u.id AS owner_id 
        FROM properties p 
        LEFT JOIN users u ON p.owner_id = u.id 
        WHERE p.id = :id 
        LIMIT 1
    ");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get properties with optional filters
 * @param PDO $pdo Database connection
 * @param string $search Search term for title/description
 * @param string $city City filter
 * @param int $limit Maximum number of results
 * @return array Array of properties
 */
function get_properties(PDO $pdo, string $search = '', string $city = '', int $limit = 0): array {
    $sql = "SELECT id, title, city, price, image FROM properties WHERE 1=1";
    $params = [];
    
    if ($search !== '') {
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }
    
    if ($city !== '') {
        $sql .= " AND city = :city";
        $params['city'] = $city;
    }
    
    $sql .= " ORDER BY id DESC";
    
    if ($limit > 0) {
        $sql .= " LIMIT :limit";
    }
    
    $stmt = $pdo->prepare($sql);
    
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get user rentals with property information
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $limit Maximum number of results (0 = no limit)
 * @return array Array of rentals
 */
function get_user_rentals(PDO $pdo, int $userId, int $limit = 0): array {
    $sql = "
        SELECT r.*, p.title, p.city 
        FROM rentals r 
        LEFT JOIN properties p ON r.property_id = p.id 
        WHERE r.user_id = :uid 
        ORDER BY r.created_at DESC
    ";
    
    if ($limit > 0) {
        $sql .= " LIMIT :limit";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get rental statistics for a user
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return array Statistics array with total and upcoming rentals
 */
function get_user_rental_stats(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = :uid");
    $stmt->execute(['uid' => $userId]);
    $total = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = :uid AND start_date >= CURDATE()");
    $stmt->execute(['uid' => $userId]);
    $upcoming = (int)$stmt->fetchColumn();
    
    return [
        'total' => $total,
        'upcoming' => $upcoming
    ];
}

/**
 * Get user support tickets
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $limit Maximum number of results
 * @return array Array of support tickets
 */
function get_user_tickets(PDO $pdo, int $userId, int $limit = 5): array {
    $stmt = $pdo->prepare("
        SELECT id, subject, status, created_at 
        FROM support_tickets 
        WHERE user_id = :uid 
        ORDER BY created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get conversations for a user
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $limit Maximum number of messages to fetch
 * @return array Array of conversations grouped by property and partner
 */
function get_user_conversations(PDO $pdo, int $userId, int $limit = 200): array {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u_from.name AS from_name, 
               u_to.name AS to_name, 
               p.title AS property_title, 
               p.image AS property_image
        FROM messages m
        LEFT JOIN users u_from ON m.from_user_id = u_from.id
        LEFT JOIN users u_to ON m.to_user_id = u_to.id
        LEFT JOIN properties p ON m.property_id = p.id
        WHERE m.from_user_id = :uid OR m.to_user_id = :uid
        ORDER BY m.sent_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $convs = [];
    
    foreach ($rows as $r) {
        $propertyId = intval($r['property_id'] ?? 0);
        $partnerId = ($r['from_user_id'] == $userId) ? (int)$r['to_user_id'] : (int)$r['from_user_id'];
        $key = $propertyId . ':' . $partnerId;
        
        if (!isset($convs[$key])) {
            $img = $r['property_image'] ?? null;
            $imgUrl = $img ? ('uploads/properties/' . rawurlencode($img)) : null;
            
            $convs[$key] = [
                'property_id' => $propertyId,
                'partner_id' => $partnerId,
                'partner_name' => ($r['from_user_id'] == $userId) ? ($r['to_name'] ?? 'Użytkownik') : ($r['from_name'] ?? 'Użytkownik'),
                'property_title' => $r['property_title'] ?? 'oferta',
                'snippet' => mb_substr(strip_tags($r['body']), 0, 160),
                'sent_at' => $r['sent_at'],
                'property_image' => $imgUrl,
            ];
        }
    }
    
    return array_values($convs);
}

/**
 * Get message thread between two users for a property
 * @param PDO $pdo Database connection
 * @param int $propertyId Property ID
 * @param int $userId Current user ID
 * @param int $partnerId Other user ID
 * @return array Array of messages in chronological order
 */
function get_message_thread(PDO $pdo, int $propertyId, int $userId, int $partnerId): array {
    $stmt = $pdo->prepare("
        SELECT m.*, u_from.name AS from_name, u_to.name AS to_name
        FROM messages m
        LEFT JOIN users u_from ON m.from_user_id = u_from.id
        LEFT JOIN users u_to ON m.to_user_id = u_to.id
        WHERE m.property_id = :pid
          AND ((m.from_user_id = :me AND m.to_user_id = :partner) 
               OR (m.from_user_id = :partner AND m.to_user_id = :me))
        ORDER BY m.sent_at ASC
    ");
    $stmt->execute([
        'pid' => $propertyId,
        'me' => $userId,
        'partner' => $partnerId
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Send a message
 * @param PDO $pdo Database connection
 * @param int $fromUserId Sender user ID
 * @param int $toUserId Recipient user ID
 * @param int $propertyId Property ID
 * @param string $body Message body
 * @return bool Success status
 */
function send_message(PDO $pdo, int $fromUserId, int $toUserId, int $propertyId, string $body): bool {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO messages (from_user_id, to_user_id, property_id, body, sent_at, read_flag) 
            VALUES (:from, :to, :pid, :body, NOW(), 0)
        ");
        $stmt->execute([
            'from' => $fromUserId,
            'to' => $toUserId,
            'pid' => $propertyId,
            'body' => $body
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Error sending message: " . $e->getMessage());
        return false;
    }
}
