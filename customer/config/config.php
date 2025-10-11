<?php
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'savoredb',
    'charset' => 'utf8mb4'
];
function getDatabaseConnection()
{
    global $db_config;

    $conn = new mysqli(
        $db_config['host'],
        $db_config['username'],
        $db_config['password'],
        $db_config['database']
    );
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset($db_config['charset']);
    return $conn;
}
function closeDatabaseConnection($conn)
{
    if ($conn) {
        $conn->close();
    }
}
