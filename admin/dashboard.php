<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    die('Access denied');
}

require __DIR__ . '/../config/db.php';
include __DIR__ . '/../track.php';

$totalVisits = $conn->query('SELECT COUNT(*) AS total FROM visits')->fetch_assoc()['total'] ?? 0;
$totalUsers = $conn->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'] ?? 0;
$today = $conn->query("SELECT COUNT(*) AS total FROM visits WHERE DATE(visit_time) = CURDATE()")->fetch_assoc()['total'] ?? 0;
$totalFacts = $conn->query('SELECT COUNT(*) AS total FROM fact_views')->fetch_assoc()['total'] ?? 0;
$todayFacts = $conn->query("SELECT COUNT(*) AS total FROM fact_views WHERE DATE(viewed_at) = CURDATE()")->fetch_assoc()['total'] ?? 0;

// Последние 7 дней по датам
$dailyStmt = $conn->query(
    "SELECT DATE(visit_time) as d, COUNT(*) as c
     FROM visits
     WHERE visit_time >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(visit_time)
     ORDER BY d ASC"
);
$daily = [];
while ($row = $dailyStmt->fetch_assoc()) {
    $daily[$row['d']] = (int)$row['c'];
}
// Заполняем пропуски нулями
$labels = [];
$values = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i day"));
    $labels[] = $day;
    $values[] = $daily[$day] ?? 0;
}

// Распределение по ролям/гостям
$roleStmt = $conn->query(
    "SELECT COALESCE(u.role, 'guest') as role, COUNT(*) as c
     FROM visits v
     LEFT JOIN users u ON u.id = v.user_id
     GROUP BY role"
);
$roleLabels = [];
$roleValues = [];
while ($row = $roleStmt->fetch_assoc()) {
    $roleLabels[] = $row['role'];
    $roleValues[] = (int)$row['c'];
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container">
    <div class="nav">
        <h2>Админ-панель</h2>
        <div class="links">
            <a class="btn-ghost" href="../index.php">Сайт</a>
            <a class="btn-ghost" href="dashboard.php">Главная</a>
            <a class="btn-ghost" href="users.php">Пользователи</a>
            <a class="btn-ghost" href="stats.php">Статистика</a>
            <a class="btn-primary" href="../auth/logout.php">Выход</a>
        </div>
    </div>

    <div class="card-grid">
        <div class="card">
            <strong>Всего посещений</strong>
            <div class="value"><?= $totalVisits; ?></div>
            <p class="muted">Все визиты сайта</p>
        </div>
        <div class="card">
            <strong>Уникальных пользователей</strong>
            <div class="value"><?= $totalUsers; ?></div>
            <p class="muted">Включая админов</p>
        </div>
        <div class="card">
            <strong>Сегодня</strong>
            <div class="value"><?= $today; ?></div>
            <p class="muted">За текущую дату</p>
        </div>
        <div class="card">
            <strong>Просмотрено фактов</strong>
            <div class="value"><?= $totalFacts; ?></div>
            <p class="muted">Всего показов блока фактов</p>
        </div>
        <div class="card">
            <strong>Фактов сегодня</strong>
            <div class="value"><?= $todayFacts; ?></div>
            <p class="muted">За текущие сутки</p>
        </div>
    </div>

    <div class="grid mt-4" style="grid-template-columns: 1.2fr 1fr; gap: 16px;">
        <div class="surface">
            <div class="flex-between">
                <h3 style="margin:0;">Посещения по дням</h3>
                <span class="pill">7 дней</span>
            </div>
            <canvas id="visitsChart" height="140"></canvas>
        </div>
        <div class="surface">
            <div class="flex-between">
                <h3 style="margin:0;">Распределение по ролям</h3>
                <span class="pill">User/Admin/Guest</span>
            </div>
            <canvas id="roleChart" height="140"></canvas>
        </div>
    </div>
</div>
<script>
    const visitsCtx = document.getElementById('visitsChart').getContext('2d');
    new Chart(visitsCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels); ?>,
            datasets: [{
                label: 'Визитов',
                data: <?= json_encode($values); ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.15)',
                tension: 0.3,
                fill: true,
                borderWidth: 2,
                pointRadius: 3
            }]
        },
        options: {
            scales: {
                x: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.05)' } }
            },
            plugins: {
                legend: { labels: { color: '#e2e8f0' } }
            }
        }
    });

    const roleCtx = document.getElementById('roleChart').getContext('2d');
    new Chart(roleCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($roleLabels); ?>,
            datasets: [{
                data: <?= json_encode($roleValues); ?>,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4']
            }]
        },
        options: {
            plugins: { legend: { labels: { color: '#e2e8f0' } } }
        }
    });
</script>
</body>
</html>

