<?php
require_once '../config/database.php';

$message = '';
$messageType = '';
$editMode = false;
$menuData = null;

if (isset($_GET['edit_id'])) {
    $editMode = true;
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM menu WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $menuData = $result->fetch_assoc();
    } else {
        $message = 'Menu item not found!';
        $messageType = 'error';
        $editMode = false;
    }
    $stmt->close();
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("SELECT name FROM menu WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $menu = $result->fetch_assoc();
        $delete_stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
        $delete_stmt->bind_param("i", $delete_id);

        if ($delete_stmt->execute()) {
        } else {
            $message = 'Error deleting menu item: ' . $conn->error;
            $messageType = 'error';
        }
        $delete_stmt->close();
    } else {
        $message = 'Menu item not found!';
        $messageType = 'error';
    }
    $stmt->close();
    echo "<script>
        setTimeout(function() {
            window.location.href = 'menu.php';
        }, 1000);
    </script>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $price = intval($_POST['price']);
    $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
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
                    $photo = file_get_contents($uploadedFile['tmp_name']);
                    if ($photo === false) {
                        $errors['photo'] = 'Error reading uploaded file!';
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
    } elseif ($menu_id > 0) {
        $photoRequired = false;
    }
    if (empty($name)) {
        $errors['name'] = 'Name is required.';
    }
    if (empty($type)) {
        $errors['type'] = 'Type is required.';
    }
    if ($price <= 0) {
        $errors['price'] = 'Price must be greater than 0.';
    }
    if ($photoRequired && empty($photo)) {
        $errors['photo'] = $errors['photo'] ?? 'Photo is required.';
    }

    if (empty($errors)) {
        if ($menu_id > 0 && isset($_POST['update'])) {
            if ($photo) {
                $stmt = $conn->prepare("UPDATE menu SET name = ?, type = ?, price = ?, photo = ? WHERE id = ?");
                $null = NULL;
                $stmt->bind_param("ssibi", $name, $type, $price, $null, $menu_id);
                $stmt->send_long_data(3, $photo);
            } else {
                $stmt = $conn->prepare("UPDATE menu SET name = ?, type = ?, price = ? WHERE id = ?");
                $stmt->bind_param("ssii", $name, $type, $price, $menu_id);
            }
            if ($stmt->execute()) {
                $message = 'Menu item updated successfully!';
                $messageType = 'success';
                $_POST = array();
                $editMode = false;
                $menuData = null;
                echo "<script>window.location.href = 'menu.php';</script>";
            } else {
                $message = 'Error updating menu item: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO menu (name, type, price, photo) VALUES (?, ?, ?, ?)");
            $null = NULL;
            $stmt->bind_param("ssib", $name, $type, $price, $null);
            $stmt->send_long_data(3, $photo);

            if ($stmt->execute()) {
                $_POST = array();
            } else {
                $message = 'Error adding menu item: ' . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link rel="stylesheet" href="../styles/modern.css">
    <link rel="stylesheet" href="../styles/menu.css">
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
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="add-item-container">
            <h2><?php echo $editMode ? 'Edit Menu Item' : 'Add Menu Item'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($editMode && $menuData): ?>
                    <input type="hidden" name="menu_id" value="<?php echo $menuData['id']; ?>">
                <?php endif; ?>
                <input type="text" name="name" placeholder="Name"
                    value="<?php echo $editMode && $menuData ? htmlspecialchars($menuData['name']) : (isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''); ?>">
                <div class="field-error"><?php echo isset($errors['name']) ? htmlspecialchars($errors['name']) : ''; ?></div>
                <select name="type">
                    <option value="">Type of The Item</option>
                    <option value="burgers" <?php echo ($editMode && $menuData && $menuData['type'] == 'burgers') || (isset($_POST['type']) && $_POST['type'] == 'burgers') ? 'selected' : ''; ?>>Burgers</option>
                    <option value="bebidas" <?php echo ($editMode && $menuData && $menuData['type'] == 'bebidas') || (isset($_POST['type']) && $_POST['type'] == 'bebidas') ? 'selected' : ''; ?>>Bebidas</option>
                    <option value="chickens" <?php echo ($editMode && $menuData && $menuData['type'] == 'chickens') || (isset($_POST['type']) && $_POST['type'] == 'chickens') ? 'selected' : ''; ?>>Chickens</option>
                    <option value="rice" <?php echo ($editMode && $menuData && $menuData['type'] == 'rice') || (isset($_POST['type']) && $_POST['type'] == 'rice') ? 'selected' : ''; ?>>Rice</option>
                    <option value="shakes" <?php echo ($editMode && $menuData && $menuData['type'] == 'shakes') || (isset($_POST['type']) && $_POST['type'] == 'shakes') ? 'selected' : ''; ?>>Shakes</option>
                    <option value="potatoes" <?php echo ($editMode && $menuData && $menuData['type'] == 'potatoes') || (isset($_POST['type']) && $_POST['type'] == 'potatoes') ? 'selected' : ''; ?>>Potatoes</option>
                    <option value="sweet_tooth" <?php echo ($editMode && $menuData && $menuData['type'] == 'sweet_tooth') || (isset($_POST['type']) && $_POST['type'] == 'sweet_tooth') ? 'selected' : ''; ?>>Sweet Tooth</option>
                </select>
                <div class="field-error"><?php echo isset($errors['type']) ? htmlspecialchars($errors['type']) : ''; ?></div>
                <input type="number" name="price" placeholder="Price" min="1"
                    value="<?php echo $editMode && $menuData ? htmlspecialchars($menuData['price']) : (isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''); ?>">
                <div class="field-error"><?php echo isset($errors['price']) ? htmlspecialchars($errors['price']) : ''; ?></div>
                <input type="file" name="photo" accept="image/*" <?php echo $editMode ? '' : ''; ?>>
                <div class="field-error"><?php echo isset($errors['photo']) ? htmlspecialchars($errors['photo']) : ''; ?></div>
                <?php if ($editMode && $menuData && $menuData['photo']): ?>
                    <div class="current-photo-preview">
                        <small>Current photo:</small><br>
                        <img src="get_image.php?id=<?php echo $menuData['id']; ?>&table=menu&t=<?php echo time(); ?>"
                            alt="Current Photo" class="current-photo-preview">
                        <br><small>Upload a new photo to replace current one (optional)</small>
                    </div>
                <?php endif; ?>
                <br>
                <div class="form-actions">
                    <?php if ($editMode): ?>
                        <button type="submit" name="update">Update</button>
                        <a href="menu.php" class="cancel-btn">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="submit">Add</button>
                        <button type="reset">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="menu-list-container">
            <h2>Menu List</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Photo</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM menu ORDER BY id DESC");
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $row['type']))) . "</td>";
                                echo "<td>৳" . htmlspecialchars($row['price']) . "</td>";
                                echo "<td><img src='get_image.php?id=" . $row['id'] . "&table=menu&t=" . time() . "' alt='" . htmlspecialchars($row['name']) . "' class='table-image'></td>";
                                echo "<td>";
                                echo "<a href='menu.php?edit_id=" . $row['id'] . "' class='action-btn edit-btn edit-btn-link'>Edit</a>";
                                echo "<button class='action-btn delete-btn' onclick='deleteMenuItem(" . $row['id'] . ", \"" . htmlspecialchars($row['name'], ENT_QUOTES) . "\")'>Delete</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No menu items found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
    <script>
        function deleteMenuItem(menuId, menuName) {
            if (confirm('Are you sure you want to delete menu item "' + menuName + '"?\n\nThis action cannot be undone.')) {
                window.location.href = 'menu.php?delete_id=' + menuId;
            }
        }
    </script>
</body>

</html>