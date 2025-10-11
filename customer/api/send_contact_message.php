<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../vendor/autoload.php';
require_once '../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode([
            'success' => false,
            'message' => 'Name, email, and message are required fields.'
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid email address.'
        ]);
        exit;
    }

    $email_config = require_once '../config/email_config.php';
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $email_config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $email_config['smtp_username'];
    $mail->Password   = $email_config['smtp_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $email_config['smtp_port'];
    $mail->setFrom($email_config['from_email'], $email_config['from_name']);
    $mail->addAddress($email_config['from_email'], $email_config['from_name']);
    $mail->addReplyTo($email, $name);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = !empty($subject) ? "Contact Form: " . $subject : "New Contact Message from " . $name;
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #bfa46b; color: white; padding: 20px; text-align: center; }
            .content { background-color: #f9f9f9; padding: 20px; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #555; }
            .value { margin-top: 5px; padding: 10px; background-color: white; border-left: 4px solid #bfa46b; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Contact Message - Savoré Restaurant</h2>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='label'>From:</div>
                    <div class='value'>" . htmlspecialchars($name) . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Email:</div>
                    <div class='value'>" . htmlspecialchars($email) . "</div>
                </div>
                
                " . (!empty($phone) ? "
                <div class='field'>
                    <div class='label'>Phone:</div>
                    <div class='value'>" . htmlspecialchars($phone) . "</div>
                </div>
                " : "") . "
                
                " . (!empty($subject) ? "
                <div class='field'>
                    <div class='label'>Subject:</div>
                    <div class='value'>" . htmlspecialchars($subject) . "</div>
                </div>
                " : "") . "
                
                <div class='field'>
                    <div class='label'>Message:</div>
                    <div class='value'>" . nl2br(htmlspecialchars($message)) . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Sent:</div>
                    <div class='value'>" . date('F j, Y \a\t g:i A') . "</div>
                </div>
            </div>
            <div class='footer'>
                <p>This message was sent through the Savoré Restaurant contact form.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $mail->Body = $htmlBody;
    $textBody = "New Contact Message from Savoré Restaurant\n\n";
    $textBody .= "From: " . $name . "\n";
    $textBody .= "Email: " . $email . "\n";
    if (!empty($phone)) $textBody .= "Phone: " . $phone . "\n";
    if (!empty($subject)) $textBody .= "Subject: " . $subject . "\n";
    $textBody .= "Message:\n" . $message . "\n\n";
    $textBody .= "Sent: " . date('F j, Y \a\t g:i A') . "\n";

    $mail->AltBody = $textBody;
    $mail->send();
    try {
        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}",
            $db_config['username'],
            $db_config['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO messages (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $phone, $subject, $message]);
    } catch (Exception $e) {
        error_log("Failed to store contact message in database: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you soon.'
    ]);
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    error_log("Contact form error trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => 'Check server error logs for details'
    ]);
}
