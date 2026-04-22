<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireUserLogin();
$user = currentUser();
$flash = trim((string)($_GET['flash'] ?? ''));

$pdo = getPdo();
$stmt = $pdo->prepare(
    "SELECT
        a.id,
        a.event_name,
        a.total_amount,
        a.status,
        a.payment_status,
        u.use_date,
        r.name AS room_name
     FROM applications a
     INNER JOIN usages u ON u.application_id = a.id
     INNER JOIN rooms r ON r.id = u.room_id
     WHERE a.user_id = ?
     ORDER BY u.use_date DESC, a.created_at DESC"
);
$stmt->execute([(int)$user['id']]);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイ予約一覧</title>
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
            <h2 class="card-title">マイ予約一覧</h2>
            <?php if ($flash !== ''): ?><div class="notice"><?= h($flash) ?></div><?php endif; ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>イベント</th>
                        <th>部屋</th>
                        <th>利用日</th>
                        <th>金額</th>
                        <th>状態</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td><?= h($row['event_name']) ?></td>
                            <td><?= h($row['room_name']) ?></td>
                            <td><?= h($row['use_date']) ?></td>
                            <td><?= number_format((int)$row['total_amount']) ?>円<br><small><?= h($row['payment_status']) ?></small></td>
                            <td><span class="status status-<?= h($row['status']) ?>"><?= h($row['status']) ?></span></td>
                            <td>
                                <a class="btn btn-outline" href="/reservation_detail.php?id=<?= (int)$row['id'] ?>">詳細</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
</body>
</html>

