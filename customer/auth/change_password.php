<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "savoredb";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to change your password']);
    exit();
}

if ($_POST) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmNewPassword = $_POST['confirm_new_password'];
    $customerId = $_SESSION['customer_id'];
    if (empty($currentPassword) || empty($newPassword) || empty($confirmNewPassword)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    if ($newPassword !== $confirmNewPassword) {
        echo json_encode(['success' => false, 'message' => 'New password and confirm password do not match']);
        exit();
    }
    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
        exit();
    }
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $newPassword)) {
        echo json_encode(['success' => false, 'message' => 'New password must contain at least one letter and one number']);
        exit();
    }
    $stmt = $conn->prepare("SELECT password FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    $customer = $result->fetch_assoc();
    if (!password_verify($currentPassword, $customer['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit();
    }
    if (password_verify($newPassword, $customer['password'])) {
        echo json_encode(['success' => false, 'message' => 'New password must be different from your current password']);
        exit();
    }
    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE customers SET password = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("si", $hashedNewPassword, $customerId);

    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
    }

    $updateStmt->close();
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
