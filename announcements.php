<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$pdo = getPdo();
$user = currentUser();
$rows = $pdo->query(
    "SELECT id, title, body, published_at
     FROM announcements
     WHERE is_published = 1
     ORDER BY published_at DESC, id DESC"
)->fetchAll();
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>お知らせ</title>
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
            <a href="/announcements.php">お知らせ</a>
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
    <div class="container" style="max-width: 960px;">
        <section class="card">
            <h2 class="card-title">お知らせ一覧</h2>
            <?php if (count($rows) === 0): ?>
                <p class="room-meta">現在公開中のお知らせはありません。</p>
            <?php else: ?>
                <div class="dash-list">
                    <?php foreach ($rows as $row): ?>
                        <article class="card" style="margin-bottom:0.7rem;">
                            <h3 style="margin:0 0 0.4rem;"><?= h($row['title']) ?></h3>
                            <p class="room-meta">公開日: <?= h((string)$row['published_at']) ?></p>
                            <p style="margin-top:0.6rem;"><?= nl2br(h((string)$row['body'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>

