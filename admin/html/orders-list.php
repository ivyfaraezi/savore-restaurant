<?php
require_once '../config/database.php';
$sql_orders = "SELECT id, name, email, mobile, items, quantities, total, statuss FROM orders ORDER BY id ";
$result_orders = $conn->query($sql_orders);
$sql_tables = "SELECT id, name, email, mobile, datee, times, guests, tableno FROM tables ORDER BY id ";
$result_tables = $conn->query($sql_tables);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders</title>
    <link rel="stylesheet" href="../styles/modern.css">
    <link rel="stylesheet" href="../styles/orders-list.css">
    <script src="../scripts/logout.js"></script>
</head>

<body>
    <nav class="sidebar-container">
        <div class="sidebar-header">
            <a class="savore" href="../index.php">
                <span class="sidebar-logo">🍽️</span>
                <h1>Savoré</h1>
            </a>
        </div>
        <div class="sidebar-links">
            <ul>
                <li><a href="employee.php"><span class="sidebar-icon">👨‍💼</span> Employee</a></li>
                <li><a href="customer.php"><span class="sidebar-icon">🧑‍🤝‍🧑</span> Customer</a></li>
                <li><a href="menu.php"><span class="sidebar-icon">🍲</span> Menu</a></li>
                <li><a href="orders-list.php"><span class="sidebar-icon">🧾</span> Orders & Reservations</a></li>
                <li><a href="reviews.php"><span class="sidebar-icon">📊</span> Reviews</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <button class="sidebar-logout"><span class="sidebar-icon">🚪</span> Logout</button>
        </div>
    </nav>
    <main class="main-content">
        <section class="orders-list-container">
            <h1>Recent Orders</h1>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Items</th>
                            <th>Total Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_orders && $result_orders->num_rows > 0) {
                            while ($row = $result_orders->fetch_assoc()) {
                                $badgeClass = 'status-badge ';
                                $status = strtolower(trim($row['statuss']));

                                switch ($status) {
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
                                echo "<tr>";
                                echo "<td>#" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['items']) . "</td>";
                                echo "<td>৳" . number_format($row['total'], 2) . "</td>";
                                echo "<td><span class='$badgeClass'>" . htmlspecialchars(ucfirst($row['statuss'])) . "</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align: center; padding: 20px;'>No orders found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="reservations-list-container">
            <h1>Reservations</h1>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Guest No</th>
                            <th>Table</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_tables && $result_tables->num_rows > 0) {
                            while ($row = $result_tables->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>#" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['mobile']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['datee']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['times']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['guests']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['tableno']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' style='text-align: center; padding: 20px;'>No reservations found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>

</html>
<?php
$conn->close();
?>