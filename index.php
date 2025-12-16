<?php
include __DIR__ . '/track.php';
$role = $_SESSION['role'] ?? null;
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Статистика посещаемости</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="nav">
        <h2>Статистика посещаемости</h2>
        <div class="links">
            <?php if ($role): ?>
                <a class="btn-ghost" href="auth/logout.php">Выход</a>
            <?php else: ?>
                <a class="btn-ghost" href="auth/login.php">Вход</a>
                <a class="btn-primary" href="auth/register.php">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="hero">
        <div>
            <h1>Отслеживание и аналитика посещений</h1>
            <p class="lead">Собираем IP, страницы, время визитов и показываем живую аналитику. Пользователь видит свои данные, админ управляет всем сайтом.</p>
            <div class="badges mt-3">
                <span class="pill">PHP 8+</span>
                <span class="pill">MySQL</span>
                <span class="pill">Chart-ready</span>
                <span class="pill">Roles: user / admin</span>
            </div>
            <div class="mt-4" style="display:flex; gap:12px; flex-wrap:wrap;">
                <?php if ($role === 'admin'): ?>
                    <a class="btn btn-primary" href="admin/dashboard.php">Перейти в админку</a>
                    <a class="btn btn-ghost" href="auth/logout.php">Выйти</a>
                    <a class="btn btn-ghost" href="admin/stats.php">Смотреть логи</a>
                <?php elseif ($role === 'user'): ?>
                    <a class="btn btn-primary" href="user/dashboard.php">Открыть кабинет</a>
                    <a class="btn btn-ghost" href="auth/logout.php">Выйти</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="auth/register.php">Начать бесплатно</a>
                    <a class="btn btn-ghost" href="auth/login.php">Войти</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="surface">
            <h4>Что внутри</h4>
            <div class="card-grid">
                <div class="card">
                    <strong>Сбор данных</strong>
                    <p class="muted">Отслеживаем IP, URL и время визита на каждой странице.</p>
                </div>
                <div class="card">
                    <strong>Личный кабинет</strong>
                    <p class="muted">Пользователь видит свои визиты и быстрые метрики.</p>
                </div>
                <div class="card">
                    <strong>Админ-панель</strong>
                    <p class="muted">Общая статистика по сайту и управление пользователями.</p>
                </div>
                <div class="card">
                    <strong>Фильтры</strong>
                    <p class="muted">Отбор по датам в журналах посещений.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="surface mt-4">
        <div class="flex-between">
            <div>
                <h3 style="margin:0;">Случайный факт</h3>
                <p class="muted" style="margin:4px 0 0;">Бесконечный поток интересных фактов.</p>
            </div>
            <button id="factBtn" class="btn btn-primary" type="button">Обновить</button>
        </div>
        <div id="factBox" class="mt-3" style="font-size:16px; color:#e2e8f0; line-height:1.6;">
            Загружаем...
        </div>
        <div id="factHint" class="mt-2 muted" style="color:#94a3b8; font-size:13px;"></div>
    </div>
</div>
<script>
    const box = document.getElementById('factBox');
    const hint = document.getElementById('factHint');
    const apiUrl = 'https://catfact.ninja/fact';
    const trackUrl = 'track_fact.php';

    async function renderFact() {
        hint.textContent = 'Получаем свежий факт...';
        try {
            const res = await fetch(apiUrl, { cache: 'no-store' });
            if (!res.ok) throw new Error('API error');
            const data = await res.json();
            box.textContent = data.fact || 'Факт не вернулся, попробуйте ещё раз.';
            hint.textContent = 'Источник: catfact.ninja.';
            // фиксируем просмотр факта
            fetch(trackUrl, { method: 'POST' }).catch(() => {});
        } catch (e) {
            const fallback = [
                'HTTP 418 "I’m a teapot" — первоапрельская шутка, ставшая стандартом.',
                'Первый домен .com — symbolics.com, зарегистрирован в 1985.',
                'CSS предложен Хоконом Виум Ли в 1994 году.',
                'MySQL назван в честь дочери сооснователя — My.'
            ];
            const fact = fallback[Math.floor(Math.random() * fallback.length)];
            box.textContent = fact;
            hint.textContent = 'API недоступен, показываем резервный факт.';
        }
    }

    document.getElementById('factBtn').addEventListener('click', renderFact);
    renderFact();
</script>
</body>
</html>

