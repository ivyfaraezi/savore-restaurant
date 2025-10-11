<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $name = trim($_POST['name'] ?? '');
    $stars = intval($_POST['stars'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    if (empty($name) || empty($message)) {
        echo json_encode([
            'success' => false,
            'message' => 'Name and review message are required fields.'
        ]);
        exit;
    }
    if ($stars < 1 || $stars > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Please select a star rating between 1 and 5.'
        ]);
        exit;
    }
    if (strlen($name) > 50) {
        echo json_encode([
            'success' => false,
            'message' => 'Name must be 50 characters or less.'
        ]);
        exit;
    }
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("INSERT INTO reviews (name, stars, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$name, $stars, $message]);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your review! Your feedback has been submitted successfully.'
    ]);
} catch (Exception $e) {
    error_log("Review submission error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, there was an error submitting your review. Please try again later.'
    ]);
}
