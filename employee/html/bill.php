<?php
require_once '../config/database.php';
$sql = "SELECT id, name, email, mobile, items, quantities, total, statuss FROM orders ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill</title>
    <link rel="stylesheet" href="../css/bill.css">
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
            <li><a href="orders-list.php"><span class="icon">📋</span> <span class="link-text">Orders List</span></a>
            </li>
            <li><a href="tables.php"><span class="icon">🍽️</span> <span class="link-text">Tables</span></a></li>
            <li><a href="bill.php"><span class="icon">🧾</span> <span class="link-text">Bill & Invoices</span></a></li>
        </ul>
        <button class="logout-btn"><span class="icon">🚪</span> <span class="link-text">Logout</span></button>
    </aside>
    <div class="bills-container">
        <h3>Bill & Invoices</h3>
        <table id="billsTable" border="1">
            <thead>
                <tr>
                    <th>Bill ID</th>
                    <th>Customer</th>
                    <th>Items Name with Quantity</th>
                    <th>Total</th>
                    <th>Invoice</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $items_string = trim($row['items']);
                        if (preg_match('/\(x\d+\)/', $items_string)) {
                            $items_display = $items_string;
                        } else {
                            $items_array = array_filter(array_map('trim', explode(',', $row['items'])));
                            $quantities_array = array_filter(array_map('trim', explode(',', $row['quantities'])));

                            $formatted_items = array();
                            $item_count = min(count($items_array), count($quantities_array));

                            for ($i = 0; $i < $item_count; $i++) {
                                $item_name = $items_array[$i];
                                $quantity = $quantities_array[$i];
                                if (!empty($item_name) && !empty($quantity) && is_numeric($quantity)) {
                                    $formatted_items[] = $item_name . " (x" . $quantity . ")";
                                }
                            }

                            $items_display = !empty($formatted_items) ? implode(', ', $formatted_items) : 'No items';
                        }

                        echo "<tr>";
                        echo "<td>" . sprintf('%03d', $row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "<br><small>" . htmlspecialchars($row['email']) . "</small></td>";
                        echo "<td>" . htmlspecialchars($items_display) . "</td>";
                        echo "<td>Tk " . number_format($row['total'], 2) . "</td>";
                        echo "<td><button class='print-invoice-btn' data-customer-name='" . htmlspecialchars($row['name']) . "' data-customer-email='" . htmlspecialchars($row['email']) . "' data-customer-mobile='" . htmlspecialchars($row['mobile']) . "'>Print</button></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No orders found</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        document.querySelector(".sidebar-logo").addEventListener("click", function() {
            window.location.href = "../index.php";
        });
    </script>
    <script src="../scripts/print-invoice.js"></script>
</body>

</html>