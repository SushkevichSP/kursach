<?php
session_start();
require __DIR__ . '/../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT id, password, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && password_verify($password, $result['password'])) {
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['role'] = $result['role'];

        if ($result['role'] === 'admin') {
            header('Location: ../admin/dashboard.php');
        } else {
            header('Location: ../user/dashboard.php');
        }
        exit;
    } else {
        $message = 'Неверная почта или пароль';
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="nav">
        <h2>Вход</h2>
        <div class="links">
            <a class="btn-ghost" href="../index.php">Главная</a>
            <?php if (($_SESSION['role'] ?? null) === 'admin'): ?>
                <a class="btn-primary" href="../admin/dashboard.php">Админка</a>
            <?php elseif (($_SESSION['role'] ?? null) === 'user'): ?>
                <a class="btn-primary" href="../user/dashboard.php">Кабинет</a>
            <?php else: ?>
                <a class="btn-ghost" href="register.php">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert error"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="grid" style="grid-template-columns: 1.2fr 1fr; gap: 18px;">
        <div class="surface">
            <h3>Войдите в аккаунт</h3>
            <p class="muted">После входа сразу попадаете в нужный раздел (кабинет или админка).</p>
            <form method="post" autocomplete="off">
                <label>Email</label>
                <input type="email" name="email" required placeholder="you@example.com" autocomplete="off">

                <label>Пароль</label>
                <input type="password" name="password" required placeholder="••••••••" autocomplete="new-password">

                <button type="submit">Войти</button>
            </form>
            <p class="mt-3">Нет аккаунта? <a class="subtle-link" href="register.php">Зарегистрируйтесь</a></p>
        </div>
        <div class="surface">
            <h4>Подсказки</h4>
            <ul style="color:#cbd5e1; padding-left:18px; line-height:1.6;">
                <li>Пользователи видят только свои визиты.</li>
                <li>Админ получает доступ ко всем разделам: пользователи, логи, фильтры.</li>
                <li>Можно вернуться на главную и ходить по сайту, трекинг сохраняется.</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>

