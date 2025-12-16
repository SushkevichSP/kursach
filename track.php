<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

$userId = $_SESSION['user_id'] ?? null;

// Определяем IP с учётом прокси
$ip = '';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    // Берём первый адрес из списка
    $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($parts[0]);
}
if (!$ip && !empty($_SERVER['REMOTE_ADDR'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
}
// Нормализуем локальный IPv6 loopback
if ($ip === '::1') {
    $ip = '127.0.0.1';
}
$ip = substr($ip ?: 'unknown', 0, 45);

$page = $_SERVER['REQUEST_URI'] ?? '';

// Фиксируем конкретный просмотр страницы
$stmt = $conn->prepare('INSERT INTO visits (user_id, ip_address, page) VALUES (?, ?, ?)');
$stmt->bind_param('iss', $userId, $ip, $page);
$stmt->execute();

// Фиксируем сессию (уникальное посещение сайта) и длительность
$sessionKey = session_id() . '|' . ($userId ? ('user_' . $userId) : 'guest');
$now = date('Y-m-d H:i:s');
$nowTs = time();

// Проверяем, есть ли уже сессия
$sessionStmt = $conn->prepare('SELECT id, started_at, last_seen, user_id, duration_seconds FROM sessions WHERE session_key = ?');
$sessionStmt->bind_param('s', $sessionKey);
$sessionStmt->execute();
$sessionResult = $sessionStmt->get_result();

if ($row = $sessionResult->fetch_assoc()) {
    $sessionId = (int)$row['id'];
    $storedUserId = $row['user_id'];
    $lastSeen = $row['last_seen'] ?? $row['started_at'];
    $lastSeenTs = $lastSeen ? strtotime($lastSeen) : $nowTs;
    $delta = max(0, $nowTs - $lastSeenTs);
    // Не даём накапливать большие паузы от "спящих" вкладок: максимум 30 минут за шаг
    $delta = min($delta, 1800);
    $newDuration = (int)($row['duration_seconds'] ?? 0) + $delta;

    // Обновляем last_seen и длительность
    $updateStmt = $conn->prepare(
        'UPDATE sessions 
         SET last_seen = ?,
             duration_seconds = ?,
             user_id = COALESCE(?, user_id)
         WHERE id = ?'
    );
    $updateStmt->bind_param('siii', $now, $newDuration, $userId, $sessionId);
    $updateStmt->execute();
} else {
    // Создаем новую сессию
    $insertSession = $conn->prepare(
        'INSERT INTO sessions (session_key, user_id, ip_address, started_at, last_seen, duration_seconds) 
         VALUES (?, ?, ?, ?, ?, 0)'
    );
    $insertSession->bind_param('sisss', $sessionKey, $userId, $ip, $now, $now);
    $insertSession->execute();
}

// Не выводим ничего, просто пишем трек в БД

