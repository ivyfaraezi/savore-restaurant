<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "savoredb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

if ($_POST) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $errors = [];
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    }
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => 'Please fix the errors below.', 'errors' => $errors]);
        exit();
    }
    $stmt = $conn->prepare("SELECT id, name, email, email_verified_at FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'If an account with this email exists, a password reset link has been sent to your email address.'
        ]);
        exit();
    }

    $customer = $result->fetch_assoc();
    if ($customer['email_verified_at'] === null) {
        echo json_encode(['success' => false, 'message' => 'Please verify your email address first before requesting a password reset']);
        exit();
    }
    $resetToken = bin2hex(random_bytes(32));
    $checkColumns = $conn->query("SHOW COLUMNS FROM customers LIKE 'reset_token'");
    if ($checkColumns === false) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    if ($checkColumns->num_rows === 0) {
        $addColumn1 = $conn->query("ALTER TABLE customers ADD COLUMN reset_token VARCHAR(255) NULL DEFAULT NULL");
        $addColumn2 = $conn->query("ALTER TABLE customers ADD COLUMN reset_token_expires_at DATETIME NULL DEFAULT NULL");

        if (!$addColumn1 || !$addColumn2) {
            echo json_encode(['success' => false, 'message' => 'Failed to create required database columns: ' . $conn->error]);
            exit();
        }
    }
    $updateStmt = $conn->prepare("UPDATE customers SET reset_token = ?, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 4 HOUR) WHERE id = ?");
    $updateStmt->bind_param("si", $resetToken, $customer['id']);

    if (!$updateStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate reset token. Please try again.']);
        exit();
    }
    $emailConfigFile = '../config/email_config.php';
    if (!file_exists($emailConfigFile)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email service is not configured. Please contact the administrator.',
            'debug_info' => [
                'error' => 'Email configuration file not found',
                'instruction' => 'Copy email_config_example.php to email_config.php and configure your SMTP settings',
                'token_for_testing' => $resetToken,
                'reset_link_for_testing' => "http://localhost/savore-restaurant/customer/pages/reset_password.php?token=" . $resetToken
            ]
        ]);
        exit();
    }
    $emailConfig = include $emailConfigFile;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $emailConfig['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['smtp_username'];
        $mail->Password   = $emailConfig['smtp_password'];
        $mail->SMTPSecure = $emailConfig['smtp_secure'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $emailConfig['smtp_port'];
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($email, $customer['name']);
        $resetLink = $emailConfig['app_url'] . "/pages/reset_password.php?token=" . $resetToken;
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Password Reset Request - Savoré Restaurant';

        $htmlBody = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; background: #181818; color: #bfa46b; padding: 20px; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0; font-family: Georgia, serif;'>Savoré Restaurant</h1>
                </div>
                
                <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #ddd;'>
                    <h2 style='color: #181818; margin-top: 0;'>Password Reset Request</h2>
                    
                    <p>Hello <strong>" . htmlspecialchars($customer['name']) . "</strong>,</p>
                    
                    <p>You requested a password reset for your Savoré Restaurant account. Click the button below to reset your password:</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . $resetLink . "' style='background: #bfa46b; color: #181818; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Reset Password</a>
                    </div>
                    
                    <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; background: #f0f0f0; padding: 10px; border-radius: 5px;'>" . $resetLink . "</p>
                    
                    <p><strong>Important:</strong> This link will expire in 1 hour for security reasons.</p>
                    
                    <p>If you didn't request this password reset, please ignore this email. Your password will remain unchanged.</p>
                    
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                    
                    <p style='color: #666; font-size: 14px;'>
                        Best regards,<br>
                        The Savoré Restaurant Team
                    </p>
                </div>
            </div>
        </body>
        </html>";

        $textBody = "Hello " . $customer['name'] . ",\n\n";
        $textBody .= "You requested a password reset for your Savoré Restaurant account.\n\n";
        $textBody .= "Click the link below to reset your password:\n";
        $textBody .= $resetLink . "\n\n";
        $textBody .= "This link will expire in 1 hour for security reasons.\n\n";
        $textBody .= "If you didn't request this password reset, please ignore this email.\n\n";
        $textBody .= "Best regards,\nThe Savoré Restaurant Team";

        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody;

        $mail->send();

        echo json_encode([
            'success' => true,
            'message' => 'A password reset link has been sent to your email address. Please check your inbox and spam folder.'
        ]);
    } catch (Exception $e) {
        error_log("Password reset email failed: " . $mail->ErrorInfo);
        echo json_encode([
            'success' => true,
            'message' => 'If an account with this email exists, a password reset link has been sent to your email address.',
            'debug_error' => 'Email sending failed: ' . $mail->ErrorInfo 
        ]);
    }

    $updateStmt->close();
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
