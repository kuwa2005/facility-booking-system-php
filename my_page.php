<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

requireUserLogin();
$user = currentUser();
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="public-header">
    <div class="container public-header-inner">
        <h1 class="brand"><a href="/index.php">施設予約システム（PHP DEMO）</a></h1>
        <nav class="public-nav">
            <a href="/index.php">ホーム</a>
            <a href="/my_page.php">マイページ</a>
            <a href="/my_reservations.php">予約一覧</a>
            <a href="/user_logout.php">ログアウト</a>
        </nav>
    </div>
</header>

<main class="main-section">
    <div class="container">
        <section class="card">
            <h2 class="card-title">マイページ</h2>
            <p>ログイン中ユーザー: <strong><?= h((string)($user['name'] ?? '')) ?></strong></p>
            <p>メールアドレス: <?= h((string)($user['email'] ?? '')) ?></p>
            <p style="margin-top: 1rem;">
                <a href="/my_reservations.php" class="btn btn-primary">自分の予約を確認する</a>
            </p>
        </section>
    </div>
</main>
</body>
</html>

