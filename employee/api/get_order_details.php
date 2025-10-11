<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_GET['orderId'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

$orderId = $_GET['orderId'];

$stmt = $conn->prepare("SELECT id, name, email, mobile, items, total FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$order = $result->fetch_assoc();
$itemsString = $order['items'];
$itemDetails = [];

preg_match_all('/([^(]+)\s*\(x(\d+)\)/', $itemsString, $matches, PREG_SET_ORDER);

if (!empty($matches)) {
    foreach ($matches as $match) {
        $itemName = trim($match[1]);
        $itemName = ltrim($itemName, ', ');
        $itemName = rtrim($itemName, ', ');
        $itemName = preg_replace('/\s+/', ' ', $itemName);
        $itemName = trim($itemName);

        $quantity = intval($match[2]);
        $menuStmt = $conn->prepare("SELECT price FROM menu WHERE name = ? LIMIT 1");
        $menuStmt->bind_param("s", $itemName);
        $menuStmt->execute();
        $menuResult = $menuStmt->get_result();

        if ($menuResult->num_rows > 0) {
            $menuRow = $menuResult->fetch_assoc();
            $unitPrice = floatval($menuRow['price']);
        } else {
            $searchName = '%' . $itemName . '%';
            $fuzzyStmt = $conn->prepare("SELECT price FROM menu WHERE name LIKE ? LIMIT 1");
            $fuzzyStmt->bind_param("s", $searchName);
            $fuzzyStmt->execute();
            $fuzzyResult = $fuzzyStmt->get_result();

            if ($fuzzyResult->num_rows > 0) {
                $fuzzyRow = $fuzzyResult->fetch_assoc();
                $unitPrice = floatval($fuzzyRow['price']);
            } else {
                $unitPrice = 0;
            }
            $fuzzyStmt->close();
        }

        $itemDetails[] = [
            'name' => $itemName,
            'quantity' => $quantity,
            'unitPrice' => $unitPrice,
            'amount' => $unitPrice * $quantity
        ];

        $menuStmt->close();
    }
}

$calculatedTotal = array_sum(array_column($itemDetails, 'amount'));
$totalQuantity = array_sum(array_column($itemDetails, 'quantity'));
$actualTotal = floatval($order['total']);

if ($calculatedTotal > 0 && abs($calculatedTotal - $actualTotal) > 0.01) {
    $adjustmentRatio = $actualTotal / $calculatedTotal;
    foreach ($itemDetails as &$item) {
        $item['unitPrice'] = round($item['unitPrice'] * $adjustmentRatio, 2);
        $item['amount'] = round($item['unitPrice'] * $item['quantity'], 2);
    }
} elseif ($calculatedTotal == 0 && $totalQuantity > 0) {
    $averageUnitPrice = round($actualTotal / $totalQuantity, 2);
    $distributedTotal = 0;

    foreach ($itemDetails as $key => &$item) {
        if ($key === count($itemDetails) - 1) {
            $item['unitPrice'] = round(($actualTotal - $distributedTotal) / $item['quantity'], 2);
            $item['amount'] = round($actualTotal - $distributedTotal, 2);
        } else {
            $item['unitPrice'] = $averageUnitPrice;
            $item['amount'] = round($averageUnitPrice * $item['quantity'], 2);
            $distributedTotal += $item['amount'];
        }
    }
}

$response = [
    'success' => true,
    'order' => [
        'id' => $order['id'],
        'customerName' => $order['name'],
        'customerEmail' => $order['email'],
        'customerMobile' => $order['mobile'],
        'items' => $itemDetails,
        'total' => $actualTotal
    ]
];

echo json_encode($response);
$stmt->close();
$conn->close();
