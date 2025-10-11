<?php
header('Content-Type: application/json');
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "savoredb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_POST && isset($_POST['token']) && isset($_POST['new_password'])) {
    $token = $_POST['token'];
    $newPassword = $_POST['new_password'];

    if (empty($token) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Token and new password are required']);
        exit();
    }
    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        exit();
    }

    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Password must contain at least one letter and one number']);
        exit();
    }
    $stmt = $conn->prepare("SELECT id, name, email FROM customers WHERE reset_token = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
        exit();
    }
    $customer = $result->fetch_assoc();
    $customerId = $customer['id'];
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE customers SET password = ?, reset_token = NULL, reset_token_expires_at = NULL, updated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("si", $hashedPassword, $customerId);

    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Password has been reset successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }

    $updateStmt->close();
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
