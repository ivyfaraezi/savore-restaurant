<?php
require_once '../config/database.php';

$message = '';
$messageType = '';
$editMode = false;
$employeeData = null;

if (isset($_GET['edit_id'])) {
    $editMode = true;
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM employee WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $employeeData = $result->fetch_assoc();
    } else {
        $message = 'Employee not found!';
        $messageType = 'error';
        $editMode = false;
    }
    $stmt->close();
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("SELECT name FROM employee WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        $delete_stmt = $conn->prepare("DELETE FROM employee WHERE id = ?");
        $delete_stmt->bind_param("i", $delete_id);

        if ($delete_stmt->execute()) {
            $message = 'Employee "' . htmlspecialchars($employee['name']) . '" deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting employee: ' . $conn->error;
            $messageType = 'error';
        }
        $delete_stmt->close();
    } else {
        $message = 'Employee not found!';
        $messageType = 'error';
    }
    $stmt->close();
    echo "<script>
        setTimeout(function() {
            window.location.href = 'employee.php';
        }, 2000);
    </script>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    $name = trim($_POST['name']);
    $position = $_POST['position'];
    $mobile = $_POST['mobile'];
    $gender = $_POST['gender'];
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
    $photo = null;
    $photoRequired = true;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $uploadedFile = $_FILES['photo'];
        if ($uploadedFile['size'] > 5 * 1024 * 1024) {
            $errors['photo'] = 'Photo file size must be less than 5MB!';
        } else {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $fileType = $uploadedFile['type'];

            if (!in_array($fileType, $allowedTypes)) {
                $errors['photo'] = 'Only JPEG, PNG, and GIF image files are allowed!';
            } else {
                $imageInfo = getimagesize($uploadedFile['tmp_name']);
                if ($imageInfo === false) {
                    $errors['photo'] = 'Invalid image file!';
                } else {
                    $maxAllowed = null;
                    try {
                        $maxRes = $conn->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
                        if ($maxRes) {
                            $row = $maxRes->fetch_assoc();
                            if (isset($row['Value'])) {
                                $maxAllowed = (int)$row['Value'];
                            }
                        }
                    } catch (Exception $e) {
                        error_log('Could not read max_allowed_packet: ' . $e->getMessage());
                    }

                    if ($maxAllowed !== null && $uploadedFile['size'] > max(0, $maxAllowed - 1024)) {
                        $errors['photo'] = 'Uploaded photo is too large for the database server (max_allowed_packet = ' . number_format($maxAllowed) . ' bytes). Please reduce the image size or ask the administrator to increase MySQL\'s max_allowed_packet.';
                    } else {
                        $photo = file_get_contents($uploadedFile['tmp_name']);
                        if ($photo === false) {
                            $errors['photo'] = 'Error reading uploaded file!';
                        }
                    }
                }
            }
        }
    } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] != 4) {
        switch ($_FILES['photo']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors['photo'] = 'Photo file is too large!';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors['photo'] = 'Photo upload was interrupted!';
                break;
            default:
                $errors['photo'] = 'Unknown upload error!';
                break;
        }
        $messageType = 'error';
    } elseif ($employee_id > 0) {
        $photoRequired = false;
    }
    if (empty($name)) {
        $errors['name'] = 'Name is required.';
    } else {
        if (!preg_match("/^[a-zA-Z-' ]+$/", $name)) {
            $errors['name'] = 'Name may only contain letters, spaces, apostrophes and hyphens.';
        }
    }
    if (empty($position)) {
        $errors['position'] = 'Position is required.';
    }
    $mobileDigits = preg_replace('/\D+/', '', $mobile);
    if ($mobileDigits === '') {
        $errors['mobile'] = 'Mobile is required.';
    } elseif (strlen($mobileDigits) !== 11) {
        $errors['mobile'] = 'Mobile number must be exactly 11 digits.';
    } else {
        $mobile = $mobileDigits;
    }
    if (empty($gender)) {
        $errors['gender'] = 'Gender is required.';
    }
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    }
    if (empty($address)) {
        $errors['address'] = 'Address is required.';
    }
    if ($photoRequired && empty($photo)) {
        $errors['photo'] = $errors['photo'] ?? 'Photo is required.';
    }

    if (empty($errors)) {
        if ($employee_id > 0 && isset($_POST['update'])) {
            if ($photo) {
                $stmt = $conn->prepare("UPDATE employee SET name = ?, position = ?, mobile = ?, gender = ?, email = ?, address = ?, photo = ? WHERE id = ?");
                $null = NULL;
                $stmt->bind_param("ssisssbi", $name, $position, $mobile, $gender, $email, $address, $null, $employee_id);
                $stmt->send_long_data(6, $photo);
            } else {
                $stmt = $conn->prepare("UPDATE employee SET name = ?, position = ?, mobile = ?, gender = ?, email = ?, address = ? WHERE id = ?");
                $stmt->bind_param("ssisssi", $name, $position, $mobile, $gender, $email, $address, $employee_id);
            }

            try {
                if ($stmt->execute()) {
                    $message = 'Employee updated successfully!';
                    $messageType = 'success';
                    $_POST = array();
                    $editMode = false;
                    $employeeData = null;
                    echo "<script>window.location.href = 'employee.php';</script>";
                } else {
                    $message = 'Error updating employee: ' . $stmt->error;
                    $messageType = 'error';
                    error_log("Database error in employee update: " . $stmt->error);
                }
            } catch (mysqli_sql_exception $e) {
                $errMsg = $e->getMessage();
                if (stripos($errMsg, 'packet') !== false || stripos($errMsg, 'max_allowed_packet') !== false) {
                    $errors['photo'] = 'Uploaded photo is too large for the database server. Please reduce the image size or contact the administrator.';
                } else {
                    $message = 'Database error while updating employee: ' . htmlspecialchars($errMsg);
                    $messageType = 'error';
                    error_log('Database exception in employee update: ' . $errMsg);
                }
            }
            $stmt->close();
        } else {
            $email_check_query = "SELECT id FROM employee WHERE email = ?";
            if ($employee_id > 0) {
                $email_check_query .= " AND id != ?";
            }
            $check_email = $conn->prepare($email_check_query);
            if ($employee_id > 0) {
                $check_email->bind_param("si", $email, $employee_id);
            } else {
                $check_email->bind_param("s", $email);
            }
            $check_email->execute();
            $result = $check_email->get_result();

            if ($result->num_rows > 0) {
                $message = 'Email already exists!';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("INSERT INTO employee (name, position, mobile, gender, email, address, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $null = NULL;
                $stmt->bind_param("ssisssb", $name, $position, $mobile, $gender, $email, $address, $null);
                $stmt->send_long_data(6, $photo);

                try {
                    if ($stmt->execute()) {
                        $message = 'Employee added successfully!';
                        $messageType = 'success';
                        $_POST = array();
                    } else {
                        $message = 'Error adding employee: ' . $stmt->error;
                        $messageType = 'error';
                        error_log("Database error in employee insert: " . $stmt->error);
                    }
                } catch (mysqli_sql_exception $e) {
                    $errMsg = $e->getMessage();
                    if (stripos($errMsg, 'packet') !== false || stripos($errMsg, 'max_allowed_packet') !== false) {
                        $errors['photo'] = 'Uploaded photo is too large for the database server. Please reduce the image size or contact the administrator.';
                    } else {
                        $message = 'Database error while adding employee: ' . htmlspecialchars($errMsg);
                        $messageType = 'error';
                        error_log('Database exception in employee insert: ' . $errMsg);
                    }
                }
                $stmt->close();
            }
            $check_email->close();
        }
    }
}
$employees_result = $conn->query("SELECT * FROM employee ORDER BY id");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee</title>
    <link rel="stylesheet" href="../styles/modern.css">
    <link rel="stylesheet" href="../styles/employee.css">
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
        <div class="add-employee-container">
            <h2><?php echo $editMode ? 'Edit Employee' : 'Add Employee'; ?></h2>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($editMode && $employeeData): ?>
                    <input type="hidden" name="employee_id" value="<?php echo $employeeData['id']; ?>">
                <?php endif; ?>
                <input type="text" name="name" placeholder="Name"
                    value="<?php echo $editMode && $employeeData ? htmlspecialchars($employeeData['name']) : (isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''); ?>">
                <div class="field-error"><?php echo isset($errors['name']) ? htmlspecialchars($errors['name']) : ''; ?></div>
                <select name="position" id="position">
                    <option value="">Select Position</option>
                    <option value="manager" <?php echo ($editMode && $employeeData && $employeeData['position'] == 'manager') || (isset($_POST['position']) && $_POST['position'] == 'manager') ? 'selected' : ''; ?>>Manager</option>
                    <option value="chef" <?php echo ($editMode && $employeeData && $employeeData['position'] == 'chef') || (isset($_POST['position']) && $_POST['position'] == 'chef') ? 'selected' : ''; ?>>Chef</option>
                    <option value="waiter" <?php echo ($editMode && $employeeData && $employeeData['position'] == 'waiter') || (isset($_POST['position']) && $_POST['position'] == 'waiter') ? 'selected' : ''; ?>>Waiter</option>
                    <option value="bartender" <?php echo ($editMode && $employeeData && $employeeData['position'] == 'bartender') || (isset($_POST['position']) && $_POST['position'] == 'bartender') ? 'selected' : ''; ?>>Bartender</option>
                    <option value="barista" <?php echo ($editMode && $employeeData && $employeeData['position'] == 'barista') || (isset($_POST['position']) && $_POST['position'] == 'barista') ? 'selected' : ''; ?>>Barista</option>
                </select>
                <div class="field-error"><?php echo isset($errors['position']) ? htmlspecialchars($errors['position']) : ''; ?></div>
                <input type="number" name="mobile" placeholder="Mobile Number"
                    value="<?php echo $editMode && $employeeData ? htmlspecialchars($employeeData['mobile']) : (isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''); ?>">
                <div class="field-error"><?php echo isset($errors['mobile']) ? htmlspecialchars($errors['mobile']) : ''; ?></div>
                <select name="gender" id="gender">
                    <option value="">Select Gender</option>
                    <option value="male" <?php echo ($editMode && $employeeData && $employeeData['gender'] == 'male') || (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo ($editMode && $employeeData && $employeeData['gender'] == 'female') || (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                </select>
                <div class="field-error"><?php echo isset($errors['gender']) ? htmlspecialchars($errors['gender']) : ''; ?></div>
                <input type="email" name="email" placeholder="Email"
                    value="<?php echo $editMode && $employeeData ? htmlspecialchars($employeeData['email']) : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''); ?>">
                <div class="field-error"><?php echo isset($errors['email']) ? htmlspecialchars($errors['email']) : ''; ?></div>
                <input type="text" name="address" placeholder="Address"
                    value="<?php echo $editMode && $employeeData ? htmlspecialchars($employeeData['address']) : (isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''); ?>">
                <div class="field-error"><?php echo isset($errors['address']) ? htmlspecialchars($errors['address']) : ''; ?></div>
                <input type="file" name="photo" accept="image/jpeg,image/jpg,image/png,image/gif" <?php echo $editMode ? '' : ''; ?>>
                <div class="field-error"><?php echo isset($errors['photo']) ? htmlspecialchars($errors['photo']) : ''; ?></div>
                <small class="file-description">
                    Accepted formats: JPEG, PNG, GIF (Max size: 1MB)
                </small>
                <?php if ($editMode && $employeeData && $employeeData['photo']): ?>
                    <div class="current-photo-container">
                        <small>Current photo:</small><br>
                        <img src="get_image.php?id=<?php echo $employeeData['id']; ?>&table=employee"
                            alt="Current Photo" class="current-photo-preview">
                        <br><small>Upload a new photo to replace current one (optional)</small>
                    </div>
                <?php endif; ?>
                <br>
                <div class="form-actions">
                    <?php if ($editMode): ?>
                        <button type="submit" name="update">Update</button>
                        <a href="employee.php" class="cancel-btn">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="submit">Add</button>
                        <button type="reset">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="employee-list-container table-wrapper">
            <h2>Employee List</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Mobile</th>
                            <th>Gender</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Photo</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($employees_result && $employees_result->num_rows > 0): ?>
                            <?php while ($employee = $employees_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['id']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($employee['position'])); ?></td>
                                    <td><?php echo htmlspecialchars($employee['mobile']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($employee['gender'])); ?></td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['address']); ?></td>
                                    <td>
                                        <?php if ($employee['photo']): ?>
                                            <img src="get_image.php?id=<?php echo $employee['id']; ?>&table=employee"
                                                alt="<?php echo htmlspecialchars($employee['name']); ?>'s Photo"
                                                class="employee-photo">
                                        <?php else: ?>
                                            No Photo
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="employee.php?edit_id=<?php echo $employee['id']; ?>" class="action-btn edit-btn edit-link">Edit</a>
                                        <button class="action-btn delete-btn" onclick="deleteEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['name'], ENT_QUOTES); ?>')">Delete</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">No employees found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script>
        function deleteEmployee(employeeId, employeeName) {
            if (confirm('Are you sure you want to delete employee "' + employeeName + '"?\n\nThis action cannot be undone.')) {
                window.location.href = 'employee.php?delete_id=' + employeeId;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const messageElement = document.querySelector('.message');
            if (messageElement) {
                setTimeout(function() {
                    messageElement.style.transition = 'opacity 0.3s ease-out';
                    messageElement.style.opacity = '0';
                    setTimeout(function() {
                        messageElement.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>

</html>