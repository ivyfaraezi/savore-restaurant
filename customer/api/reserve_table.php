<?php
session_start();
require_once '../config/config.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to make a reservation',
        'require_login' => true
    ]);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}
$conn = getDatabaseConnection();

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
$datee = isset($_POST['date']) ? trim($_POST['date']) : '';
$times = isset($_POST['time']) ? trim($_POST['time']) : '';
$guests = isset($_POST['guests']) ? intval($_POST['guests']) : 0;

$errors = [];
if (empty($name)) {
    $errors['name'] = 'Name is required.';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
    $errors['name'] = 'Name must contain only alphabets and spaces.';
} elseif (strlen($name) < 5) {
    $errors['name'] = 'Name must be at least 5 characters long.';
} elseif (strlen($name) > 100) {
    $errors['name'] = 'Name must not exceed 100 characters.';
}
if (empty($email)) {
    $errors['email'] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email format.';
}
if (empty($mobile)) {
    $errors['mobile'] = 'Mobile number is required.';
} elseif (!preg_match('/^[0-9]{11}$/', $mobile)) {
    $errors['mobile'] = 'Mobile number must be exactly 11 digits.';
}
if (empty($datee)) {
    $errors['date'] = 'Date is required.';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datee)) {
    $errors['date'] = 'Invalid date format.';
}
if (empty($times)) {
    $errors['time'] = 'Time is required.';
} elseif (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $times)) {
    $errors['time'] = 'Invalid time format.';
}
if ($guests <= 0) {
    $errors['guests'] = 'Number of guests must be at least 1.';
} elseif ($guests > 20) {
    $errors['guests'] = 'Number of guests cannot exceed 20.';
}
if (empty($errors['date']) && empty($errors['time'])) {
    try {
        $reservationDateTime = new DateTime($datee . ' ' . $times);
        $now = new DateTime();

        if ($reservationDateTime <= $now) {
            $errors['date'] = 'Reservation must be for a future date and time.';
        }
    } catch (Exception $e) {
        $errors['date'] = 'Invalid date or time.';
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => 'Please fix the errors below.', 'errors' => $errors]);
    closeDatabaseConnection($conn);
    exit();
}

$stmt = $conn->prepare("SELECT id FROM tables WHERE email = ? AND datee = ? AND times = ?");
$stmt->bind_param("sss", $email, $datee, $times);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'You already have a reservation for this date and time'
    ]);
    $stmt->close();
    closeDatabaseConnection($conn);
    exit();
}
$stmt->close();
$tableno = null;
$stmt = $conn->prepare("INSERT INTO tables (name, email, mobile, datee, times, guests, tableno) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssis", $name, $email, $mobile, $datee, $times, $guests, $tableno);

if ($stmt->execute()) {
    $reservationId = $stmt->insert_id;
    $emailSent = sendPendingReservationEmail($name, $email, $datee, $times, $guests, $reservationId);

    echo json_encode([
        'success' => true,
        'message' => 'Reservation request submitted successfully! Table number will be assigned soon and you will be notified via email.',
        'data' => [
            'reservation_id' => $reservationId,
            'table_number' => null,
            'name' => $name,
            'date' => $datee,
            'time' => $times,
            'guests' => $guests
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create reservation. Please try again.'
    ]);
}

$stmt->close();
closeDatabaseConnection($conn);
function sendPendingReservationEmail($name, $email, $date, $time, $guests, $reservationId)
{
    try {
        $emailConfig = require '../config/email_config.php';
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $emailConfig['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['smtp_username'];
        $mail->Password = $emailConfig['smtp_password'];
        $mail->SMTPSecure = $emailConfig['smtp_secure'];
        $mail->Port = $emailConfig['smtp_port'];
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($email, $name);
        $dateObj = new DateTime($date);
        $formattedDate = $dateObj->format('l, F j, Y');
        $timeObj = DateTime::createFromFormat('H:i', $time);
        $formattedTime = $timeObj->format('g:i A');
        $mail->isHTML(true);
        $mail->Subject = 'Table Reservation Request Received - Savore Restaurant';
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
                .header { background-color: #bfa46b; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: white; padding: 30px; border-radius: 0 0 8px 8px; }
                .reservation-details { background-color: #f5f5f5; padding: 20px; border-left: 4px solid #bfa46b; margin: 20px 0; }
                .detail-row { margin: 10px 0; }
                .detail-label { font-weight: bold; color: #666; }
                .detail-value { color: #333; margin-left: 10px; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
                .pending-notice { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1 style="margin: 0;">Savoré Restaurant</h1>
                    <p style="margin: 10px 0 0 0;">Reservation Request Received</p>
                </div>
                <div class="content">
                    <h2 style="color: #bfa46b;">Hello ' . htmlspecialchars($name) . ',</h2>
                    <p>Thank you for choosing Savoré Restaurant! We have received your table reservation request.</p>
                    
                    <div class="pending-notice">
                        <strong>⏳ Table Assignment Pending</strong>
                        <p style="margin: 10px 0 0 0;">Your reservation request has been received successfully. Our staff will review and assign a table number shortly. You will receive a confirmation email with your table number once it has been assigned.</p>
                    </div>
                    
                    <div class="reservation-details">
                        <h3 style="margin-top: 0; color: #333;">Reservation Details:</h3>
                        <div class="detail-row">
                            <span class="detail-label">Reservation ID:</span>
                            <span class="detail-value">#' . $reservationId . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date:</span>
                            <span class="detail-value">' . $formattedDate . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Time:</span>
                            <span class="detail-value">' . $formattedTime . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Number of Guests:</span>
                            <span class="detail-value">' . $guests . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Table Number:</span>
                            <span class="detail-value"><em>To be assigned soon</em></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value">' . htmlspecialchars($name) . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Contact:</span>
                            <span class="detail-value">' . htmlspecialchars($email) . '</span>
                        </div>
                    </div>
                    
                    <p><strong>What Happens Next?</strong></p>
                    <ul>
                        <li>Our staff will review your reservation request</li>
                        <li>A table number will be assigned based on availability</li>
                        <li>You will receive a confirmation email with your table number</li>
                        <li>Please arrive 15 minutes before your reservation time</li>
                        <li>If you need to cancel or modify your reservation, please contact us</li>
                    </ul>
                    
                    <p>We look forward to serving you at Savoré Restaurant!</p>
                    
                    <div style="text-align: center;">
                        <p style="margin: 30px 0 10px 0;"><strong>Contact Us:</strong></p>
                        <p style="margin: 5px 0;">📍 123 Savoré Street, Dhaka, Bangladesh</p>
                        <p style="margin: 5px 0;">📞 +880-1992346336</p>
                        <p style="margin: 5px 0;">📞 +880-1857048383</p>
                        <p style="margin: 5px 0;">📧 savore.2006@gmail.com</p>
                    </div>
                </div>
                <div class="footer">
                    <p>This is an automated confirmation email. Please do not reply to this email.</p>
                    <p>&copy; 2025 Savoré Restaurant. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ';

        $mail->AltBody = "Hello $name,\n\n"
            . "Your table reservation request at Savoré Restaurant has been received!\n\n"
            . "TABLE ASSIGNMENT PENDING\n"
            . "Your reservation request has been received successfully. Our staff will review and assign a table number shortly. You will receive another email once your table is assigned.\n\n"
            . "Reservation Details:\n"
            . "Reservation ID: #$reservationId\n"
            . "Date: $formattedDate\n"
            . "Time: $formattedTime\n"
            . "Guests: $guests\n"
            . "Table Number: To be assigned soon\n\n"
            . "What Happens Next?\n"
            . "1. Our staff will review your reservation request\n"
            . "2. A table will be assigned based on availability\n"
            . "3. You will receive a confirmation email with your table number\n"
            . "4. If you have any questions, please contact us\n\n"
            . "We look forward to serving you!\n\n"
            . "Savoré Restaurant\n"
            . "123 Savoré Street, Dhaka, Bangladesh\n"
            . "Contact Number: +880-1992346336 , +880-1857048383\n"
            . "Email: savore.2006@gmail.com";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}
