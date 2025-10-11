<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "savoredb";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

if ($_POST) {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);
    if (empty($email) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }
    $stmt = $conn->prepare("SELECT id, name, otp, created_at, updated_at FROM customers WHERE email = ? AND email_verified_at IS NULL");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No pending verification found for this email']);
        exit();
    }
    $customer = $result->fetch_assoc();
    if ($customer['otp'] !== $otp) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP code']);
        exit();
    }
    $otp_time = strtotime($customer['updated_at']);
    $current_time = time();
    $time_diff = ($current_time - $otp_time) / 60; 

    if ($time_diff > 10) {
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
        exit();
    }
    $stmt = $conn->prepare("UPDATE customers SET email_verified_at = CURRENT_TIMESTAMP, otp = NULL WHERE email = ?");
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully! You can now sign in.',
            'customer_name' => $customer['name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to verify email. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
