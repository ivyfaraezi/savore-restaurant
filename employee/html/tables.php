<?php
require_once '../config/database.php';
require_once '../config/email_service.php';

$errors = [];

function getTableCapacity($tableId)
{
    $tablesFile = '../config/tables.json';
    if (file_exists($tablesFile)) {
        $tablesData = json_decode(file_get_contents($tablesFile), true);
        if ($tablesData && isset($tablesData['tables'])) {
            foreach ($tablesData['tables'] as $table) {
                if ($table['id'] == $tableId) {
                    return $table['capacity'];
                }
            }
        }
    }
    return 4;
}

function validateCapacity($guests, $tableNo)
{
    $capacity = getTableCapacity($tableNo);
    return $guests <= $capacity ? true : $capacity;
}

function validateDateTime($date, $time)
{
    $currentDateTime = new DateTime();
    $currentDate = $currentDateTime->format('Y-m-d');
    $currentTime = $currentDateTime->format('H:i');

    $bookingDate = DateTime::createFromFormat('Y-m-d', $date);
    if (!$bookingDate || $bookingDate->format('Y-m-d') !== $date) {
        return "Invalid date format.";
    }

    if ($date < $currentDate) {
        return "You cannot book a table for a past date. Please select today's date or a future date.";
    }

    if ($date === $currentDate) {
        $bookingTime = DateTime::createFromFormat('H:i', $time);
        $currentTimeObj = DateTime::createFromFormat('H:i', $currentTime);

        if (!$bookingTime || $bookingTime <= $currentTimeObj) {
            return "You cannot book a table for a past time. Please select a future time.";
        }
    }

    return true;
}

function validateTableAvailability($conn, $tableNo, $date, $time, $excludeId = null)
{
    $bookingTime = DateTime::createFromFormat('H:i', $time);
    if (!$bookingTime) {
        return "Invalid time format.";
    }
    $bookingStart = $bookingTime->format('H:i');
    $bookingEnd = $bookingTime->add(new DateInterval('PT1H'))->format('H:i');
    $sql = "SELECT id, name, times FROM tables WHERE tableno = ? AND datee = ?";
    $params = [$tableNo, $date];
    $types = "is";
    if ($excludeId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
        $types .= "i";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $existingTime = DateTime::createFromFormat('H:i', $row['times']);
        if (!$existingTime) continue;
        $existingStart = $existingTime->format('H:i');
        $existingEnd = $existingTime->add(new DateInterval('PT1H'))->format('H:i');
        $existingTime = DateTime::createFromFormat('H:i', $row['times']);
        $newBookingTime = DateTime::createFromFormat('H:i', $time);
        if ($newBookingTime >= $existingTime && $newBookingTime < DateTime::createFromFormat('H:i', $existingEnd)) {
            $stmt->close();
            return "Table {$tableNo} is already reserved by {$row['name']} from {$existingStart} to {$existingEnd} on {$date}. Please select a different time.";
        }

        $newBookingEnd = DateTime::createFromFormat('H:i', $bookingEnd);
        if ($newBookingEnd > $existingTime && $newBookingEnd <= DateTime::createFromFormat('H:i', $existingEnd)) {
            $stmt->close();
            return "Table {$tableNo} is already reserved by {$row['name']} from {$existingStart} to {$existingEnd} on {$date}. Please select a different time.";
        }

        if ($newBookingTime <= $existingTime && $newBookingEnd >= DateTime::createFromFormat('H:i', $existingEnd)) {
            $stmt->close();
            return "Table {$tableNo} is already reserved by {$row['name']} from {$existingStart} to {$existingEnd} on {$date}. Please select a different time.";
        }
    }

    $stmt->close();
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array('success' => false, 'message' => '');
    $errors = [];

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'book') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $mobile = trim($_POST['phone']);
            $datee = $_POST['date'];
            $times = $_POST['time'];
            $guests = (int)$_POST['guests'];
            $tableno = (int)$_POST['table-no'];
            if (empty($name)) {
                $errors['name'] = 'Name is required.';
            } else {
                if (!preg_match("/^[a-zA-Z-' ]+$/", $name)) {
                    $errors['name'] = 'Name may only contain letters, spaces, apostrophes and hyphens.';
                }
            }
            if ($email === '') {
                $errors['email'] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format.';
            }
            $mobileDigits = preg_replace('/\D+/', '', $mobile);
            if ($mobileDigits === '') {
                $errors['mobile'] = 'Mobile is required.';
            } elseif (strlen($mobileDigits) !== 11) {
                $errors['mobile'] = 'Mobile number must be exactly 11 digits.';
            } else {
                $mobile = $mobileDigits;
            }
            if (empty($datee)) {
                $errors['date'] = 'Date is required.';
            }
            if (empty($times)) {
                $errors['time'] = 'Time is required.';
            }
            if ($guests <= 0) {
                $errors['guests'] = 'Number of guests must be > 0.';
            }
            if ($tableno <= 0) {
                $errors['tableno'] = 'Please select a valid table number.';
            }

            if (empty($errors)) {
                $dateTimeValidation = validateDateTime($datee, $times);
                if ($dateTimeValidation !== true) {
                    $response['message'] = "Error: " . $dateTimeValidation;
                } else {
                    $capacityValidation = validateCapacity($guests, $tableno);
                    if ($capacityValidation !== true) {
                        $response['message'] = "Error: Selected table can accommodate only {$capacityValidation} guests, but you entered {$guests} guests. Please select a larger table or reduce the number of guests.";
                    } else {
                        $availabilityValidation = validateTableAvailability($conn, $tableno, $datee, $times);
                        if ($availabilityValidation !== true) {
                            $response['message'] = "Error: " . $availabilityValidation;
                        } else {
                            $stmt = $conn->prepare("INSERT INTO tables (name, email, mobile, datee, times, guests, tableno) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("sssssii", $name, $email, $mobile, $datee, $times, $guests, $tableno);

                            if ($stmt->execute()) {
                                $reservationId = $conn->insert_id;
                                $reservationDetails = [
                                    'email' => $email,
                                    'mobile' => $mobile,
                                    'date' => $datee,
                                    'time' => $times,
                                    'guests' => $guests,
                                    'tableno' => $tableno,
                                    'capacity' => getTableCapacity($tableno)
                                ];
                                $emailService = new EmailService();
                                $emailResult = $emailService->sendTableReservationConfirmation($email, $name, $reservationId, $reservationDetails);

                                if ($emailResult['success']) {
                                    $response['success'] = true;
                                    $response['message'] = 'Table booked successfully! A confirmation email has been sent to your email address.';
                                } else {
                                    $response['success'] = true;
                                    $response['message'] = 'Table booked successfully! However, we could not send the confirmation email. Please contact us if you need a copy.';
                                    error_log("Table reservation email failed for reservation #$reservationId: " . $emailResult['message']);
                                }
                            } else {
                                $response['message'] = 'Error booking table: ' . $conn->error;
                            }
                            $stmt->close();
                        }
                    }
                }
            } else {
                $response['errors'] = $errors;
                $response['message'] = 'Please correct the errors below.';
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM tables WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Reservation deleted successfully!';
            } else {
                $response['message'] = 'Error deleting reservation: ' . $conn->error;
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'update') {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $mobile = trim($_POST['phone']);
            $datee = $_POST['date'];
            $times = $_POST['time'];
            $guests = (int)$_POST['guests'];
            $tableno = (int)$_POST['table-no'];
            error_log("Update request - ID: $id, Name: $name, Email: $email");
            if ($id <= 0) {
                $errors['id'] = 'Invalid reservation ID.';
            }
            if (empty($name)) {
                $errors['name'] = 'Name is required.';
            } else {
                if (!preg_match("/^[a-zA-Z-' ]+$/", $name)) {
                    $errors['name'] = 'Name may only contain letters, spaces, apostrophes and hyphens.';
                }
            }
            if ($email === '') {
                $errors['email'] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format.';
            }
            $mobileDigits = preg_replace('/\D+/', '', $mobile);
            if ($mobileDigits === '') {
                $errors['mobile'] = 'Mobile is required.';
            } elseif (strlen($mobileDigits) !== 11) {
                $errors['mobile'] = 'Mobile number must be exactly 11 digits.';
            } else {
                $mobile = $mobileDigits;
            }
            if (empty($datee)) {
                $errors['date'] = 'Date is required.';
            }
            if (empty($times)) {
                $errors['time'] = 'Time is required.';
            }
            if ($guests <= 0) {
                $errors['guests'] = 'Number of guests must be > 0.';
            }
            if ($tableno <= 0) {
                $errors['tableno'] = 'Please select a valid table number.';
            }
            if (empty($errors)) {
                $dateTimeValidation = validateDateTime($datee, $times);
                if ($dateTimeValidation !== true) {
                    $response['message'] = "Error: " . $dateTimeValidation;
                } else {
                    $capacityValidation = validateCapacity($guests, $tableno);
                    if ($capacityValidation !== true) {
                        $response['message'] = "Error: Selected table can accommodate only {$capacityValidation} guests, but you entered {$guests} guests. Please select a larger table or reduce the number of guests.";
                    } else {
                        $availabilityValidation = validateTableAvailability($conn, $tableno, $datee, $times, $id);
                        if ($availabilityValidation !== true) {
                            $response['message'] = "Error: " . $availabilityValidation;
                        } else {
                            $stmt = $conn->prepare("UPDATE tables SET name=?, email=?, mobile=?, datee=?, times=?, guests=?, tableno=? WHERE id=?");
                            $stmt->bind_param("sssssiii", $name, $email, $mobile, $datee, $times, $guests, $tableno, $id);
                            if ($stmt->execute()) {
                                $reservationDetails = [
                                    'email' => $email,
                                    'mobile' => $mobile,
                                    'date' => $datee,
                                    'time' => $times,
                                    'guests' => $guests,
                                    'tableno' => $tableno,
                                    'capacity' => getTableCapacity($tableno)
                                ];
                                $emailService = new EmailService();
                                $emailResult = $emailService->sendTableReservationUpdate($email, $name, $id, $reservationDetails);

                                if ($emailResult['success']) {
                                    $response['success'] = true;
                                    $response['message'] = 'Reservation updated successfully! A confirmation email has been sent to your email address.';
                                } else {
                                    $response['success'] = true;
                                    $response['message'] = 'Reservation updated successfully! However, we could not send the confirmation email. Please contact us if you need a copy.';
                                    error_log("Table reservation update email failed for reservation #$id: " . $emailResult['message']);
                                }
                            } else {
                                $response['message'] = 'Error updating reservation: ' . $conn->error;
                            }
                            $stmt->close();
                        }
                    }
                }
            } else {
                $response['errors'] = $errors;
                $response['message'] = 'Please correct the errors below.';
            }
        }
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
} 
$reservations = array();
$result = $conn->query("SELECT * FROM tables ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tables</title>
    <link rel="stylesheet" href="../css/tables.css">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../scripts/logout.js"></script>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <span class="logo-icon">🍽️</span>
            <span class="logo-text">Savoré</span>
        </div>
        <ul class="sidebar-links">
            <li><a href="make-orders.php"><span class="icon">🛒</span> <span class="link-text">Create Order</span></a></li>
            <li><a href="orders-list.php"><span class="icon">📋</span> <span class="link-text">Orders List</span></a></li>
            <li><a href="tables.php"><span class="icon">🍽️</span> <span class="link-text">Tables</span></a></li>
            <li><a href="bill.php"><span class="icon">🧾</span> <span class="link-text">Bill & Invoices</span></a></li>
        </ul>
        <button class="logout-btn"><span class="icon">🚪</span> <span class="link-text">Logout</span></button>
    </aside>
    <div class="page-flex">
        <div class="book-table-container">
            <h1>Reserve Table</h1>
            <form id="booking-form" action="#" method="post">
                <input type="hidden" id="action" name="action" value="book">
                <input type="hidden" id="reservation-id" name="id" value="">

                <input type="text" id="name" name="name" placeholder="Customer Name">
                <div class="field-error"><?php echo isset($errors['name']) ? htmlspecialchars($errors['name']) : ''; ?></div>

                <input type="email" id="email" name="email" placeholder="Customer Email">
                <div class="field-error"><?php echo isset($errors['email']) ? htmlspecialchars($errors['email']) : ''; ?></div>

                <input type="tel" id="phone" name="phone" placeholder="Customer Mobile No.">
                <div class="field-error"><?php echo isset($errors['mobile']) ? htmlspecialchars($errors['mobile']) : ''; ?></div>

                <div class="custom-date-picker">
                    <input type="text" id="date-display" name="date-display" placeholder="Select Date" readonly>
                    <input type="hidden" id="date" name="date">
                    <div class="date-picker-calendar" id="date-picker-calendar">
                        <div class="calendar-header">
                            <button type="button" class="calendar-nav" id="prev-month">&lt;</button>
                            <span class="calendar-month-year" id="calendar-month-year"></span>
                            <button type="button" class="calendar-nav" id="next-month">&gt;</button>
                        </div>
                        <div class="calendar-weekdays">
                            <div class="calendar-weekday">Sun</div>
                            <div class="calendar-weekday">Mon</div>
                            <div class="calendar-weekday">Tue</div>
                            <div class="calendar-weekday">Wed</div>
                            <div class="calendar-weekday">Thu</div>
                            <div class="calendar-weekday">Fri</div>
                            <div class="calendar-weekday">Sat</div>
                        </div>
                        <div class="calendar-days" id="calendar-days">
                            <!-- Days will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                <div class="field-error"><?php echo isset($errors['date']) ? htmlspecialchars($errors['date']) : ''; ?></div>

                <div class="custom-time-picker">
                    <input type="text" id="time-display" name="time-display" placeholder="Select Time" readonly>
                    <input type="hidden" id="time" name="time">
                    <div class="time-dropdown" id="time-dropdown">
                        <div class="time-options" id="time-options">
                        </div>
                    </div>
                </div>
                <div class="field-error"><?php echo isset($errors['time']) ? htmlspecialchars($errors['time']) : ''; ?></div>

                <input type="number" id="guests" name="guests" min="1" placeholder="Number of Guests">
                <div class="field-error"><?php echo isset($errors['guests']) ? htmlspecialchars($errors['guests']) : ''; ?></div>

                <select name="table-no" id="table-no">
                    <option value="">Select a Table</option>
                    <!-- Tables will be loaded dynamically via JavaScript -->
                </select>
                <div class="field-error"><?php echo isset($errors['tableno']) ? htmlspecialchars($errors['tableno']) : ''; ?></div>
                <div class="button-row">
                    <button type="submit" id="book-btn">Book Now</button>
                    <button type="button" id="update-btn">Update</button>
                    <button type="reset" id="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
        <div class="table-container">
            <h1 class="table-heading">Reserved Tables</h1>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Res No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Guests</th>
                            <th>Table No</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reservations-tbody">
                        <?php foreach ($reservations as $reservation): ?>
                            <tr data-id="<?php echo htmlspecialchars($reservation['id']); ?>">
                                <td><?php echo htmlspecialchars($reservation['id']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['name']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['email']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['datee']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['times']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['guests']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['tableno']); ?></td>
                                <td>
                                    <button class="delete-btn" data-id="<?php echo htmlspecialchars($reservation['id']); ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script src="../scripts/tables.js"></script>
        <script>
            document.querySelector('.sidebar-logo').addEventListener('click', function() {
                window.location.href = '../index.php';
            });
        </script>
</body>

</html>