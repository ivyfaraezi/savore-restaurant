<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "savoredb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

if ($_POST) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $cpassword = isset($_POST['cpassword']) ? $_POST['cpassword'] : '';
    $errors = [];

    if (empty($name)) {
        $errors['name'] = 'Name is required.';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $errors['name'] = 'Name must contain only alphabets and spaces.';
    } elseif (strlen($name) < 5) {
        $errors['name'] = 'Name must be at least 5 characters long.';
    } elseif (strlen($name) > 50) {
        $errors['name'] = 'Name must not exceed 50 characters.';
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
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long.';
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $password)) {
        $errors['password'] = 'Password must contain at least one letter and one number.';
    }
    if (empty($cpassword)) {
        $errors['cpassword'] = 'Please confirm your password.';
    } elseif ($password !== $cpassword) {
        $errors['cpassword'] = 'Passwords do not match.';
    }
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => 'Please fix the errors below.', 'errors' => $errors]);
        exit();
    }
    $stmt = $conn->prepare("SELECT id, email_verified_at FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['email_verified_at'] !== null) {
            echo json_encode(['success' => false, 'message' => 'Email already registered and verified']);
            exit();
        }
    }
    $otp = sprintf("%06d", mt_rand(1, 999999));
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $hashed_cpassword = password_hash($cpassword, PASSWORD_DEFAULT);
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE customers SET name = ?, mobile = ?, password = ?, cpassword = ?, otp = ?, updated_at = CURRENT_TIMESTAMP WHERE email = ?");
        $stmt->bind_param("ssssss", $name, $mobile, $hashed_password, $hashed_cpassword, $otp, $email);
    } else {
        $stmt = $conn->prepare("INSERT INTO customers (name, email, mobile, password, cpassword, otp) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $mobile, $hashed_password, $hashed_cpassword, $otp);
    }
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        exit();
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'savore.2006@gmail.com';
        $mail->Password   = 'sxqh eyis wzuf dami';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('savore.2006@gmail.com', 'Savoré Restaurant');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Email Verification - Savoré Restaurant';
        $mail->Body    = '
        <html>
        <head>
            <style>
                .email-container {
                    font-family: Arial, sans-serif;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f9f9f9;
                }
                .email-header {
                    background-color: #2c3e50;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 10px 10px 0 0;
                }
                .email-body {
                    background-color: white;
                    padding: 30px;
                    border-radius: 0 0 10px 10px;
                }
                .otp-code {
                    background-color: #e74c3c;
                    color: white;
                    font-size: 32px;
                    font-weight: bold;
                    padding: 15px 30px;
                    text-align: center;
                    border-radius: 5px;
                    margin: 20px 0;
                    letter-spacing: 5px;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    color: #666;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1>Welcome to Savoré Restaurant!</h1>
                </div>
                <div class="email-body">
                    <h2>Hello ' . htmlspecialchars($name) . ',</h2>
                    <p>Thank you for registering with Savoré Restaurant! To complete your registration, please verify your email address using the OTP code below:</p>
                    
                    <div class="otp-code">
                        ' . $otp . '
                    </div>
                    
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>This OTP is valid for 10 minutes only</li>
                        <li>Please do not share this code with anyone</li>
                        <li>If you did not register for this account, please ignore this email</li>
                    </ul>
                    
                    <p>Once verified, you will be able to:</p>
                    <ul>
                        <li>Browse our delicious menu</li>
                        <li>Place orders online</li>
                        <li>Reserve tables</li>
                        <li>Leave reviews</li>
                    </ul>
                    
                    <p>We look forward to serving you!</p>
                    <p><strong>The Savoré Restaurant Team</strong></p>
                </div>
                <div class="footer">
                    <p>© 2025 Savoré Restaurant. All rights reserved.</p>
                    <p>If you have any questions, contact us at <strong>+880-1992346336, +880-1857048383</strong></p>
                    <p>Email: <strong>savore.2006@gmail.com</strong></p>
                </div>
            </div>
        </body>
        </html>';

        $mail->AltBody = "Hello $name,\n\nThank you for registering with Savoré Restaurant!\n\nYour OTP verification code is: $otp\n\nThis code is valid for 10 minutes only.\n\nBest regards,\nSavoré Restaurant Team";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'OTP sent to your email address. Please check your inbox.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
