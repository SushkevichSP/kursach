<?php
// Простое подключение к MySQL
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'stats_db';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

