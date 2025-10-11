<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (isset($_SESSION['customer_id'])) {
    echo json_encode([
        'success' => true,
        'isLoggedIn' => true,
        'customer' => [
            'id' => $_SESSION['customer_id'],
            'name' => $_SESSION['customer_name'],
            'email' => $_SESSION['customer_email'],
            'mobile' => $_SESSION['customer_mobile']
        ]
    ]);
} else {
    echo json_encode([
        'success' => true,
        'isLoggedIn' => false,
        'customer' => null
    ]);
}
exit();
