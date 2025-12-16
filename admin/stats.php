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

$tz = new DateTimeZone('Europe/Minsk');

$pageNames = [
    '/' => 'Главная',
    '/index.php' => 'Главная',
    '/auth/login.php' => 'Вход',
    '/auth/register.php' => 'Регистрация',
    '/admin/dashboard.php' => 'Админ: Главная',
    '/admin/users.php' => 'Админ: Пользователи',
    '/admin/stats.php' => 'Админ: Статистика',
    '/user/dashboard.php' => 'Кабинет пользователя',
];

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$userFilter = $_GET['user_id'] ?? '';

// Для селекта пользователей
$usersList = $conn->query('SELECT id, email, role FROM users ORDER BY email ASC');

$query = 'SELECT s.id, s.ip_address, s.started_at, s.last_seen, s.duration_seconds, u.email 
          FROM sessions s 
          LEFT JOIN users u ON u.id = s.user_id';
$params = [];
$types = '';
$conditions = [];

// Фильтры для агрегатов по пользователям
$aggParams = [];
$aggTypes = '';
$aggConditions = [];

if ($from) {
    $conditions[] = 's.started_at >= ?';
    $params[] = $from . ' 00:00:00';
    $types .= 's';

    $aggConditions[] = 's.started_at >= ?';
    $aggParams[] = $from . ' 00:00:00';
    $aggTypes .= 's';
}
if ($to) {
    $conditions[] = 's.started_at <= ?';
    $params[] = $to . ' 23:59:59';
    $types .= 's';

    $aggConditions[] = 's.started_at <= ?';
    $aggParams[] = $to . ' 23:59:59';
    $aggTypes .= 's';
}
if ($userFilter !== '') {
    $conditions[] = 's.user_id = ?';
    $params[] = (int)$userFilter;
    $types .= 'i';

    $aggConditions[] = 's.user_id = ?';
    $aggParams[] = (int)$userFilter;
    $aggTypes .= 'i';
}

if ($conditions) {
    $query .= ' WHERE ' . implode(' AND ', $conditions);
}
$query .= ' ORDER BY s.started_at DESC LIMIT 200';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$visits = $stmt->get_result();

// Агрегат по пользователям: суммарное время и количество сессий
$aggQuery = 'SELECT s.user_id,
                    COALESCE(u.email, "Гость") AS email,
                    COALESCE(u.role, "guest") AS role,
                    SUM(s.duration_seconds) AS total_seconds,
                    COUNT(*) AS sessions_count
             FROM sessions s
             LEFT JOIN users u ON u.id = s.user_id';

if ($aggConditions) {
    $aggQuery .= ' WHERE ' . implode(' AND ', $aggConditions);
}
$aggQuery .= ' GROUP BY s.user_id, email, role ORDER BY total_seconds DESC';

$aggStmt = $conn->prepare($aggQuery);
if (!empty($aggParams)) {
    $aggStmt->bind_param($aggTypes, ...$aggParams);
}
$aggStmt->execute();
$aggRows = $aggStmt->get_result();

// Логи посещений страниц
$logQuery = 'SELECT v.id, v.ip_address, v.page, v.visit_time, u.email
             FROM visits v
             LEFT JOIN users u ON u.id = v.user_id';
$logParams = [];
$logTypes = [];
$logConditions = [];

if ($from) {
    $logConditions[] = 'v.visit_time >= ?';
    $logParams[] = $from . ' 00:00:00';
    $logTypes[] = 's';
}
if ($to) {
    $logConditions[] = 'v.visit_time <= ?';
    $logParams[] = $to . ' 23:59:59';
    $logTypes[] = 's';
}
if ($userFilter !== '') {
    $logConditions[] = 'v.user_id = ?';
    $logParams[] = (int)$userFilter;
    $logTypes[] = 'i';
}

if ($logConditions) {
    $logQuery .= ' WHERE ' . implode(' AND ', $logConditions);
}
$logQuery .= ' ORDER BY v.visit_time DESC LIMIT 200';

$logStmt = $conn->prepare($logQuery);
if (!empty($logParams)) {
    $logStmt->bind_param(implode('', $logTypes), ...$logParams);
}
$logStmt->execute();
$logRows = $logStmt->get_result();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Статистика</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="nav">
        <h2>Статистика посещений</h2>
        <div class="links">
            <a class="btn-ghost" href="../index.php">Сайт</a>
            <a class="btn-ghost" href="dashboard.php">Главная</a>
            <a class="btn-ghost" href="users.php">Пользователи</a>
            <a class="btn-ghost" href="stats.php">Статистика</a>
            <a class="btn-primary" href="../auth/logout.php">Выход</a>
        </div>
    </div>

    <form method="get" class="mt-2 surface">
        <div class="filters-row">
            <div class="input-block">
                <label>От даты</label>
                <input type="date" name="from" value="<?= htmlspecialchars($from); ?>">
            </div>
            <div class="input-block">
                <label>До даты</label>
                <input type="date" name="to" value="<?= htmlspecialchars($to); ?>">
            </div>
            <div class="input-block">
                <label>Пользователь</label>
                <select name="user_id">
                    <option value="">Все</option>
                    <?php while ($u = $usersList->fetch_assoc()): ?>
                        <option value="<?= $u['id']; ?>" <?= ($userFilter !== '' && (int)$userFilter === (int)$u['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($u['email']); ?> (<?= $u['role']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="input-block">
                <label>&nbsp;</label>
                <button class="btn btn-primary" type="submit">Фильтр</button>
            </div>
        </div>
    </form>

    <div class="surface mt-3">
        <h4>Время на сайте по пользователям</h4>
        <table>
            <thead>
            <tr>
                <th>Email</th>
                <th>Роль</th>
                <th>Сессий</th>
                <th>Всего времени</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = $aggRows->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['role']); ?></td>
                    <td><?= (int)$row['sessions_count']; ?></td>
                    <td>
                        <?php
                        $dur = (int)($row['total_seconds'] ?? 0);
                        $hours = floor($dur / 3600);
                        $minutes = floor(($dur % 3600) / 60);
                        $seconds = $dur % 60;
                        printf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <table>
        <thead>
        <tr>
            <th>Email</th>
            <th>IP</th>
            <th>Начало</th>
            <th>Окончание</th>
            <th>Длительность</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $visits->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['email'] ?? 'Гость'); ?></td>
                <td><?= htmlspecialchars($row['ip_address'] ?: '—'); ?></td>
                <td>
                    <?php
                    $dt = $row['started_at'] ? new DateTime($row['started_at']) : null;
                    if ($dt) {
                        $dt->setTimezone($tz);
                        echo $dt->format('d.m.Y H:i:s');
                    } else {
                        echo '—';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    $dt = $row['last_seen'] ? new DateTime($row['last_seen']) : null;
                    if ($dt) {
                        $dt->setTimezone($tz);
                        echo $dt->format('d.m.Y H:i:s');
                    } else {
                        echo '—';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    $dur = (int)($row['duration_seconds'] ?? 0);
                    $hours = floor($dur / 3600);
                    $minutes = floor(($dur % 3600) / 60);
                    $seconds = $dur % 60;
                    printf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div class="surface mt-3">
        <h4>Логи посещений страниц</h4>
        <table>
            <thead>
            <tr>
                <th>Email</th>
                <th>IP</th>
                <th>Страница</th>
                <th>Время</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = $logRows->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['email'] ?? 'Гость'); ?></td>
                    <td><?= htmlspecialchars($row['ip_address'] ?: '—'); ?></td>
                    <td>
                        <?php
                        $pageRaw = $row['page'] ?? '/';
                        $pageClean = trim($pageRaw) !== '' ? $pageRaw : '/';
                        $path = parse_url($pageClean, PHP_URL_PATH) ?? '/';
                        $name = $pageNames[$path] ?? (trim($path, '/ ') !== '' ? $path : '/');
                        // Подрезаем отображаемое имя, но полный URL оставляем в tooltip
                        $display = function_exists('mb_strimwidth')
                            ? mb_strimwidth($name, 0, 80, '…', 'UTF-8')
                            : substr($name, 0, 77) . (strlen($name) > 77 ? '…' : '');
                        ?>
                        <span title="<?= htmlspecialchars($pageClean); ?>">
                            <?= htmlspecialchars($display); ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $dt = $row['visit_time'] ? new DateTime($row['visit_time']) : null;
                        if ($dt) {
                            $dt->setTimezone($tz);
                            echo $dt->format('d.m.Y H:i:s');
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

