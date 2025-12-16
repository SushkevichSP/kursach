<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'user') {
    die('Access denied');
}

require __DIR__ . '/../config/db.php';
include __DIR__ . '/../track.php';

$userId = (int)$_SESSION['user_id'];

$total = $conn->query("SELECT COUNT(*) AS total FROM visits WHERE user_id = $userId")->fetch_assoc()['total'] ?? 0;
$today = $conn->query("SELECT COUNT(*) AS total FROM visits WHERE user_id = $userId AND DATE(visit_time) = CURDATE()")->fetch_assoc()['total'] ?? 0;
$factsTotal = $conn->query("SELECT COUNT(*) AS total FROM fact_views WHERE user_id = $userId")->fetch_assoc()['total'] ?? 0;
$factsToday = $conn->query("SELECT COUNT(*) AS total FROM fact_views WHERE user_id = $userId AND DATE(viewed_at) = CURDATE()")->fetch_assoc()['total'] ?? 0;

$latest = $conn->query(
    "SELECT page, ip_address, visit_time 
     FROM visits 
     WHERE user_id = $userId 
     ORDER BY visit_time DESC 
     LIMIT 20"
);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Моя статистика</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="nav">
        <h2>Личный кабинет</h2>
        <div class="links">
            <a class="btn-ghost" href="../index.php">Сайт</a>
            <a class="btn-primary" href="../auth/logout.php">Выход</a>
        </div>
    </div>

    <div class="card-grid">
        <div class="card">
            <strong>Всего посещений</strong>
            <div class="value"><?= $total; ?></div>
            <p class="muted">Все визиты, которые привязаны к аккаунту</p>
        </div>
        <div class="card">
            <strong>Сегодня</strong>
            <div class="value"><?= $today; ?></div>
            <p class="muted">За текущую дату</p>
        </div>
        <div class="card">
            <strong>Просмотрено фактов</strong>
            <div class="value"><?= $factsTotal; ?></div>
            <p class="muted">Сколько раз ты открывал блок фактов</p>
        </div>
        <div class="card">
            <strong>Фактов сегодня</strong>
            <div class="value"><?= $factsToday; ?></div>
            <p class="muted">За текущий день</p>
        </div>
    </div>

    <h3 class="mt-4">Последние посещения</h3>
    <table>
        <thead>
        <tr>
            <th>Страница</th>
            <th>IP</th>
            <th>Время</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $latest->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['page']); ?></td>
                <td><?= htmlspecialchars($row['ip_address']); ?></td>
                <td><?= $row['visit_time']; ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>

