<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireUserLogin();
$user = currentUser();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('/my_reservations.php');
}

$pdo = getPdo();
$stmt = $pdo->prepare(
    "SELECT
        a.id,
        a.user_id,
        a.applicant_representative,
        a.applicant_email,
        a.applicant_phone,
        a.event_name,
        a.event_description,
        a.entrance_fee_type,
        a.entrance_fee_amount,
        a.total_amount,
        a.status,
        a.payment_status,
        a.note,
        u.use_date,
        u.use_morning,
        u.use_afternoon,
        u.use_evening,
        u.use_midday_extension,
        u.use_evening_extension,
        u.ac_requested,
        u.room_charge,
        u.extension_charge,
        u.equipment_charge,
        u.ac_charge,
        r.name AS room_name
     FROM applications a
     INNER JOIN usages u ON u.application_id = a.id
     INNER JOIN rooms r ON r.id = u.room_id
     WHERE a.id = ? AND a.user_id = ?
     LIMIT 1"
);
$stmt->execute([$id, (int)$user['id']]);
$row = $stmt->fetch();

if (!$row) {
    redirect('/my_reservations.php?flash=' . urlencode('対象の予約が見つかりません'));
}

$eqStmt = $pdo->prepare(
    "SELECT e.name, ue.quantity, ue.slot_count, ue.line_amount
     FROM usage_equipment ue
     INNER JOIN usages u ON u.id = ue.usage_id
     INNER JOIN equipment e ON e.id = ue.equipment_id
     WHERE u.application_id = ?
     ORDER BY ue.id"
);
$eqStmt->execute([(int)$row['id']]);
$eqLines = $eqStmt->fetchAll();

$slotLabels = [];
if ((int)$row['use_morning'] === 1) {
    $slotLabels[] = '午前';
}
if ((int)$row['use_afternoon'] === 1) {
    $slotLabels[] = '午後';
}
if ((int)$row['use_evening'] === 1) {
    $slotLabels[] = '夜間';
}
if ((int)$row['use_midday_extension'] === 1) {
    $slotLabels[] = '正午延長';
}
if ((int)$row['use_evening_extension'] === 1) {
    $slotLabels[] = '夕方延長';
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約詳細</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="public-header">
    <div class="container public-header-inner">
        <h1 class="brand"><a href="/index.php">施設予約システム（PHP DEMO）</a></h1>
        <nav class="public-nav">
            <a href="/my_page.php">マイページ</a>
            <a href="/my_reservations.php">予約一覧</a>
            <a href="/user_logout.php">ログアウト</a>
        </nav>
    </div>
</header>

<main class="main-section">
    <div class="container" style="max-width: 900px;">
        <section class="card">
            <h2 class="card-title">予約詳細 #<?= (int)$row['id'] ?></h2>
            <p><strong>イベント:</strong> <?= h($row['event_name']) ?></p>
            <p><strong>部屋:</strong> <?= h($row['room_name']) ?></p>
            <p><strong>利用日:</strong> <?= h($row['use_date']) ?></p>
            <p><strong>時間帯:</strong> <?= h(implode(' / ', $slotLabels)) ?></p>
            <p><strong>申請者:</strong> <?= h($row['applicant_representative']) ?> / <?= h($row['applicant_email']) ?></p>
            <p><strong>状態:</strong> <span class="status status-<?= h($row['status']) ?>"><?= h($row['status']) ?></span> / <?= h($row['payment_status']) ?></p>
            <p><strong>目的:</strong> <?= h((string)$row['event_description']) ?></p>
            <hr>
            <p><strong>料金内訳</strong></p>
            <p>部屋料金: <?= number_format((int)$row['room_charge']) ?>円</p>
            <p>延長料金: <?= number_format((int)$row['extension_charge']) ?>円</p>
            <p>設備料金: <?= number_format((int)$row['equipment_charge']) ?>円</p>
            <p>空調料金: <?= number_format((int)$row['ac_charge']) ?>円</p>
            <p><strong>合計: <?= number_format((int)$row['total_amount']) ?>円</strong></p>
            <?php if (count($eqLines) > 0): ?>
                <div style="margin-top:0.8rem;">
                    <strong>設備明細</strong>
                    <ul class="slot-list">
                        <?php foreach ($eqLines as $line): ?>
                            <li><?= h($line['name']) ?> x <?= (int)$line['quantity'] ?> (slot <?= (int)$line['slot_count'] ?>): <?= number_format((int)$line['line_amount']) ?>円</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (in_array($row['status'], ['pending', 'approved'], true)): ?>
                <form method="post" action="/cancel_reservation.php" style="margin-top: 1rem;">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('この予約をキャンセルしますか？');">予約をキャンセル</button>
                </form>
            <?php endif; ?>
            <p style="margin-top: 1rem;"><a href="/my_reservations.php" style="color:#2563eb;">← 予約一覧に戻る</a></p>
        </section>
    </div>
</main>
</body>
</html>

