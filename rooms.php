<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$pdo = getPdo();
$user = currentUser();
$rooms = $pdo->query(
    "SELECT id, name, capacity, description, base_price_morning, base_price_afternoon, base_price_evening
     FROM rooms
     WHERE is_active = 1
     ORDER BY id"
)->fetchAll();
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>施設一覧</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="public-header">
    <div class="container public-header-inner">
        <h1 class="brand"><a href="/index.php">施設予約システム（PHP DEMO）</a></h1>
        <nav class="public-nav">
            <a href="/index.php">ホーム</a>
            <a href="/rooms.php">施設一覧</a>
            <a href="/availability.php">空き状況</a>
            <?php if ($user): ?>
                <a href="/my_page.php">マイページ</a>
                <a href="/my_reservations.php">予約一覧</a>
                <a href="/user_logout.php">ログアウト</a>
            <?php else: ?>
                <a href="/user_login.php">ログイン</a>
                <a href="/register.php">新規登録</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="main-section">
    <div class="container">
        <section class="card">
            <h2 class="card-title">施設一覧</h2>
            <div class="room-grid">
                <?php foreach ($rooms as $room): ?>
                    <article class="room-card">
                        <h3><?= h($room['name']) ?></h3>
                        <div class="room-meta">定員: <?= (int)$room['capacity'] ?> 名</div>
                        <div class="room-desc"><?= h((string)$room['description']) ?></div>
                        <ul class="slot-list">
                            <li>午前: <?= number_format((int)$room['base_price_morning']) ?>円</li>
                            <li>午後: <?= number_format((int)$room['base_price_afternoon']) ?>円</li>
                            <li>夜間: <?= number_format((int)$room['base_price_evening']) ?>円</li>
                        </ul>
                        <p style="margin-top: 0.8rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <a class="btn btn-outline" href="/room_detail.php?id=<?= (int)$room['id'] ?>">詳細</a>
                            <a class="btn btn-primary" href="/availability.php?room_id=<?= (int)$room['id'] ?>">空き状況</a>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>
</body>
</html>

