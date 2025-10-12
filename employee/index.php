<?php
// Include database connection
require_once 'config/database.php';

// Fetch data from database tables
try {
    // Get customers count
    $customers_query = "SELECT COUNT(*) as count FROM customers";
    $customers_result = $conn->query($customers_query);
    $customers_count = $customers_result->fetch_assoc()['count'];

    // Get menu items count
    $menu_query = "SELECT COUNT(*) as count FROM menu";
    $menu_result = $conn->query($menu_query);
    $menu_count = $menu_result->fetch_assoc()['count'];

    // Get total orders count
    $orders_query = "SELECT COUNT(*) as count FROM orders";
    $orders_result = $conn->query($orders_query);
    $orders_count = $orders_result->fetch_assoc()['count'];

    // Get table reservations count
    $tables_query = "SELECT COUNT(*) as count FROM tables";
    $tables_result = $conn->query($tables_query);
    $tables_count = $tables_result->fetch_assoc()['count'];

    // Get recent activities
    $recent_orders_query = "SELECT name, email, total, statuss FROM orders ORDER BY id DESC LIMIT 3";
    $recent_orders_result = $conn->query($recent_orders_query);

    $recent_tables_query = "SELECT name, email, datee, times, guests, tableno FROM tables ORDER BY id DESC LIMIT 3";
    $recent_tables_result = $conn->query($recent_tables_query);

    $recent_customers_query = "SELECT name, email, created_at FROM customers ORDER BY created_at DESC LIMIT 2";
    $recent_customers_result = $conn->query($recent_customers_query);

    // Get menu type distribution for pie chart
    $menu_type_query = "SELECT type, COUNT(*) as count FROM menu GROUP BY type";
    $menu_type_result = $conn->query($menu_type_query);
    $menu_types = [];
    $menu_counts = [];

    if ($menu_type_result && $menu_type_result->num_rows > 0) {
        while ($row = $menu_type_result->fetch_assoc()) {
            $menu_types[] = $row['type'];
            $menu_counts[] = $row['count'];
        }
    } else {
        // No data found in menu table
        $menu_types = ['No Data'];
        $menu_counts = [1];
    }
} catch (Exception $e) {
    // Set default values in case of error
    $customers_count = 0;
    $menu_count = 0;
    $orders_count = 0;
    $tables_count = 0;
    $recent_orders_result = null;
    $recent_tables_result = null;
    $recent_customers_result = null;
    $menu_types = ['No Data'];
    $menu_counts = [1];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <span class="logo-icon">🍽️</span>
            <span class="logo-text">Savoré</span>
        </div>
        <ul class="sidebar-links">
            <li><a href="html/make-orders.php"><span class="icon">🛒</span> <span class="link-text">Create Order</span></a></li>
            <li><a href="html/orders-list.php"><span class="icon">📋</span> <span class="link-text">Orders List</span></a></li>
            <li><a href="html/tables.php"><span class="icon">🍽️</span> <span class="link-text">Tables</span></a></li>
            <li><a href="html/bill.php"><span class="icon">🧾</span> <span class="link-text">Bill & Invoices</span></a></li>
        </ul>
        <button class="logout-btn"><span class="icon">🚪</span> <span class="link-text">Logout</span></button>
    </aside>
    <main class="main-content">
        <section class="dashboard-grid">
            <div class="dashboard-card gradient2">
                <div class="card-title">Customers</div>
                <div class="card-value"><?php echo $customers_count; ?></div>
                <div class="card-desc">Registered Users</div>
            </div>
            <div class="dashboard-card gradient3">
                <div class="card-title">Menu Items</div>
                <div class="card-value"><?php echo $menu_count; ?></div>
                <div class="card-desc">Available Dishes</div>
            </div>
            <div class="dashboard-card gradient4">
                <div class="card-title">Orders</div>
                <div class="card-value"><?php echo $orders_count; ?></div>
                <div class="card-desc">Total Orders</div>
            </div>
            <div class="dashboard-card gradient1">
                <div class="card-title">Reserve Tables</div>
                <div class="card-value"><?php echo $tables_count; ?></div>
                <div class="card-desc">Active Reservations</div>
            </div>
        </section>
        <section class="dashboard-main-row">
            <div class="dashboard-pie-chart">
                <h3>Menu Types Distribution</h3>
                <canvas id="menuTypePieChart" width="300" height="300"></canvas>
            </div>
            <div class="dashboard-activities">
                <h3>Recent Activities</h3>
                <ul>
                    <?php
                    // Display recent customers
                    if ($recent_customers_result && $recent_customers_result->num_rows > 0) {
                        while ($customer = $recent_customers_result->fetch_assoc()) {
                            echo '<li><span class="activity-dot add"></span> New customer registered: ' . htmlspecialchars($customer['name']) . ' (' . htmlspecialchars($customer['email']) . ')</li>';
                        }
                    }

                    // Display recent orders
                    if ($recent_orders_result && $recent_orders_result->num_rows > 0) {
                        while ($order = $recent_orders_result->fetch_assoc()) {
                            $status_class = 'update';
                            if ($order['statuss'] == 'completed' || $order['statuss'] == 'delivered') {
                                $status_class = 'complete';
                            } elseif ($order['statuss'] == 'pending') {
                                $status_class = 'add';
                            }
                            echo '<li><span class="activity-dot ' . $status_class . '"></span> Order by ' . htmlspecialchars($order['name']) . ' - Tk ' . htmlspecialchars($order['total']) . ' (' . htmlspecialchars($order['statuss']) . ')</li>';
                        }
                    }

                    // Display recent table reservations
                    if ($recent_tables_result && $recent_tables_result->num_rows > 0) {
                        while ($table = $recent_tables_result->fetch_assoc()) {
                            echo '<li><span class="activity-dot add"></span> Table reservation by ' . htmlspecialchars($table['name']) . ' - Table ' . htmlspecialchars($table['tableno']) . ' for ' . htmlspecialchars($table['guests']) . ' guests on ' . htmlspecialchars($table['datee']) . ' at ' . htmlspecialchars($table['times']) . '</li>';
                        }
                    }

                    // If no data found, show default message
                    if ((!$recent_customers_result || $recent_customers_result->num_rows == 0) &&
                        (!$recent_orders_result || $recent_orders_result->num_rows == 0) &&
                        (!$recent_tables_result || $recent_tables_result->num_rows == 0)
                    ) {
                        echo '<li><span class="activity-dot update"></span> No recent activities found. Start by adding some data to the database.</li>';
                    }
                    ?>
                </ul>
            </div>
        </section>
    </main>

    <?php
    // Close database connection
    $conn->close();
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="scripts/script.js"></script>
    <script src="scripts/logout.js"></script>
    <script>
        // Pass PHP data to JavaScript
        const menuTypes = <?php echo json_encode($menu_types); ?>;
        const menuCounts = <?php echo json_encode($menu_counts); ?>;

        // Debug: Log the data to console
        console.log('Menu Types:', menuTypes);
        console.log('Menu Counts:', menuCounts);

        document.addEventListener('DOMContentLoaded', function() {
            if (window.Chart && menuTypes.length > 0 && menuCounts.length > 0) {
                try {
                    // Create pie chart for menu types
                    const pieCtx = document.getElementById('menuTypePieChart').getContext('2d');
                    const pieChart = new Chart(pieCtx, {
                        type: 'pie',
                        data: {
                            labels: menuTypes,
                            datasets: [{
                                data: menuCounts,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.8)',
                                    'rgba(54, 162, 235, 0.8)',
                                    'rgba(255, 205, 86, 0.8)',
                                    'rgba(75, 192, 192, 0.8)',
                                    'rgba(153, 102, 255, 0.8)',
                                    'rgba(255, 159, 64, 0.8)',
                                    'rgba(199, 199, 199, 0.8)',
                                    'rgba(83, 102, 255, 0.8)'
                                ],
                                borderColor: [
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 205, 86, 1)',
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(153, 102, 255, 1)',
                                    'rgba(255, 159, 64, 1)',
                                    'rgba(199, 199, 199, 1)',
                                    'rgba(83, 102, 255, 1)'
                                ],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true,
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                title: {
                                    display: false
                                }
                            }
                        }
                    });
                    console.log('Pie chart created successfully');
                } catch (error) {
                    console.error('Error creating pie chart:', error);
                }
            } else {
                console.error('Chart.js not loaded or no data available');
                console.log('menuTypes:', menuTypes);
                console.log('menuCounts:', menuCounts);
            }
        });

        document.querySelector('.sidebar-logo').addEventListener('click', function() {
            window.location.href = 'index.php';
        });
    </script>
</body>

</html>