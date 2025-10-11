<?php
session_start();
require_once '../config/config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['customer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to cancel reservation'
    ]);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}
$conn = getDatabaseConnection();

$reservationId = intval($_POST['reservation_id'] ?? 0);
$customerEmail = $_SESSION['customer_email'];

if ($reservationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation ID']);
    closeDatabaseConnection($conn);
    exit();
}

$stmt = $conn->prepare("SELECT id, datee, times FROM tables WHERE id = ? AND email = ?");
$stmt->bind_param("is", $reservationId, $customerEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Reservation not found']);
    $stmt->close();
    closeDatabaseConnection($conn);
    exit();
}

$reservation = $result->fetch_assoc();
$stmt->close();
$reservationDateTime = new DateTime($reservation['datee'] . ' ' . $reservation['times']);
$now = new DateTime();

if ($reservationDateTime < $now) {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel past reservations']);
    closeDatabaseConnection($conn);
    exit();
}
$stmt = $conn->prepare("DELETE FROM tables WHERE id = ? AND email = ?");
$stmt->bind_param("is", $reservationId, $customerEmail);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Reservation cancelled successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to cancel reservation'
    ]);
}

$stmt->close();
closeDatabaseConnection($conn);
