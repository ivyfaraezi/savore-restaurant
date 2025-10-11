<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
require_once '../config/config.php';

try {
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);
        exit;
    }
    $conn = getDatabaseConnection();
    $user_email = $_SESSION['customer_email'];
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
    if ($order_id) {
        $sql = "SELECT id, name, email, mobile, items, quantities, total, statuss 
                FROM orders 
                WHERE id = ? AND email = ?
                ORDER BY id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $order_id, $user_email);
    } else {
        $sql = "SELECT id, name, email, mobile, items, quantities, total, statuss 
                FROM orders 
                WHERE email = ?
                ORDER BY id DESC 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user_email);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $items_array = [];
        if (!empty($row['items'])) {
            $items_list = explode(', ', $row['items']);
            foreach ($items_list as $item_string) {
                if (preg_match('/^(.+?)\s*\(x(\d+)\)$/', $item_string, $matches)) {
                    $items_array[] = [
                        'name' => trim($matches[1]),
                        'quantity' => intval($matches[2])
                    ];
                } else {
                    $items_array[] = [
                        'name' => trim($item_string),
                        'quantity' => 1
                    ];
                }
            }
        }

        $orders[] = [
            'id' => $row['id'],
            'order_number' => 'ORD' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['mobile'],
            'items' => $items_array,
            'total_items' => $row['quantities'],
            'total' => $row['total'],
            'status' => $row['statuss'],
            'created_at' => date('Y-m-d H:i:s')  
        ];
    }

    if ($order_id && empty($orders)) {
        echo json_encode([
            'success' => false,
            'message' => 'Order not found'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $order_id ? $orders[0] : $orders,
        'count' => count($orders)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
