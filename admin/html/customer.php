<?php
require_once '../config/database.php';
require_once '../vendor/autoload.php';
require_once '../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function buildCustomerEmailTemplate($title, $content, $footerNote = '')
{
    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background-color:#f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f4;padding:20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#2d2d2d;padding:30px 20px;text-align:center;">
                            <h1 style="margin:0;color:#d4a649;font-size:28px;font-weight:bold;">🍽️ Savoré Restaurant</h1>
                            <p style="margin:8px 0 0 0;color:#ffffff;font-size:14px;">' . htmlspecialchars($title) . '</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 30px;color:#333333;font-size:15px;line-height:1.6;">
                            ' . $content . '
                        </td>
                    </tr>';

    if (!empty($footerNote)) {
        $html .= '
                    <!-- Additional Info -->
                    <tr>
                        <td style="padding:20px 30px;background-color:#e8f5e9;border-top:2px solid #d4a649;">
                            <p style="margin:0;color:#2e7d32;font-size:14px;"><strong>What\'s Next?</strong></p>
                            <p style="margin:8px 0 0 0;color:#555555;font-size:13px;">' . $footerNote . '</p>
                        </td>
                    </tr>';
    }

    $html .= '
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#2d2d2d;padding:25px 20px;text-align:center;color:#ffffff;font-size:12px;">
                            <p style="margin:0 0 10px 0;color:#d4a649;font-weight:bold;">© ' . date('Y') . ' Savoré Restaurant. All rights reserved.</p>
                            <p style="margin:0;color:#cccccc;">
                                <strong>Phone:</strong> +880-1854048383, +880-1992346336<br>
                                <strong>Email:</strong> savore.2006@gmail.com<br>
                                <strong>Website:</strong> <a href="https://www.savore.com" style="color:#d4a649;text-decoration:none;">www.savore.com</a>
                            </p>
                            <p style="margin:15px 0 0 0;color:#999999;font-size:11px;">
                                We look forward to serving you at Savoré Restaurant!
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    return $html;
}

function sendCustomerEmail($to, $toName, $subject, $htmlBody, $altBody = '')
{
    global $mailConfig;
    try {
        $mail = new PHPMailer(true);
        if (!empty($mailConfig['smtp']) && $mailConfig['smtp']['enabled']) {
            $mail->isSMTP();
            $mail->Host = $mailConfig['smtp']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['smtp']['username'];
            $mail->Password = $mailConfig['smtp']['password'];
            $mail->SMTPSecure = !empty($mailConfig['smtp']['encryption']) ? $mailConfig['smtp']['encryption'] : '';
            $mail->Port = $mailConfig['smtp']['port'];
        }
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($to, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody ?: strip_tags($htmlBody);

        if (!empty($mailConfig['debug'])) {
            $mail->SMTPDebug = 2;
            $debugOutput = '';
            $mail->Debugoutput = function ($str, $level) use (&$debugOutput) {
                $debugOutput .= "[level $level] $str\n";
            };
        }

        $mail->send();

        if (!empty($mailConfig['debug']) && !empty($debugOutput)) {
            error_log("PHPMailer debug output:\n" . $debugOutput);
        }

        return [true, ''];
    } catch (Exception $e) {
        error_log('Mail error (sendCustomerEmail): ' . $e->getMessage());
        return [false, $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $id = intval($_POST['customerId']);
                $errors = [];
                $name = isset($_POST['name']) ? trim($_POST['name']) : '';
                $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
                $email = isset($_POST['email']) ? trim($_POST['email']) : '';
                if ($id <= 0) {
                    $errors['general'] = 'Invalid customer ID.';
                }
                if ($name === '') {
                    $errors['name'] = 'Name is required.';
                } elseif (!preg_match("/^[a-zA-Z-' ]*$/", $name)) {
                    $errors['name'] = 'Only letters and white space allowed.';
                }

                if ($phone === '') {
                    $errors['phone'] = 'Phone is required.';
                } else {
                    $digits = preg_replace('/\D+/', '', $phone);
                    if (strlen($digits) !== 11) {
                        $errors['phone'] = 'Mobile number must be exactly 11 digits.';
                    } else {
                        $phone = $digits;
                    }
                }
                if ($email === '') {
                    $errors['email'] = 'Email is required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Invalid email format.';
                }
                if (!empty($errors)) {
                    $response['errors'] = $errors;
                } else {
                    $sql = "UPDATE customers SET name = ?, mobile = ?, email = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sssi", $name, $phone, $email, $id);

                    if (mysqli_stmt_execute($stmt)) {
                        $response['success'] = true;
                        $response['message'] = 'Customer updated successfully!';

                        // Send notification email to customer
                        if (!empty($email)) {
                            $emailContent = '
                                <p style="margin:0 0 15px 0;">Dear <strong>' . htmlspecialchars($name) . '</strong>,</p>
                                <p style="margin:0 0 15px 0;">Your customer profile has been successfully updated in our system.</p>
                                <div style="background-color:#fff9e6;padding:20px;border-left:4px solid #d4a649;margin:20px 0;border-radius:4px;">
                                    <h3 style="margin:0 0 10px 0;color:#d4a649;font-size:18px;">Updated Information</h3>
                                    <table cellpadding="5" cellspacing="0" border="0" style="width:100%;">
                                        <tr>
                                            <td style="color:#555;padding:5px 0;"><strong>Name:</strong></td>
                                            <td style="color:#333;padding:5px 0;">' . htmlspecialchars($name) . '</td>
                                        </tr>
                                        <tr>
                                            <td style="color:#555;padding:5px 0;"><strong>Email:</strong></td>
                                            <td style="color:#333;padding:5px 0;">' . htmlspecialchars($email) . '</td>
                                        </tr>
                                        <tr>
                                            <td style="color:#555;padding:5px 0;"><strong>Mobile:</strong></td>
                                            <td style="color:#333;padding:5px 0;">' . htmlspecialchars($phone) . '</td>
                                        </tr>
                                    </table>
                                </div>
                                <p style="margin:20px 0 0 0;">If you did not request this change or believe this is a mistake, please contact us immediately.</p>
                                <p style="margin:15px 0 0 0;color:#666;font-weight:bold;">Thank you for being a valued customer!</p>
                            ';
                            $fullHtml = buildCustomerEmailTemplate(
                                'Profile Update Notification',
                                $emailContent,
                                'Your profile information has been updated. If you have any questions or concerns, feel free to contact us at savore.2006@gmail.com or +123-456-7890.'
                            );
                            list($sent, $err) = sendCustomerEmail(
                                $email,
                                $name,
                                'Savoré Restaurant - Your Profile Has Been Updated',
                                $fullHtml,
                                'Hi ' . $name . ",\n\nYour customer profile has been updated in our system.\n\nUpdated Information:\n- Name: " . $name . "\n- Email: " . $email . "\n- Mobile: " . $phone . "\n\nIf you did not request this change, please contact us immediately.\n\nThank you,\nSavoré Restaurant Team"
                            );
                            if (!$sent) {
                                error_log('Failed to send customer update email to ' . $email . ': ' . $err);
                                // Note: We don't fail the whole operation if email fails
                            }
                        }
                    } else {
                        $response['message'] = 'Error updating customer: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                }
                break;

            case 'delete':
                $id = intval($_POST['customerId']);

                if ($id > 0) {
                    $sql = "DELETE FROM customers WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "i", $id);

                    if (mysqli_stmt_execute($stmt)) {
                        $response['success'] = true;
                        $response['message'] = 'Customer deleted successfully!';
                    } else {
                        $response['message'] = 'Error deleting customer: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $response['message'] = 'Invalid customer ID.';
                }
                break;
        }
    }

    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if ($response['success']) {
        header('Location: customer.php?success=' . urlencode($response['message']));
    } else {
        header('Location: customer.php?error=' . urlencode($response['message']));
    }
    exit;
}

$sql = "SELECT id, name, mobile, email FROM customers ORDER BY id ASC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "Error fetching customers: " . mysqli_error($conn);
    $customers = [];
} else {
    $customers = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer</title>
    <link rel="stylesheet" href="../styles/modern.css">
    <link rel="stylesheet" href="../styles/customer.css">
    <script src="../scripts/logout.js"></script>
</head>

<body>
    <nav class="sidebar-container">
        <div class="sidebar-header">
            <a class="savore" href="../index.php">
                <span class="sidebar-logo">🍽️</span>
                <h1>Savoré</h1>
            </a>
        </div>
        <div class="sidebar-links">
            <ul>
                <li><a href="employee.php"><span class="sidebar-icon">👨‍💼</span> Employee</a></li>
                <li><a href="customer.php"><span class="sidebar-icon">🧑‍🤝‍🧑</span> Customer</a></li>
                <li><a href="menu.php"><span class="sidebar-icon">🍲</span> Menu</a></li>
                <li><a href="orders-list.php"><span class="sidebar-icon">🧾</span> Orders & Reservations</a></li>
                <li><a href="reviews.php"><span class="sidebar-icon">📊</span> Reviews</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <button class="sidebar-logout"><span class="sidebar-icon">🚪</span> Logout</button>
        </div>
    </nav>
    <main class="main-content">
        <?php if (isset($_GET['success'])): ?>
            <div class="message success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="message error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div id="messageContainer"></div>

        <div class="add-customer-container">
            <h2 id="formTitle">Update Customer</h2>
            <form id="customerForm">
                <input type="hidden" id="customerId" name="customerId">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="ajax" value="1">
                <input type="text" id="customerName" name="name" placeholder="Name">
                <div class="field-error" id="error-name"></div>
                <input type="tel" id="customerPhone" name="phone" placeholder="Phone">
                <div class="field-error" id="error-phone"></div>
                <input type="text" id="customerEmail" name="email" placeholder="Email">
                <div class="field-error" id="error-email"></div>
                <br>
                <div class="form-actions">
                    <button type="submit" id="updateBtn">Update</button>
                    <button type="button" onclick="resetForm()">Cancel</button>
                </div>
                <div id="formMessageContainer"></div>
            </form>
        </div>
        <div class="customer-list-container table-wrapper">
            <h2>Customer List</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="customerTableBody">
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr id="customer-row-<?php echo $customer['id']; ?>">
                                <td><?php echo htmlspecialchars($customer['id']); ?></td>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="editCustomer(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($customer['mobile']); ?>', '<?php echo htmlspecialchars($customer['email'], ENT_QUOTES); ?>')">Edit</button>
                                    <button class="action-btn delete-btn" onclick="deleteCustomer(<?php echo $customer['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="no-customers-row">
                            <td colspan="5" style="text-align: center;">No customers found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function editCustomer(id, name, mobile, email) {
            document.getElementById('customerId').value = id;
            document.getElementById('customerName').value = name;
            document.getElementById('customerPhone').value = mobile;
            document.getElementById('customerEmail').value = email;
            document.querySelector('.add-customer-container').scrollIntoView({
                behavior: 'smooth'
            });
            document.getElementById('formTitle').textContent = 'Update Customer (ID: ' + id + ')';
        }

        function resetForm() {
            document.getElementById('customerForm').reset();
            document.getElementById('customerId').value = '';
            document.getElementById('formTitle').textContent = 'Update Customer';
            clearMessages();
            clearFormMessages();
        }

        function deleteCustomer(id) {
            if (confirm('Are you sure you want to delete this customer?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('customerId', id);
                formData.append('ajax', '1');
                const row = document.getElementById('customer-row-' + id);
                if (row) {
                    row.classList.add('loading');
                }

                fetch('customer.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (row) {
                                row.remove();
                            }
                            const tbody = document.getElementById('customerTableBody');
                            if (tbody.children.length === 0) {
                                tbody.innerHTML = '<tr id="no-customers-row"><td colspan="5" style="text-align: center;">No customers found</td></tr>';
                            }

                            showMessage(data.message, 'success');
                        } else {
                            showMessage(data.message, 'error');
                            if (row) {
                                row.classList.remove('loading');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showMessage('An error occurred while deleting the customer.', 'error');
                        if (row) {
                            row.classList.remove('loading');
                        }
                    });
            }
        }
        document.getElementById('customerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const customerId = document.getElementById('customerId').value;
            if (!customerId) {
                showFormMessage('Please select a customer to update.', 'error');
                return;
            }
            const formData = new FormData(this);
            const updateBtn = document.getElementById('updateBtn');
            const originalText = updateBtn.textContent;
            updateBtn.textContent = 'Updating...';
            updateBtn.disabled = true;
            clearFormMessages();

            fetch('customer.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        updateTableRow(
                            customerId,
                            document.getElementById('customerName').value,
                            document.getElementById('customerPhone').value,
                            document.getElementById('customerEmail').value
                        );
                        clearFormMessages();
                        clearFieldErrors();
                        resetForm();
                    } else {
                        if (data.errors) {
                            renderFieldErrors(data.errors);
                            clearFormMessages();
                        } else {
                            showFormMessage(data.message || 'An error occurred while updating the customer.', 'error');
                            clearFieldErrors();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred while updating the customer.', 'error');
                })
                .finally(() => {
                    updateBtn.textContent = originalText;
                    updateBtn.disabled = false;
                });
        });

        function updateTableRow(id, name, mobile, email) {
            const row = document.getElementById('customer-row-' + id);
            if (row) {
                const cells = row.getElementsByTagName('td');
                cells[1].textContent = name;
                cells[2].textContent = mobile;
                cells[3].textContent = email;
                const editBtn = row.querySelector('.edit-btn');
                editBtn.setAttribute('onclick', `editCustomer(${id}, '${name.replace(/'/g, "\\'")}', '${mobile}', '${email.replace(/'/g, "\\'")}')`);
            }
        }

        function showMessage(message, type) {
            clearMessages();
            const messageContainer = document.getElementById('messageContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = message;
            messageContainer.appendChild(messageDiv);
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }

        function showFormMessage(message, type) {
            clearFormMessages();
            const container = document.getElementById('formMessageContainer');
            if (!container) return;
            const div = document.createElement('div');
            div.className = `form-message ${type}`;
            div.textContent = message;
            container.appendChild(div);
            setTimeout(() => {
                div.remove();
            }, 6000);
        }

        function clearFormMessages() {
            const container = document.getElementById('formMessageContainer');
            if (container) container.innerHTML = '';
        }

        function renderFieldErrors(errors) {
            ['name', 'phone', 'email'].forEach(function(field) {
                const el = document.getElementById('error-' + field);
                if (el) el.textContent = errors[field] || '';
            });
        }

        function clearFieldErrors() {
            ['name', 'phone', 'email'].forEach(function(field) {
                const el = document.getElementById('error-' + field);
                if (el) el.textContent = '';
            });
        }

        function clearMessages() {
            const messageContainer = document.getElementById('messageContainer');
            messageContainer.innerHTML = '';
        }
        window.addEventListener('load', function() {
            setTimeout(() => {
                const messages = document.querySelectorAll('.message');
                messages.forEach(msg => msg.remove());
                if (window.location.search) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }, 3000);
        });
    </script>
</body>

</html>