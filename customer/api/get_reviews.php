<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $limit = max(1, min($limit, 50)); // Between 1 and 50
    $stmt = $pdo->prepare("SELECT name, stars, message, created_at FROM reviews ORDER BY created_at DESC LIMIT " . $limit);
    $stmt->execute();

    $reviews = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $reviews[] = [
            'name' => htmlspecialchars($row['name']),
            'stars' => intval($row['stars']),
            'message' => htmlspecialchars($row['message']),
            'created_at' => $row['created_at'],
            'date_formatted' => date('F j, Y', strtotime($row['created_at']))
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $reviews,
        'count' => count($reviews)
    ]);
} catch (Exception $e) {
    error_log("Get reviews error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load reviews',
        'data' => []
    ]);
}
