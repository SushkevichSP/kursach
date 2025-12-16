<?php
include __DIR__ . '/track.php';
$role = $_SESSION['role'] ?? null;
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flappy Bird - Игра</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .game-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .game-wrapper {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(79, 70, 229, 0.3);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
                        0 0 40px rgba(79, 70, 229, 0.2);
        }

        .game-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .game-header h1 {
            font-size: 2.5em;
            background: linear-gradient(135deg, #ffffff, #a5b4fc, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            text-shadow: 0 0 30px rgba(79, 70, 229, 0.5);
        }

        .score-display {
            font-size: 1.5em;
            color: #fff;
            font-weight: bold;
            margin: 10px 0;
            text-shadow: 0 0 10px rgba(79, 70, 229, 0.8);
        }

        .high-score {
            font-size: 1em;
            color: #cbd5e1;
            margin-bottom: 10px;
        }

        #gameCanvas {
            border: 3px solid rgba(79, 70, 229, 0.5);
            border-radius: 15px;
            background: linear-gradient(180deg, #87ceeb 0%, #98d8c8 50%, #f7dc6f 100%);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3),
                        inset 0 0 20px rgba(79, 70, 229, 0.1);
            display: block;
            margin: 0 auto;
        }

        .game-controls {
            text-align: center;
            margin-top: 20px;
            color: #cbd5e1;
        }

        .game-controls p {
            margin: 5px 0;
            font-size: 0.9em;
        }

        .game-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }

        .btn-game {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            font-size: 1em;
        }

        .btn-start {
            background: linear-gradient(135deg, #4f46e5, #3b82f6);
            color: #fff;
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.4);
        }

        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.6);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .game-over {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(79, 70, 229, 0.5);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            display: none;
            z-index: 100;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
        }

        .game-over.show {
            display: block;
            animation: fadeInScale 0.3s ease;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        .game-over h2 {
            color: #fff;
            margin-bottom: 15px;
            font-size: 2em;
        }

        .game-over .final-score {
            font-size: 1.5em;
            color: #a5b4fc;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div id="particles-container"></div>
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <div class="game-container">
        <div class="game-wrapper">
            <div class="game-header">
                <h1>🐦 Flappy Bird</h1>
                <div class="score-display">Счет: <span id="score">0</span></div>
                <div class="high-score">Рекорд: <span id="highScore">0</span></div>
            </div>

            <canvas id="gameCanvas" width="400" height="600"></canvas>

            <div class="game-controls">
                <p>🖱️ Кликните или нажмите ПРОБЕЛ для прыжка</p>
                <div class="game-buttons">
                    <button class="btn-game btn-start" id="startBtn">Начать игру</button>
                    <a href="index.php" class="btn-game btn-back">Назад</a>
                </div>
            </div>
        </div>
    </div>

    <div class="game-over" id="gameOver">
        <h2>Игра окончена!</h2>
        <div class="final-score">Ваш счет: <span id="finalScore">0</span></div>
        <div class="game-buttons" style="margin-top: 20px;">
            <button class="btn-game btn-start" id="restartBtn">Играть снова</button>
            <a href="index.php" class="btn-game btn-back">На главную</a>
        </div>
    </div>

    <script>
        // Canvas setup
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        const scoreElement = document.getElementById('score');
        const highScoreElement = document.getElementById('highScore');
        const gameOverElement = document.getElementById('gameOver');
        const finalScoreElement = document.getElementById('finalScore');
        const startBtn = document.getElementById('startBtn');
        const restartBtn = document.getElementById('restartBtn');

        // Game state
        let gameState = 'waiting'; // waiting, playing, gameOver
        let score = 0;
        let highScore = localStorage.getItem('flappyHighScore') || 0;
        highScoreElement.textContent = highScore;

        // Bird
        const bird = {
            x: 100,
            y: 300,
            width: 40,
            height: 30,
            velocity: 0,
            gravity: 0.5,
            jump: -8,
            color: '#ffd700'
        };

        // Pipes
        const pipes = [];
        const pipeWidth = 60;
        const pipeGap = 200;
        const pipeSpeed = 2;
        let pipeSpawnTimer = 0;
        const pipeSpawnInterval = 120;

        // Clouds
        const clouds = [];
        for (let i = 0; i < 5; i++) {
            clouds.push({
                x: Math.random() * canvas.width,
                y: Math.random() * 200,
                width: 80 + Math.random() * 40,
                height: 40 + Math.random() * 20,
                speed: 0.5 + Math.random() * 0.5
            });
        }

        // Draw functions
        function drawBird() {
            ctx.save();
            ctx.translate(bird.x + bird.width / 2, bird.y + bird.height / 2);
            const rotation = Math.min(bird.velocity * 0.1, Math.PI / 3);
            ctx.rotate(rotation);
            
            // Bird body
            ctx.fillStyle = bird.color;
            ctx.beginPath();
            ctx.ellipse(0, 0, bird.width / 2, bird.height / 2, 0, 0, Math.PI * 2);
            ctx.fill();
            
            // Bird eye
            ctx.fillStyle = '#000';
            ctx.beginPath();
            ctx.arc(5, -5, 4, 0, Math.PI * 2);
            ctx.fill();
            
            // Bird beak
            ctx.fillStyle = '#ff6b35';
            ctx.beginPath();
            ctx.moveTo(bird.width / 2 - 5, 0);
            ctx.lineTo(bird.width / 2 + 5, -3);
            ctx.lineTo(bird.width / 2 + 5, 3);
            ctx.closePath();
            ctx.fill();
            
            // Wing
            ctx.fillStyle = '#ffed4e';
            ctx.beginPath();
            ctx.ellipse(-5, 5, 8, 12, Math.sin(Date.now() / 100) * 0.3, 0, Math.PI * 2);
            ctx.fill();
            
            ctx.restore();
        }

        function drawPipe(x, y, height, isTop) {
            ctx.fillStyle = '#2d5016';
            ctx.fillRect(x, y, pipeWidth, height);
            
            // Pipe cap
            ctx.fillStyle = '#3a6b1f';
            ctx.fillRect(x - 5, isTop ? y : y + height - 20, pipeWidth + 10, 20);
            
            // Pipe highlight
            ctx.fillStyle = 'rgba(255, 255, 255, 0.2)';
            ctx.fillRect(x + 5, y, 10, height);
        }

        function drawClouds() {
            ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
            clouds.forEach(cloud => {
                ctx.beginPath();
                ctx.arc(cloud.x, cloud.y, cloud.height / 2, 0, Math.PI * 2);
                ctx.arc(cloud.x + cloud.width / 3, cloud.y, cloud.height / 2, 0, Math.PI * 2);
                ctx.arc(cloud.x + cloud.width * 2 / 3, cloud.y, cloud.height / 2, 0, Math.PI * 2);
                ctx.fill();
            });
        }

        function drawBackground() {
            // Sky gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
            gradient.addColorStop(0, '#87ceeb');
            gradient.addColorStop(0.5, '#98d8c8');
            gradient.addColorStop(1, '#f7dc6f');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

        // Game logic
        function updateBird() {
            if (gameState === 'playing') {
                bird.velocity += bird.gravity;
                bird.y += bird.velocity;
                
                // Ground and ceiling collision
                if (bird.y + bird.height > canvas.height - 50) {
                    bird.y = canvas.height - 50 - bird.height;
                    gameOver();
                }
                if (bird.y < 0) {
                    bird.y = 0;
                    bird.velocity = 0;
                }
            }
        }

        function updatePipes() {
            if (gameState === 'playing') {
                pipeSpawnTimer++;
                
                if (pipeSpawnTimer >= pipeSpawnInterval) {
                    const pipeHeight = 150 + Math.random() * 200;
                    pipes.push({
                        x: canvas.width,
                        topHeight: pipeHeight,
                        bottomY: pipeHeight + pipeGap,
                        bottomHeight: canvas.height - pipeHeight - pipeGap - 50,
                        passed: false
                    });
                    pipeSpawnTimer = 0;
                }
                
                pipes.forEach((pipe, index) => {
                    pipe.x -= pipeSpeed;
                    
                    // Check collision
                    if (bird.x < pipe.x + pipeWidth &&
                        bird.x + bird.width > pipe.x &&
                        (bird.y < pipe.topHeight || bird.y + bird.height > pipe.bottomY)) {
                        gameOver();
                    }
                    
                    // Score
                    if (!pipe.passed && pipe.x + pipeWidth < bird.x) {
                        pipe.passed = true;
                        score++;
                        scoreElement.textContent = score;
                    }
                    
                    // Remove off-screen pipes
                    if (pipe.x + pipeWidth < 0) {
                        pipes.splice(index, 1);
                    }
                });
            }
        }

        function updateClouds() {
            clouds.forEach(cloud => {
                cloud.x -= cloud.speed;
                if (cloud.x + cloud.width < 0) {
                    cloud.x = canvas.width;
                    cloud.y = Math.random() * 200;
                }
            });
        }

        function drawGround() {
            ctx.fillStyle = '#8b4513';
            ctx.fillRect(0, canvas.height - 50, canvas.width, 50);
            
            // Grass
            ctx.fillStyle = '#2d5016';
            ctx.fillRect(0, canvas.height - 50, canvas.width, 5);
            
            // Ground pattern
            ctx.strokeStyle = 'rgba(0, 0, 0, 0.2)';
            ctx.lineWidth = 1;
            for (let i = 0; i < canvas.width; i += 20) {
                ctx.beginPath();
                ctx.moveTo(i, canvas.height - 50);
                ctx.lineTo(i, canvas.height);
                ctx.stroke();
            }
        }

        function gameOver() {
            if (gameState === 'playing') {
                gameState = 'gameOver';
                if (score > highScore) {
                    highScore = score;
                    localStorage.setItem('flappyHighScore', highScore);
                    highScoreElement.textContent = highScore;
                }
                finalScoreElement.textContent = score;
                gameOverElement.classList.add('show');
            }
        }

        function resetGame() {
            gameState = 'waiting';
            score = 0;
            scoreElement.textContent = score;
            bird.y = 300;
            bird.velocity = 0;
            pipes.length = 0;
            pipeSpawnTimer = 0;
            gameOverElement.classList.remove('show');
        }

        function startGame() {
            resetGame();
            gameState = 'playing';
        }

        function gameLoop() {
            // Clear canvas
            drawBackground();
            drawClouds();
            updateClouds();
            
            // Update game
            updateBird();
            updatePipes();
            
            // Draw pipes
            pipes.forEach(pipe => {
                drawPipe(pipe.x, 0, pipe.topHeight, true);
                drawPipe(pipe.x, pipe.bottomY, pipe.bottomHeight, false);
            });
            
            // Draw ground
            drawGround();
            
            // Draw bird
            drawBird();
            
            requestAnimationFrame(gameLoop);
        }

        // Controls
        function jump() {
            if (gameState === 'waiting') {
                startGame();
            } else if (gameState === 'playing') {
                bird.velocity = bird.jump;
            }
        }

        canvas.addEventListener('click', jump);
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space') {
                e.preventDefault();
                jump();
            }
        });

        startBtn.addEventListener('click', startGame);
        restartBtn.addEventListener('click', startGame);

        // Start game loop
        gameLoop();

        // Simple particle system for background (reuse from index.php)
        const particlesContainer = document.getElementById('particles-container');
        if (particlesContainer) {
            const canvas = document.createElement('canvas');
            canvas.style.position = 'fixed';
            canvas.style.top = '0';
            canvas.style.left = '0';
            canvas.style.width = '100%';
            canvas.style.height = '100%';
            canvas.style.pointerEvents = 'none';
            canvas.style.zIndex = '0';
            particlesContainer.appendChild(canvas);
            
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            
            const particles = [];
            for (let i = 0; i < 50; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    size: Math.random() * 2 + 1,
                    speedX: Math.random() * 0.5 - 0.25,
                    speedY: Math.random() * 0.5 - 0.25,
                    opacity: Math.random() * 0.3 + 0.1
                });
            }
            
            function animateParticles() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                particles.forEach(p => {
                    p.x += p.speedX;
                    p.y += p.speedY;
                    if (p.x < 0 || p.x > canvas.width) p.speedX *= -1;
                    if (p.y < 0 || p.y > canvas.height) p.speedY *= -1;
                    
                    ctx.fillStyle = `rgba(79, 70, 229, ${p.opacity})`;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                    ctx.fill();
                });
                requestAnimationFrame(animateParticles);
            }
            animateParticles();
        }
    </script>
</body>
</html>
