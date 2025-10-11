<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../config/config.php';
$email_config = require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    $requiredFields = ['customerName', 'customerEmail', 'orderItems', 'orderTotal'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    if (!filter_var($data['customerEmail'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }
    if (!is_array($data['orderItems']) || count($data['orderItems']) === 0) {
        throw new Exception("Order must contain at least one item");
    }

    $customerName = $data['customerName'];
    $customerEmail = $data['customerEmail'];
    $orderItems = $data['orderItems'];
    $orderTotal = $data['orderTotal'];
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $email_config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $email_config['smtp_username'];
    $mail->Password = $email_config['smtp_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $email_config['smtp_port'];

    $mail->setFrom($email_config['from_email'], $email_config['from_name']);
    $mail->addAddress($customerEmail, $customerName);
    $mail->addReplyTo($email_config['from_email'], $email_config['from_name']);

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = "Order Confirmation - $orderNumber";

    $itemsHtml = '';
    foreach ($orderItems as $item) {
        $itemsHtml .= "
            <tr>
                <td style='padding: 12px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: right;'>{$item['price']}</td>
            </tr>
        ";
    }

    $mail->Body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #181818; color: #f5f5f5; padding: 20px; text-align: center; }
            .header h1 { color: #bfa46b; margin: 0; }
            .content { padding: 20px; background: #f9f9f9; }
            .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .order-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            .order-table th { background: #bfa46b; color: #181818; padding: 12px; text-align: left; }
            .total-row { background: #f0f0f0; font-weight: bold; }
            .footer { background: #181818; color: #f5f5f5; padding: 20px; text-align: center; font-size: 14px; }
            .highlight { color: #bfa46b; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Savoré Restaurant</h1>
                <p>Order Confirmation</p>
            </div>
            
            <div class='content'>
                <h2>Thank you for your order, {$customerName}!</h2>
                <p>We're excited to prepare your delicious meal. Here are your order details:</p>
                
                <div class='order-details'>
                    <h3>Order Information</h3>
                    <p><strong>Order Number:</strong> <span class='highlight'>{$orderNumber}</span></p>
                    <p><strong>Order Date:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                    <p><strong>Customer:</strong> {$customerName}</p>
                    <p><strong>Email:</strong> {$customerEmail}</p>
                    
                    <h3>Order Items</h3>
                    <table class='order-table'>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style='text-align: center;'>Quantity</th>
                                <th style='text-align: right;'>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHtml}
                            <tr class='total-row'>
                                <td colspan='2' style='padding: 12px; text-align: right;'><strong>Total:</strong></td>
                                <td style='padding: 12px; text-align: right;'><strong>{$orderTotal}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #2d6a2d; margin-top: 0;'>What's Next?</h3>
                    <p style='margin-bottom: 0;'>
                        • Your order is being prepared by our skilled chefs<br>
                        • You'll receive updates on your order status<br>
                        • Estimated preparation time: 25-35 minutes<br>
                        • For any questions, contact us at <strong>+880-1992346336, +880-1857048383</strong>
                    </p>
                </div>
                
                <p>Thank you for choosing Savoré Restaurant. We appreciate your business!</p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2025 Savoré Restaurant. All rights reserved.</p>
                <p>123 Savoré Street, Dhaka, Bangladesh | Contact Number: +880-1992346336, +880-1857048383</p>
                <p>Email: savore.2006@gmail.com | Website: www.savore.com</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $plainTextItems = '';
    foreach ($orderItems as $item) {
        $plainTextItems .= "- {$item['name']} (x{$item['quantity']}) - {$item['price']}\n";
    }

    $mail->AltBody = "
Order Confirmation - Savoré Restaurant

Thank you for your order, {$customerName}!

Order Number: {$orderNumber}
Order Date: " . date('F j, Y \a\t g:i A') . "
Customer: {$customerName}
Email: {$customerEmail}

Order Items:
{$plainTextItems}
Total: {$orderTotal}

Your order is being prepared and you'll receive updates on the status.
Estimated preparation time: 25-35 minutes.

For any questions, contact us at <strong>+880-1992346336, +880-1857048383</strong>.

Thank you for choosing Savoré Restaurant!

---
Savoré Restaurant
123 Savoré Street, Dhaka, Bangladesh
Contact Number: +880-1992346336, +880-1857048383
Email: savore.2006@gmail.com
    ";
    $mail->send();
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $customerMobile = isset($data['customerMobile']) ? $data['customerMobile'] : '0000000000';
    $itemsWithQuantities = array_map(function ($item) {
        return $item['name'] . ' (x' . $item['quantity'] . ')';
    }, $orderItems);
    $itemsString = implode(', ', $itemsWithQuantities);
    $totalQuantity = array_sum(array_map(function ($item) {
        return $item['quantity'];
    }, $orderItems));
    $cleanTotal = preg_replace('/[$৳Tk]/', '', $orderTotal);
    $cleanTotal = trim($cleanTotal);
    $totalAmount = (int)round(floatval($cleanTotal));
    $stmt = $pdo->prepare("
        INSERT INTO orders (name, email, mobile, items, quantities, total, statuss) 
        VALUES (?, ?, ?, ?, ?, ?, 'Pending')
    ");
    $mobileInt = is_numeric($customerMobile) ? (int)$customerMobile : 0;
    if ($mobileInt > 2147483647) { 
        $mobileInt = 2147483647;
    }

    $stmt->execute([
        $customerName,
        $customerEmail,
        $mobileInt,
        $itemsString,
        $totalQuantity,
        $totalAmount,
    ]);

    $orderId = $pdo->lastInsertId();
    echo json_encode([
        'success' => true,
        'message' => 'Order placed and confirmation email sent successfully',
        'orderNumber' => $orderNumber,
        'orderId' => $orderId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send confirmation email: ' . $e->getMessage()
    ]);
}
