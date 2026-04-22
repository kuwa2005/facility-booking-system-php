<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$pdo = getPdo();
$user = currentUser();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/rooms.php');
}

$stmt = $pdo->prepare(
    "SELECT
        id, name, capacity, description, base_price_morning, base_price_afternoon, base_price_evening,
        extension_price_midday, extension_price_evening, ac_price_per_hour
     FROM rooms
     WHERE id = ? AND is_active = 1
     LIMIT 1"
);
$stmt->execute([$id]);
$room = $stmt->fetch();
if (!$room) {
    redirect('/rooms.php');
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>施設詳細</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="public-header">
    <div class="container public-header-inner">
        <h1 class="brand"><a href="/index.php">施設予約システム（PHP DEMO）</a></h1>
        <nav class="public-nav">
            <a href="/index.php">ホーム</a>
            <a href="/rooms.php">施設一覧</a>
            <a href="/availability.php?room_id=<?= (int)$room['id'] ?>">空き状況</a>
            <?php if ($user): ?>
                <a href="/my_page.php">マイページ</a>
                <a href="/user_logout.php">ログアウト</a>
            <?php else: ?>
                <a href="/user_login.php">ログイン</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="main-section">
    <div class="container" style="max-width: 900px;">
        <section class="card">
            <h2 class="card-title"><?= h($room['name']) ?></h2>
            <p class="room-meta">定員: <?= (int)$room['capacity'] ?> 名</p>
            <p class="room-desc"><?= h((string)$room['description']) ?></p>
            <hr>
            <h3>料金表</h3>
            <ul class="slot-list">
                <li>午前（09:00-12:00）: <?= number_format((int)$room['base_price_morning']) ?>円</li>
                <li>午後（13:00-17:00）: <?= number_format((int)$room['base_price_afternoon']) ?>円</li>
                <li>夜間（18:00-21:30）: <?= number_format((int)$room['base_price_evening']) ?>円</li>
                <li>正午延長（12:00-13:00）: <?= number_format((int)$room['extension_price_midday']) ?>円</li>
                <li>夕方延長（17:00-18:00）: <?= number_format((int)$room['extension_price_evening']) ?>円</li>
                <li>空調（1時間）: <?= number_format((int)$room['ac_price_per_hour']) ?>円</li>
            </ul>
            <p style="margin-top: 1rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                <a class="btn btn-primary" href="/availability.php?room_id=<?= (int)$room['id'] ?>">この施設の空き状況</a>
                <a class="btn btn-outline" href="/index.php#booking-form">予約申請フォームへ</a>
            </p>
        </section>
    </div>
</main>
</body>
</html>

