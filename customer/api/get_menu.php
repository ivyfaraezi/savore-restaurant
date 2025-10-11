<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT id, name, type, price, photo FROM menu ORDER BY name ASC");
    $stmt->execute();

    $menuItems = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $photoBase64 = base64_encode($row['photo']);

        $menuItems[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'type' => $row['type'],
            'price' => (float)$row['price'], 
            'photo' => 'data:image/jpeg;base64,' . $photoBase64
        ];
    }
    echo json_encode([
        'success' => true,
        'data' => $menuItems,
        'count' => count($menuItems)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
