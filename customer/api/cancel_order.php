<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    $orderId = $input['order_id'] ?? null;

    if (!$orderId) {
        throw new Exception('Order ID is required');
    }
    $conn = getDatabaseConnection();
    $customerEmail = $_SESSION['customer_email'];

    $sql = "SELECT id, statuss, email FROM orders WHERE id = ? AND email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $orderId, $customerEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        throw new Exception('Order not found or access denied');
    }

    if (strtolower($order['statuss']) !== 'pending') {
        throw new Exception('Only pending orders can be canceled');
    }
    $sql = "UPDATE orders SET statuss = 'Canceled' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Order canceled successfully',
        'order_id' => $orderId
    ]);
} catch (Exception $e) {
    error_log("Cancel order error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
