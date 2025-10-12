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

// --- send login notification email (non-blocking) ---
// Capture client IP and user agent, then attempt to send an email to the customer.
// Any errors are caught and logged so they don't affect the login flow.
try {
    function get_client_ip()
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $ips = explode(',', $_SERVER[$k]);
                // return first valid IP after trimming
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        return 'Unknown';
    }

    $clientIp = get_client_ip();
    if ($clientIp === '::1' || $clientIp === '0:0:0:0:0:0:0:1') {
        $clientIp = '127.0.0.1';
    }
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';

    function summarize_user_agent($ua)
    {
        if (!$ua || $ua === 'Unknown') {
            return 'Unknown';
        }
        $browser = 'Unknown browser';
        $os = 'Unknown OS';

        // Browser detection (order matters)
        if (stripos($ua, 'Edg/') !== false || stripos($ua, 'Edge/') !== false) {
            $browser = 'Edge';
        } elseif (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) {
            $browser = 'Opera';
        } elseif (stripos($ua, 'Chrome/') !== false && stripos($ua, 'Chromium') === false) {
            $browser = 'Chrome';
        } elseif (stripos($ua, 'CriOS') !== false) {
            $browser = 'Chrome (iOS)';
        } elseif (stripos($ua, 'Firefox/') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($ua, 'Safari/') !== false && stripos($ua, 'Chrome/') === false) {
            $browser = 'Safari';
        }

        // OS detection
        if (stripos($ua, 'Windows NT 10.0') !== false) {
            $os = 'Windows 10';
        } elseif (stripos($ua, 'Windows NT 6.1') !== false) {
            $os = 'Windows 7';
        } elseif (stripos($ua, 'Windows NT 6.2') !== false) {
            $os = 'Windows 8';
        } elseif (stripos($ua, 'Mac OS X') !== false || stripos($ua, 'Macintosh') !== false) {
            $os = 'macOS';
        } elseif (stripos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
            $os = 'iOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        }

        return $browser . ' on ' . $os;
    }
    $friendlyUA = summarize_user_agent($userAgent);
    $time = date('Y-m-d H:i:s');

    $email_config = require_once __DIR__ . '/../config/email_config.php';
    require_once __DIR__ . '/../vendor/autoload.php';

    if (!empty($customer['email'])) {
        $toEmail = $customer['email'];
        $toName = $customer['name'];

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $email_config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $email_config['smtp_username'];
        $mail->Password = $email_config['smtp_password'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $email_config['smtp_port'];

        $mail->setFrom($email_config['from_email'], $email_config['from_name']);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo($email_config['from_email'], $email_config['from_name']);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'New sign-in to your Savoré account';

        $appUrl = isset($email_config['app_url']) ? rtrim($email_config['app_url'], '/') : '';

        $mailBody = "<!doctype html>";
        $mailBody .= "<html><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">";
        $mailBody .= "<style>body{font-family:Arial,Helvetica,sans-serif;color:#333} .container{max-width:600px;margin:0 auto;padding:20px} .header{background:#1f2937;color:#fff;padding:20px;text-align:center;border-radius:6px 6px 0 0} .content{background:#fff;padding:24px;border:1px solid #e6e6e6;border-top:0;border-radius:0 0 6px 6px} .btn{display:inline-block;padding:10px 16px;background:#bfa46b;color:#111;text-decoration:none;border-radius:6px} .muted{color:#6b7280;font-size:13px}</style></head><body>";
        $mailBody .= "<div class=\"container\">";
        $mailBody .= "<div class=\"header\"><h2 style=\"margin:0;font-weight:600;\">Savoré Restaurant</h2><div style=\"font-size:14px;margin-top:6px;opacity:.9\">Security notification</div></div>";
        $mailBody .= "<div class=\"content\">";
        $mailBody .= "<p>Hi " . htmlspecialchars($toName) . ",</p>";
        $mailBody .= "<p>We noticed a sign-in to your Savoré account. If this was you, there's nothing to do. If you don't recognize this activity, please secure your account immediately.</p>";
        $mailBody .= "<table style=\"width:100%;margin:12px 0;border-collapse:collapse;font-size:14px\">";
        $mailBody .= "<tr><td style=\"padding:8px 0;vertical-align:top;width:170px;color:#111;font-weight:600\">IP address</td><td style=\"padding:8px 0;\">" . htmlspecialchars($clientIp) . "</td></tr>";
        $mailBody .= "<tr><td style=\"padding:8px 0;vertical-align:top;font-weight:600\">Time</td><td style=\"padding:8px 0;\">" . htmlspecialchars($time) . "</td></tr>";
        $mailBody .= "<tr><td style=\"padding:8px 0;vertical-align:top;font-weight:600\">Device / Browser</td><td style=\"padding:8px 0;\">" . htmlspecialchars($friendlyUA) . "</td></tr>";
        $mailBody .= "</table>";
        $mailBody .= "<p class=\"muted\">Full user agent: <span style=\"display:block;margin-top:6px;word-break:break-all;\">" . htmlspecialchars($userAgent) . "</span></p>";
        $mailBody .= "<p style=\"margin-top:18px;\">If you need help, contact our support at <strong>savore.2006@gmail.com</strong>.</p>";
        $mailBody .= "<p style=\"margin-top:18px;font-size:13px;color:#6b7280\">This is an automated message — please do not reply to this email.</p>";
        $mailBody .= "</div></div></body></html>";

        $plain = "Hi $toName\n\nWe noticed a sign-in to your Savoré account.\n\nIP address: $clientIp\nTime: $time\nDevice/Browser: $friendlyUA\n\nIf you don't recognize this activity, please secure your account.";
        $plain .= "\n\nFull user agent: $userAgent\n\nIf you need help, contact support at savore.2006@gmail.com\n\n— Savoré Restaurant";

        $mail->Body = $mailBody;
        $mail->AltBody = $plain;

        try {
            $mail->send();
        } catch (Exception $e) {
            error_log('Login notification email failed: ' . $e->getMessage());
        }
    }
} catch (Exception $e) {
    // non-fatal: log and continue
    error_log('Login notification error: ' . $e->getMessage());
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
