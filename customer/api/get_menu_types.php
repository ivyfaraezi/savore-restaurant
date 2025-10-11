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
    $stmt = $pdo->prepare("SELECT DISTINCT type FROM menu ORDER BY type ASC");
    $stmt->execute();
    $types = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $types[] = $row['type'];
    }
    echo json_encode([
        'success' => true,
        'data' => $types,
        'count' => count($types)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
