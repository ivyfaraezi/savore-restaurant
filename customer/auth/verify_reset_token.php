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

if ($_POST && isset($_POST['token'])) {
    $token = $_POST['token'];

    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'Token is required']);
        exit();
    }
    $stmt = $conn->prepare("SELECT id, name, email, reset_token_expires_at FROM customers WHERE reset_token = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
        exit();
    }

    $customer = $result->fetch_assoc();

    echo json_encode([
        'success' => true,
        'message' => 'Token is valid',
        'customer_name' => $customer['name']
    ]);

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
