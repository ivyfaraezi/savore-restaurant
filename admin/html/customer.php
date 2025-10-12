<?php
require_once '../config/database.php';

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