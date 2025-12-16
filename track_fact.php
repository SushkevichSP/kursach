<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require __DIR__ . '/config/db.php';

$userId = $_SESSION['user_id'] ?? null;

$stmt = $conn->prepare('INSERT INTO fact_views (user_id) VALUES (?)');
$stmt->bind_param('i', $userId);
$stmt->execute();

header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);

