<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_role('admin');

// Fetch all properties with rental statistics
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.title,
        p.city,
        p.price,
        p.owner_id,
        p.created_at,
        u.name AS owner_name,
        COUNT(r.id) AS rentals_count,
        COALESCE(SUM(r.price), 0) AS total_revenue
    FROM properties p
    LEFT JOIN users u ON p.owner_id = u.id
    LEFT JOIN rentals r ON p.id = r.property_id
    GROUP BY p.id
    ORDER BY p.id DESC
");
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie nieruchomościami — <?=APP_NAME?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
    <h2>Zarządzanie nieruchomościami</h2>
    
    <div class="panel">
        <p><a class="btn btn-primary" href="add_property.php">Dodaj nową nieruchomość</a></p>
    </div>

    <?php if (empty($properties)): ?>
        <div class="panel">
            <p>Brak nieruchomości w systemie.</p>
        </div>
    <?php else: ?>
        <div class="panel" style="overflow-x:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tytuł</th>
                        <th>Miasto</th>
                        <th>Cena</th>
                        <th>Właściciel</th>
                        <th>Liczba wynajmów</th>
                        <th>Przychód</th>
                        <th>Data utworzenia</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($properties as $prop): ?>
                        <tr>
                            <td><?=htmlspecialchars($prop['id'])?></td>
                            <td><?=htmlspecialchars($prop['title'])?></td>
                            <td><?=htmlspecialchars($prop['city'])?></td>
                            <td><?=number_format((float)$prop['price'], 2, ',', ' ')?> zł</td>
                            <td><?=htmlspecialchars($prop['owner_name'] ?? 'Brak')?></td>
                            <td><?=htmlspecialchars($prop['rentals_count'])?></td>
                            <td><?=number_format((float)$prop['total_revenue'], 2, ',', ' ')?> zł</td>
                            <td><?=htmlspecialchars($prop['created_at'])?></td>
                            <td>
                                <a class="btn btn-sm" href="edit_property.php?id=<?=urlencode($prop['id'])?>">Edytuj</a>
                                <a class="btn btn-sm" href="property_details.php?id=<?=urlencode($prop['id'])?>">Zobacz</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
