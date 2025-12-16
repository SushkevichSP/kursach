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

$users = $conn->query(
    'SELECT u.id, u.email, u.role, COUNT(v.id) AS visits 
     FROM users u 
     LEFT JOIN visits v ON v.user_id = u.id 
     GROUP BY u.id 
     ORDER BY u.id DESC'
);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Пользователи</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="nav">
        <h2>Пользователи</h2>
        <div class="links">
            <a class="btn-ghost" href="../index.php">Сайт</a>
            <a class="btn-ghost" href="dashboard.php">Главная</a>
            <a class="btn-ghost" href="users.php">Пользователи</a>
            <a class="btn-ghost" href="stats.php">Статистика</a>
            <a class="btn-primary" href="../auth/logout.php">Выход</a>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Роль</th>
            <th>Посещений</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $users->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['email']); ?></td>
                <td><?= $row['role']; ?></td>
                <td><?= $row['visits']; ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>

