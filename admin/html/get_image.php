<?php
require_once '../config/database.php';

if (isset($_GET['id']) && isset($_GET['table'])) {
    $id = intval($_GET['id']);
    $table = $_GET['table'];

    $allowedTables = ['employee', 'menu'];
    if (!in_array($table, $allowedTables)) {
        header('HTTP/1.1 400 Bad Request');
        exit('Invalid table name');
    }

    $stmt = $conn->prepare("SELECT photo FROM $table WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $photo = $row['photo'];

        if ($photo) {
            $imageInfo = getimagesizefromstring($photo);
            if ($imageInfo !== false) {
                $mimeType = $imageInfo['mime'];
                header('Content-Type: ' . $mimeType);
                header('Content-Length: ' . strlen($photo));
                echo $photo;
            } else {
                header('Content-Type: image/jpeg');
                echo $photo;
            }
        } else {
            header('HTTP/1.1 404 Not Found');
            exit('Image not found');
        }
    } else {
        header('HTTP/1.1 404 Not Found');
        exit('Record not found');
    }

    $stmt->close();
} else {
    header('HTTP/1.1 400 Bad Request');
    exit('Missing parameters');
}

$conn->close();
