<?php
require_once 'config/database.php';
$employee_count = 0;
$customer_count = 0;
$menu_count = 0;
$order_count = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM employee");
if ($result && $row = $result->fetch_assoc()) {
    $employee_count = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customers");
if ($result && $row = $result->fetch_assoc()) {
    $customer_count = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM menu");
if ($result && $row = $result->fetch_assoc()) {
    $menu_count = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result && $row = $result->fetch_assoc()) {
    $order_count = $row['count'];
}

$menu_types_query = "SELECT type, COUNT(*) as count FROM menu GROUP BY type ORDER BY count DESC";
$menu_types_result = $conn->query($menu_types_query);
$menu_types = [];
$menu_counts = [];
if ($menu_types_result && $menu_types_result->num_rows > 0) {
    while ($row = $menu_types_result->fetch_assoc()) {
        $menu_types[] = ucfirst(str_replace('_', ' ', $row['type']));
        $menu_counts[] = (int)$row['count'];
    }
}

$order_status_query = "SELECT statuss, COUNT(*) as count FROM orders GROUP BY statuss ORDER BY count DESC";
$order_status_result = $conn->query($order_status_query);
$order_statuses = [];
$order_status_counts = [];
if ($order_status_result && $order_status_result->num_rows > 0) {
    while ($row = $order_status_result->fetch_assoc()) {
        $order_statuses[] = ucfirst($row['statuss']);
        $order_status_counts[] = (int)$row['count'];
    }
}

$reviews_rating_query = "SELECT stars, COUNT(*) as count FROM reviews GROUP BY stars ORDER BY stars ASC";
$reviews_rating_result = $conn->query($reviews_rating_query);
$rating_stars = [];
$rating_counts = [];
if ($reviews_rating_result && $reviews_rating_result->num_rows > 0) {
    while ($row = $reviews_rating_result->fetch_assoc()) {
        $rating_stars[] = $row['stars'] . ' Star' . ($row['stars'] > 1 ? 's' : '');
        $rating_counts[] = (int)$row['count'];
    }
}

$table_guests_query = "SELECT tableno, SUM(guests) as total_guests 
                       FROM tables 
                       GROUP BY tableno 
                       ORDER BY tableno ASC 
                       LIMIT 10";
$table_guests_result = $conn->query($table_guests_query);
$table_numbers = [];
$guest_counts = [];
if ($table_guests_result && $table_guests_result->num_rows > 0) {
    while ($row = $table_guests_result->fetch_assoc()) {
        $table_numbers[] = 'Table ' . $row['tableno'];
        $guest_counts[] = (int)$row['total_guests'];
    }
}

$recent_customers_result = $conn->query("SELECT name, email FROM customers ORDER BY id DESC LIMIT 2");
$recent_orders_result = $conn->query("SELECT name, total, statuss FROM orders ORDER BY id DESC LIMIT 3");
$recent_tables_result = $conn->query("SELECT name, tableno, guests, datee, times FROM tables ORDER BY id DESC LIMIT 2");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="styles/modern.css">
    <link rel="stylesheet" href="styles/dashboard.css">
</head>

<body>
    <nav class="sidebar-container">
        <div class="sidebar-header">
            <a class="savore" href="index.php">
                <span class="sidebar-logo">🍽️</span>
                <h1>Savoré</h1>
            </a>
        </div>
        <div class="sidebar-links">
            <ul>
                <li><a href="html/employee.php"><span class="sidebar-icon">👨‍💼</span> Employee</a></li>
                <li><a href="html/customer.php"><span class="sidebar-icon">🧑‍🤝‍🧑</span> Customer</a></li>
                <li><a href="html/menu.php"><span class="sidebar-icon">🍲</span> Menu</a></li>
                <li><a href="html/orders-list.php"><span class="sidebar-icon">🧾</span> Orders & Reservations</a></li>
                <li><a href="html/reviews.php"><span class="sidebar-icon">📊</span> Reviews</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <button class="sidebar-logout"><span class="sidebar-icon">🚪</span> Logout</button>
        </div>
    </nav>
    <main class="main-content">
        <section class="dashboard-grid">
            <div class="dashboard-card gradient1">
                <div class="card-title">Employees</div>
                <div class="card-value"><?php echo $employee_count; ?></div>
                <div class="card-desc">Active Staff</div>
            </div>
            <div class="dashboard-card gradient2">
                <div class="card-title">Customers</div>
                <div class="card-value"><?php echo $customer_count; ?></div>
                <div class="card-desc">Registered Users</div>
            </div>
            <div class="dashboard-card gradient3">
                <div class="card-title">Menu Items</div>
                <div class="card-value"><?php echo $menu_count; ?></div>
                <div class="card-desc">Available Dishes</div>
            </div>
            <div class="dashboard-card gradient4">
                <div class="card-title">Orders</div>
                <div class="card-value"><?php echo $order_count; ?></div>
                <div class="card-desc">Total Orders</div>
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
                    if ($recent_customers_result && $recent_customers_result->num_rows > 0) {
                        while ($customer = $recent_customers_result->fetch_assoc()) {
                            echo '<li><span class="activity-dot add"></span> New customer registered: ' . htmlspecialchars($customer['name']) . ' (' . htmlspecialchars($customer['email']) . ')</li>';
                        }
                    }
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

                    if ($recent_tables_result && $recent_tables_result->num_rows > 0) {
                        while ($table = $recent_tables_result->fetch_assoc()) {
                            echo '<li><span class="activity-dot add"></span> Table reservation by ' . htmlspecialchars($table['name']) . ' - Table ' . htmlspecialchars($table['tableno']) . ' for ' . htmlspecialchars($table['guests']) . ' guests on ' . htmlspecialchars($table['datee']) . ' at ' . htmlspecialchars($table['times']) . '</li>';
                        }
                    }

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

        <!-- Additional Charts Section -->
        <section class="dashboard-charts-grid">
            <div class="dashboard-chart-card">
                <h3>Order Status Distribution</h3>
                <canvas id="orderStatusChart" width="300" height="300"></canvas>
            </div>
            <div class="dashboard-chart-card">
                <h3>Customer Reviews Rating</h3>
                <canvas id="reviewsBarChart" width="400" height="300"></canvas>
            </div>
        </section>

        <section class="dashboard-charts-grid">
            <div class="dashboard-chart-card wide">
                <h3>Table Reservations - Guest Distribution</h3>
                <canvas id="tableGuestsChart" width="600" height="300"></canvas>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="scripts/script.js"></script>
    <script src="scripts/logout.js"></script>
    <script>
        const menuTypes = <?php echo json_encode($menu_types); ?>;
        const menuCounts = <?php echo json_encode($menu_counts); ?>;
        console.log('Menu Types:', menuTypes);
        console.log('Menu Counts:', menuCounts);

        document.addEventListener('DOMContentLoaded', function() {
            if (window.Chart && menuTypes.length > 0 && menuCounts.length > 0) {
                try {
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
                            responsive: false,
                            maintainAspectRatio: true,
                            aspectRatio: 1,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 25,
                                        usePointStyle: true,
                                        font: {
                                            size: 12
                                        },
                                        boxWidth: 12,
                                        boxHeight: 12,
                                        generateLabels: function(chart) {
                                            const data = chart.data;
                                            if (data.labels.length && data.datasets.length) {
                                                return data.labels.map((label, i) => {
                                                    const meta = chart.getDatasetMeta(0);
                                                    const style = meta.controller.getStyle(i);
                                                    return {
                                                        text: label,
                                                        fillStyle: style.backgroundColor,
                                                        strokeStyle: style.borderColor,
                                                        lineWidth: style.borderWidth,
                                                        pointStyle: 'circle',
                                                        hidden: isNaN(data.datasets[0].data[i]) || meta.data[i].hidden,
                                                        index: i
                                                    };
                                                });
                                            }
                                            return [];
                                        }
                                    }
                                },
                                title: {
                                    display: false
                                }
                            },
                            layout: {
                                padding: {
                                    top: 10,
                                    bottom: 10,
                                    left: 10,
                                    right: 10
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

                if (menuTypes.length === 0) {
                    const chartContainer = document.querySelector('.dashboard-pie-chart');
                    const canvas = document.getElementById('menuTypePieChart');
                    canvas.style.display = 'none';
                    chartContainer.innerHTML += '<p style="text-align: center; color: #666; margin-top: 20px;">No menu items found. Add some menu items to see the distribution.</p>';
                }
            }

            // Order Status Doughnut Chart
            const orderStatuses = <?php echo json_encode($order_statuses); ?>;
            const orderStatusCounts = <?php echo json_encode($order_status_counts); ?>;

            if (orderStatuses.length > 0 && orderStatusCounts.length > 0) {
                try {
                    const orderCtx = document.getElementById('orderStatusChart').getContext('2d');
                    new Chart(orderCtx, {
                        type: 'doughnut',
                        data: {
                            labels: orderStatuses,
                            datasets: [{
                                data: orderStatusCounts,
                                backgroundColor: [
                                    'rgba(67, 233, 123, 0.8)',
                                    'rgba(250, 112, 154, 0.8)',
                                    'rgba(79, 140, 255, 0.8)',
                                    'rgba(255, 159, 64, 0.8)',
                                    'rgba(153, 102, 255, 0.8)'
                                ],
                                borderColor: '#fff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: false,
                            maintainAspectRatio: true,
                            aspectRatio: 1,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        usePointStyle: true,
                                        font: {
                                            size: 11
                                        }
                                    }
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error creating order status chart:', error);
                }
            }

            // Reviews Rating Bar Chart
            const ratingStars = <?php echo json_encode($rating_stars); ?>;
            const ratingCounts = <?php echo json_encode($rating_counts); ?>;

            if (ratingStars.length > 0 && ratingCounts.length > 0) {
                try {
                    const reviewsCtx = document.getElementById('reviewsBarChart').getContext('2d');
                    new Chart(reviewsCtx, {
                        type: 'bar',
                        data: {
                            labels: ratingStars,
                            datasets: [{
                                label: 'Number of Reviews',
                                data: ratingCounts,
                                backgroundColor: 'rgba(255, 205, 86, 0.8)',
                                borderColor: 'rgba(255, 205, 86, 1)',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            aspectRatio: 1.5,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error creating reviews chart:', error);
                }
            }

            // Table Guests Line Chart
            const tableNumbers = <?php echo json_encode($table_numbers); ?>;
            const guestCounts = <?php echo json_encode($guest_counts); ?>;

            if (tableNumbers.length > 0 && guestCounts.length > 0) {
                try {
                    const tableGuestsCtx = document.getElementById('tableGuestsChart').getContext('2d');
                    new Chart(tableGuestsCtx, {
                        type: 'line',
                        data: {
                            labels: tableNumbers,
                            datasets: [{
                                label: 'Total Guests',
                                data: guestCounts,
                                borderColor: 'rgba(48, 207, 208, 1)',
                                backgroundColor: 'rgba(48, 207, 208, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: 'rgba(48, 207, 208, 1)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            aspectRatio: 2.2,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        callback: function(value) {
                                            return value + ' guests';
                                        }
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return 'Guests: ' + context.parsed.y;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error creating table guests chart:', error);
                }
            }
        });

        document.querySelector('.sidebar-logo').addEventListener('click', function() {
            window.location.href = 'index.php';
        });
    </script>
</body>

</html>
<?php
$conn->close();
?>