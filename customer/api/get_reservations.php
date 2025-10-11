<?php
session_start();
require_once '../config/config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['customer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to view reservations'
    ]);
    exit();
}
$conn = getDatabaseConnection();
$customerEmail = $_SESSION['customer_email'];
$stmt = $conn->prepare("
    SELECT id, name, email, mobile, datee, times, guests, tableno, 
           CONCAT(datee, ' ', times) as reservation_datetime
    FROM tables 
    WHERE email = ? 
    ORDER BY datee DESC, times DESC
");
$stmt->bind_param("s", $customerEmail);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $reservationDateTime = new DateTime($row['datee'] . ' ' . $row['times']);
    $now = new DateTime();
    $row['status'] = ($reservationDateTime >= $now) ? 'upcoming' : 'past';
    $reservations[] = $row;
}

$stmt->close();
closeDatabaseConnection($conn);

echo json_encode([
    'success' => true,
    'data' => $reservations,
    'count' => count($reservations)
]);
