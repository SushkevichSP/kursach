<?php
session_start();
require __DIR__ . '/../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Введите корректный email.';
    } elseif (strlen($password) < 6) {
        $message = 'Пароль должен быть не менее 6 символов.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();

        if ($exists) {
            $message = 'Пользователь с таким email уже существует.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $conn->prepare('INSERT INTO users (email, password, role) VALUES (?, ?, "user")');
            $insert->bind_param('ss', $email, $hash);
            $insert->execute();
            header('Location: login.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="nav">
        <h2>Регистрация</h2>
        <div class="links">
            <a class="btn-ghost" href="../index.php">Главная</a>
            <?php if (($_SESSION['role'] ?? null) === 'admin'): ?>
                <a class="btn-primary" href="../admin/dashboard.php">Админка</a>
            <?php elseif (($_SESSION['role'] ?? null) === 'user'): ?>
                <a class="btn-primary" href="../user/dashboard.php">Кабинет</a>
            <?php else: ?>
                <a class="btn-ghost" href="login.php">Вход</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert error"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="grid" style="grid-template-columns: 1.2fr 1fr; gap: 18px;">
        <div class="surface">
            <h3>Создайте аккаунт</h3>
            <p class="muted">Получите доступ к личной статистике, а админ — к полной аналитике.</p>
            <form method="post">
                <label>Email</label>
                <input type="email" name="email" required placeholder="you@example.com">

                <label>Пароль</label>
                <input type="password" name="password" required minlength="6" placeholder="••••••••">

                <button type="submit">Создать аккаунт</button>
            </form>
            <p class="mt-3">Уже есть аккаунт? <a class="subtle-link" href="login.php">Войти</a></p>
        </div>
        <div class="surface">
            <h4>Что дальше?</h4>
            <ul style="color:#cbd5e1; padding-left:18px; line-height:1.6;">
                <li>После регистрации сразу перейдёте в личный кабинет.</li>
                <li>Все визиты автоматически пишутся в журнал с каждой страницы.</li>
                <li>Можно вернуться на сайт и свободно переходить по ссылкам.</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>

