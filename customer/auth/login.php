<?php
$start_output_buffer = true;
if (!headers_sent() && $start_output_buffer) {
    ob_start();
}
function send_json($conn, $data)
{
    if (ob_get_length() !== false && ob_get_length() > 0) {
        @ob_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data);
    if ($conn && is_object($conn)) {
        @$conn->close();
    }
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "savoredb";
$conn = new mysqli($servername, $username, $password, $dbname);
header('Content-Type: application/json; charset=utf-8');
if ($conn->connect_error) {
    send_json($conn, ['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json($conn, ['success' => false, 'message' => 'Invalid request method']);
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

$errors = [];

if ($email === '') {
    $errors['email'] = 'Email or username is required.';
} else {
    if (strpos($email, '@') !== false) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        }
    } else {
        if (!preg_match('/^(?:emp|adm)-\d+$/i', $email)) {
            $errors['email'] = 'Invalid username format.';
        }
    }
}

if ($password === '') {
    $errors['password'] = 'Password is required.';
}

if (!empty($errors)) {
    send_json($conn, ['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
}
$isHandled = false;

// First, check if credentials match the 'login' table (admin/employee generic login)
if (!$isHandled) {
    $stmtL = $conn->prepare("SELECT id, username, password_hash, created_at FROM login WHERE username = ? LIMIT 1");
    $stmtL->bind_param("s", $email);
    $stmtL->execute();
    $resL = $stmtL->get_result();
    if ($resL && $resL->num_rows > 0) {
        $row = $resL->fetch_assoc();
        $stored = $row['password_hash'];
        $matches = false;
        if (password_verify($password, $stored)) {
            $matches = true;
        } elseif ($password === $stored) {
            $matches = true;
        }

        if ($matches) {
            $username = $row['username'];
            $passwordHashRaw = $row['password_hash'];
            $uname = strtolower($username);
            $ph = strtolower($passwordHashRaw);
            if (preg_match('/^emp-(\d+)$/', $uname, $mU) && strpos($ph, $mU[1]) !== false) {
                session_start();
                $_SESSION['employee_login_id'] = $row['id'];
                $_SESSION['employee_username'] = $username;
                send_json($conn, ['success' => true, 'message' => 'Employee login successful', 'redirect' => '../employee/index.php']);
            }
            if (preg_match('/^adm-(\d+)$/', $uname, $mU2) && strpos($ph, $mU2[1]) !== false) {
                session_start();
                $_SESSION['admin_login_id'] = $row['id'];
                $_SESSION['admin_username'] = $username;
                send_json($conn, ['success' => true, 'message' => 'Admin login successful', 'redirect' => '../admin/index.php']);
            }
            send_json($conn, ['success' => false, 'message' => 'Invalid username or password']);
        } else {
            send_json($conn, ['success' => false, 'message' => 'Invalid username or password']);
        }
    }
}

// Fallback: customer email login (existing behavior)
$stmt = $conn->prepare("SELECT id, name, email, mobile, password, email_verified_at FROM customers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_json($conn, ['success' => false, 'message' => 'Invalid email or password']);
}

$customer = $result->fetch_assoc();
if ($customer['email_verified_at'] === null) {
    send_json($conn, ['success' => false, 'message' => 'Please verify your email address before signing in']);
}
if (!password_verify($password, $customer['password'])) {
    send_json($conn, ['success' => false, 'message' => 'Invalid email or password']);
}
session_start();
$_SESSION['customer_id'] = $customer['id'];
$_SESSION['customer_name'] = $customer['name'];
$_SESSION['customer_email'] = $customer['email'];
$_SESSION['customer_mobile'] = $customer['mobile'];
$rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === 'on';

if ($rememberMe) {
    $cookieData = json_encode([
        'id' => $customer['id'],
        'name' => $customer['name'],
        'email' => $customer['email'],
        'mobile' => $customer['mobile']
    ]);
    setcookie('remember_customer', $cookieData, time() + (30 * 24 * 60 * 60), '/', '', false, true); // HttpOnly for security
} else {
    if (isset($_COOKIE['remember_customer'])) {
        setcookie('remember_customer', '', time() - 3600, '/', '', false, true);
    }
}

send_json($conn, [
    'success' => true,
    'message' => 'Login successful',
    'customer' => [
        'id' => $customer['id'],
        'name' => $customer['name'],
        'email' => $customer['email'],
        'mobile' => $customer['mobile']
    ]
]);
