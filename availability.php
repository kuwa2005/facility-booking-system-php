<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$pdo = getPdo();
$user = currentUser();

$selectedDate = trim((string)($_GET['date'] ?? ''));
if ($selectedDate === '') {
    $selectedDate = date('Y-m-d');
}
$selectedRoomId = (int)($_GET['room_id'] ?? 0);
$slots = reservationSlots();

$rooms = $pdo->query("SELECT id, name FROM rooms WHERE is_active = 1 ORDER BY id")->fetchAll();

$closedStmt = $pdo->prepare("SELECT reason FROM closed_dates WHERE date = ? LIMIT 1");
$closedStmt->execute([$selectedDate]);
$closed = $closedStmt->fetch();

$usageSql = "
    SELECT
        u.room_id,
        u.use_morning,
        u.use_afternoon,
        u.use_evening
    FROM usages u
    INNER JOIN applications a ON a.id = u.application_id
    WHERE u.use_date = ?
      AND a.status IN ('pending', 'approved')
";
if ($selectedRoomId > 0) {
    $usageSql .= " AND u.room_id = ?";
}
$usageStmt = $pdo->prepare($usageSql);
if ($selectedRoomId > 0) {
    $usageStmt->execute([$selectedDate, $selectedRoomId]);
} else {
    $usageStmt->execute([$selectedDate]);
}
$rows = $usageStmt->fetchAll();

$booked = [];
foreach ($rows as $row) {
    $rid = (int)$row['room_id'];
    $booked[$rid] = $booked[$rid] ?? ['morning' => false, 'afternoon' => false, 'night' => false];
    $booked[$rid]['morning'] = $booked[$rid]['morning'] || ((int)$row['use_morning'] === 1);
    $booked[$rid]['afternoon'] = $booked[$rid]['afternoon'] || ((int)$row['use_afternoon'] === 1);
    $booked[$rid]['night'] = $booked[$rid]['night'] || ((int)$row['use_evening'] === 1);
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>空き状況確認</title>
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
                <a href="/user_logout.php">ログアウト</a>
            <?php else: ?>
                <a href="/user_login.php">ログイン</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="main-section">
    <div class="container">
        <section class="card">
            <h2 class="card-title">空き状況確認</h2>
            <form method="get" class="form-grid two-col">
                <label>日付
                    <input type="date" name="date" value="<?= h($selectedDate) ?>">
                </label>
                <label>施設
                    <select name="room_id">
                        <option value="0">すべての施設</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= $selectedRoomId === (int)$r['id'] ? 'selected' : '' ?>><?= h($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="btn btn-primary" style="grid-column: 1 / -1;">表示する</button>
            </form>
        </section>

        <?php if ($closed): ?>
            <div class="error">この日は休館日です。<?= h((string)$closed['reason']) ?></div>
        <?php endif; ?>
        <?php if (!$closed): ?>
            <div class="notice">この日は予約可能日です。空き枠がある施設は予約申請できます。</div>
        <?php endif; ?>

        <section class="card">
            <h3 class="card-title"><?= h($selectedDate) ?> の空き状況</h3>
            <div class="availability-grid">
                <?php foreach ($rooms as $room): ?>
                    <?php if ($selectedRoomId > 0 && $selectedRoomId !== (int)$room['id']) { continue; } ?>
                    <?php
                        $status = $booked[(int)$room['id']] ?? ['morning' => false, 'afternoon' => false, 'night' => false];
                    ?>
                    <article class="availability-card">
                        <h4 style="margin:0 0 0.55rem;"><?= h($room['name']) ?></h4>
                        <?php foreach ($slots as $key => $label): ?>
                            <?php $isBooked = $closed ? true : ($status[$key] ?? false); ?>
                            <p style="margin:0.3rem 0; display:flex; justify-content:space-between; gap:0.5rem;">
                                <span><?= h($label) ?></span>
                                <?php if ($isBooked): ?>
                                    <span class="badge-ng">予約不可</span>
                                <?php else: ?>
                                    <span class="badge-ok">空きあり</span>
                                <?php endif; ?>
                            </p>
                        <?php endforeach; ?>
                        <p style="margin-top:0.7rem;">
                            <a class="btn btn-outline" href="/room_detail.php?id=<?= (int)$room['id'] ?>">施設詳細</a>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>
</body>
</html>

