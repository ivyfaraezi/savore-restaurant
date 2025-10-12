<?php
require_once '../config/database.php';

$sql = "SELECT id, name, stars, message, created_at FROM reviews ORDER BY created_at DESC";
$result = $conn->query($sql);

function displayStars($rating)
{
    $stars = '';
    $rating = max(1, min(5, intval($rating)));

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '⭐';
        }
    }
    return $stars;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews</title>
    <link rel="stylesheet" href="../styles/modern.css">
    <link rel="stylesheet" href="../styles/reviews.css">
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
        <section class="reviews-list-container">
            <h1>Customer Reviews</h1>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . displayStars($row['stars']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['message']) . "</td>";
                                echo "<td>" . date('M j, Y', strtotime($row['created_at'])) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align: center; padding: 20px;'>No reviews found</td></tr>";
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