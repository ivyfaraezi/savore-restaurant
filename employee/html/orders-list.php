<?php
require_once '../config/database.php';
require_once '../config/email_service.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['ajax_action']) && $input['ajax_action'] === 'update_status') {
        header('Content-Type: application/json');
        error_log("AJAX request received: " . print_r($input, true));
        if (!isset($input['order_id']) || !isset($input['status'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing order_id or status']);
            exit;
        }

        $order_id = intval($input['order_id']);
        $status = trim($input['status']);
        $valid_statuses = ['pending', 'ready_to_serve', 'in_progress', 'canceled'];
        if (!in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status value']);
            exit;
        }
        $status_display_map = [
            'pending' => 'Pending',
            'ready_to_serve' => 'Ready To Serve',
            'in_progress' => 'In Progress',
            'canceled' => 'Canceled'
        ];

        $display_status = $status_display_map[$status];

        try {
            $order_stmt = $conn->prepare("SELECT name, email, mobile, items, quantities, total FROM orders WHERE id = ?");
            $order_stmt->bind_param("i", $order_id);
            $order_stmt->execute();
            $order_result = $order_stmt->get_result();

            if ($order_result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'No order found with the given ID']);
                exit;
            }

            $order_data = $order_result->fetch_assoc();
            $order_stmt->close();
            $stmt = $conn->prepare("UPDATE orders SET statuss = ? WHERE id = ?");
            $stmt->bind_param("si", $display_status, $order_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $items_array = explode(', ', $order_data['items']);
                    $email_items = [];

                    foreach ($items_array as $item_string) {
                        if (preg_match('/^(.*?)\s*\(x(\d+)\)$/', $item_string, $matches)) {
                            $item_name = trim($matches[1]);
                            $quantity = intval($matches[2]);
                            $estimated_price = floatval($order_data['total']) / intval($order_data['quantities']);
                            $email_items[] = [
                                'item' => $item_name,
                                'quantity' => $quantity,
                                'total' => $estimated_price * $quantity
                            ];
                        }
                    }

                    $orderDetails = [
                        'email' => $order_data['email'],
                        'mobile' => $order_data['mobile'],
                        'items' => $email_items,
                        'totalQuantity' => intval($order_data['quantities']),
                        'grandTotal' => floatval($order_data['total']),
                        'status' => $display_status
                    ];
                    $emailService = new EmailService();
                    $emailResult = $emailService->sendStatusUpdateEmail(
                        $order_data['email'],
                        $order_data['name'],
                        $order_id,
                        $orderDetails,
                        $display_status
                    );

                    $message = 'Status updated successfully';
                    if ($emailResult['success']) {
                        $message .= ' and notification email sent to customer';
                    } else {
                        $message .= ' but email notification failed to send';
                        error_log("Email notification failed for order #$order_id: " . $emailResult['message']);
                    }

                    echo json_encode([
                        'success' => true,
                        'message' => $message,
                        'new_status' => $display_status,
                        'status_class' => 'status-badge status-' . ($status === 'in_progress' ? 'processing' : ($status === 'ready_to_serve' ? 'completed' : $status)),
                        'email_sent' => $emailResult['success']
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No order found with the given ID']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }

            $stmt->close();
        } catch (Exception $e) {
            error_log("Database error in orders-list.php: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }

        $conn->close();
        exit;
    }
}

$sql = "SELECT id, name, email, mobile, items, quantities, total, statuss FROM orders ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <link rel="stylesheet" href="../css/orders-list.css">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../scripts/logout.js"></script>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <span class="logo-icon">🍽️</span>
            <span class="logo-text">Savoré</span>
        </div>
        <ul class="sidebar-links">
            <li><a href="make-orders.php"><span class="icon">🛒</span> <span class="link-text">Create Order</span></a></li>
            <li><a href="orders-list.php"><span class="icon">📋</span> <span class="link-text">Orders List</span></a></li>
            <li><a href="tables.php"><span class="icon">🍽️</span> <span class="link-text">Tables</span></a></li>
            <li><a href="bill.php"><span class="icon">🧾</span> <span class="link-text">Bill & Invoices</span></a></li>
        </ul>
        <button class="logout-btn"><span class="icon">🚪</span> <span class="link-text">Logout</span></button>
    </aside>
    <div class="order-list-container">
        <h1 class="order-list-heading">Order List</h1>
        <div class="table-wrapper">
            <table border="1">
                <thead class="table-header">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Email</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Change Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $badgeClass = 'status-badge ';
                            switch (strtolower($row['statuss'])) {
                                case 'pending':
                                    $badgeClass .= 'status-pending';
                                    break;
                                case 'ready to serve':
                                    $badgeClass .= 'status-completed';
                                    break;
                                case 'in progress':
                                case 'in_progress':
                                    $badgeClass .= 'status-processing';
                                    break;
                                case 'canceled':
                                case 'cancelled':
                                    $badgeClass .= 'status-canceled';
                                    break;
                                default:
                                    $badgeClass .= 'status-pending';
                            }

                            echo "<tr data-order-id='" . htmlspecialchars($row['id']) . "'>";
                            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['items']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['quantities']) . "</td>";
                            echo "<td><span class='" . $badgeClass . "'>" . htmlspecialchars($row['statuss']) . "</span></td>";
                            echo "<td>";
                            echo "<select class='status-select'>";
                            $statuses = ['pending' => 'Pending', 'ready_to_serve' => 'Ready To Serve', 'in_progress' => 'In Progress', 'canceled' => 'Canceled'];
                            foreach ($statuses as $value => $text) {
                                $selected = (strtolower($row['statuss']) == $value ||
                                    ($value == 'ready_to_serve' && strtolower($row['statuss']) == 'ready to serve') ||
                                    ($value == 'in_progress' && strtolower($row['statuss']) == 'in progress')) ? 'selected' : '';
                                echo "<option value='" . $value . "' " . $selected . ">" . $text . "</option>";
                            }
                            echo "</select>";
                            echo "</td>";
                            echo "<td><button class='save-status-btn'>Save</button></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>No orders found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="../scripts/orders-list.js"></script>
    <script>
        document.querySelector('.sidebar-logo').addEventListener('click', function() {
            window.location.href = '../index.php';
        });
    </script>
</body>

</html>