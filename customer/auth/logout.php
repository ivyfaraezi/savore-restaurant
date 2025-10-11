<?php
session_start();
$_SESSION = array();
session_destroy();
if (isset($_COOKIE['remember_customer'])) {
    setcookie('remember_customer', '', time() - 3600, '/', '', false, true);
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);
exit();
