<?php
require_once '../config/database.php';
require_once '../config/email_service.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submitOrder') {
    $customerName = $_POST['customerName'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $cartData = json_decode($_POST['cartData'], true);
    $items = [];
    $totalQuantity = 0;
    $grandTotal = 0;

    foreach ($cartData as $item) {
        $items[] = $item['item'] . ' (x' . $item['quantity'] . ')';
        $totalQuantity += intval($item['quantity']);
        $grandTotal += floatval($item['total']);
    }

    $itemsString = implode(', ', $items);
    $status = 'Pending';
    $stmt = $conn->prepare("INSERT INTO orders (name, email, mobile, items, quantities, total, statuss) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissis", $customerName, $email, $mobile, $itemsString, $totalQuantity, $grandTotal, $status);

    if ($stmt->execute()) {
        $orderId = $conn->insert_id;
        $orderDetails = [
            'email' => $email,
            'mobile' => $mobile,
            'items' => $cartData,
            'totalQuantity' => $totalQuantity,
            'grandTotal' => $grandTotal,
            'status' => $status
        ];
        $emailService = new EmailService();
        $emailResult = $emailService->sendOrderConfirmation($email, $customerName, $orderId, $orderDetails);

        if ($emailResult['success']) {
            $response = [
                'success' => true,
                'message' => 'Order submitted successfully! A confirmation email has been sent to your email address.',
                'orderId' => $orderId
            ];
        } else {
            $response = [
                'success' => true,
                'message' => 'Order submitted successfully! However, we could not send the confirmation email. Please contact us if you need a copy.',
                'orderId' => $orderId,
                'emailWarning' => true
            ];
        }
    } else {
        $response = ['success' => false, 'message' => 'Error submitting order: ' . $conn->error];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'getItems' && isset($_GET['category'])) {
    $category = $_GET['category'];
    $stmt = $conn->prepare("SELECT name, price FROM menu WHERE type = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = array();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($items);
    exit;
}

$categoriesQuery = "SELECT DISTINCT type FROM menu ORDER BY type";
$categoriesResult = $conn->query($categoriesQuery);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Order</title>
    <script src="../scripts/make-orders.js" defer></script>
    <link rel="stylesheet" href="../css/make-orders.css">
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
    <div class="make-orders-flex">
        <div class="make-orders-container">
            <h2>Make An Order</h2>
            <form action="" method="post" id="makeOrderForm">
                <input type="text" id="customerName" name="customerName" required placeholder="Enter your name">
                <br><br>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
                <br><br>
                <input type="text" id="mobile" name="mobile" required placeholder="Enter your mobile number">
                <br><br>
                <select id="itemCategory" name="itemCategory" required>
                    <option value="">Select Category</option>
                    <?php
                    if ($categoriesResult && $categoriesResult->num_rows > 0) {
                        while ($row = $categoriesResult->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['type']) . '">' . htmlspecialchars($row['type']) . '</option>';
                        }
                    }
                    ?>
                </select>
                <br><br>
                <select id="itemName" name="itemName" required>
                    <option value="">Select Item</option>
                </select>
                <br><br>
                <div class="make-orders-row">
                    <div class="make-orders-row-col">
                        <input type="text" id="price" name="price" placeholder="Unit Price" readonly>
                    </div>
                    <div class="make-orders-row-col make-orders-row-col--right">
                        <input type="text" id="total" name="total" placeholder="Total Price" readonly>
                    </div>
                    <div class="make-orders-row-col make-orders-row-col--right">
                        <input type="number" id="quantity" name="quantity" min="1" value="1" required>
                    </div>
                </div>
                <br><br>
                <button type="button" id="addToCart">Add to Cart</button>
                <br><br>
            </form>
        </div>
        <div class="make-orders-cart">
            <h3>Cart</h3>
            <table id="cartTable" border="1" style="width:100%; text-align:left;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Cart items will appear here -->
                </tbody>
            </table>
            <button type="submit" form="makeOrderForm" id="submitOrderBtn">Checkout</button>
        </div>
    </div>
    <script>
        document.querySelector('.sidebar-logo').addEventListener('click', function() {
            window.location.href = '../index.php';
        });
    </script>
</body>

</html>