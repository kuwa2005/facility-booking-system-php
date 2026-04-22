<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAdminLogin();

$pdo = getPdo();
$stmt = $pdo->query(
    "SELECT
        a.id,
        rm.name AS room_name,
        a.applicant_representative,
        a.applicant_email,
        a.applicant_phone,
        a.event_name,
        a.event_description,
        u.use_date,
        u.use_morning,
        u.use_afternoon,
        u.use_evening,
        u.use_midday_extension,
        u.use_evening_extension,
        u.ac_requested,
        a.total_amount,
        a.status,
        a.payment_status,
        a.note,
        a.created_at
     FROM applications a
     INNER JOIN usages u ON u.application_id = a.id
     INNER JOIN rooms rm ON rm.id = u.room_id
     ORDER BY u.use_date DESC, a.created_at DESC"
);
$reservations = $stmt->fetchAll();
$slotMap = reservationSlots();
$extMap = extensionSlots();
$totalCount = count($reservations);
$pendingCount = 0;
$approvedCount = 0;
$unpaidCount = 0;
$totalAmount = 0;
foreach ($reservations as $row) {
    $totalAmount += (int)$row['total_amount'];
    if ($row['status'] === 'pending') {
        $pendingCount++;
    }
    if ($row['status'] === 'approved') {
        $approvedCount++;
    }
    if ($row['payment_status'] === 'unpaid') {
        $unpaidCount++;
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理画面</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="staff-header">
    <div class="staff-header-inner">
        <h1>施設予約システム（DEMO） - 職員管理画面</h1>
        <div class="staff-header-links">
            <a class="link-button" href="/index.php">利用者画面</a>
            <a class="link-button danger" href="/logout.php">ログアウト</a>
        </div>
    </div>
</header>

<div class="staff-layout">
    <aside class="staff-sidebar">
        <div class="group-title">メインメニュー</div>
        <a href="/admin.php" class="active">ダッシュボード</a>
        <a href="/admin.php">予約管理</a>
        <a href="/admin.php">利用者管理（モック）</a>
        <div class="group-title">施設管理</div>
        <a href="/admin.php">部屋管理（モック）</a>
        <a href="/admin.php">設備管理（モック）</a>
        <a href="/admin.php">休館日管理（モック）</a>
    </aside>

    <main class="staff-main">
        <section style="margin-bottom: 1rem;">
            <h2 class="page-title">ダッシュボード</h2>
            <p class="page-description">Node版の職員画面レイアウトを意識したPHPモック</p>
        </section>

        <section class="stats-grid">
            <article class="stat-box bg1"><div>総申請数</div><div class="num"><?= $totalCount ?></div></article>
            <article class="stat-box bg2"><div>承認待ち</div><div class="num"><?= $pendingCount ?></div></article>
            <article class="stat-box bg3"><div>未決済</div><div class="num"><?= $unpaidCount ?></div></article>
            <article class="stat-box bg4"><div>合計金額</div><div class="num">¥<?= number_format($totalAmount) ?></div></article>
        </section>

        <section class="card">
            <h3 class="card-title">予約管理</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>部屋</th>
                        <th>申請者</th>
                        <th>利用日</th>
                        <th>時間帯</th>
                        <th>イベント</th>
                        <th>金額</th>
                        <th>状態</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reservations as $row): ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td><?= h($row['room_name']) ?></td>
                            <td><?= h($row['applicant_representative']) ?><br><small><?= h($row['applicant_email']) ?> / <?= h($row['applicant_phone']) ?></small></td>
                            <td><?= h($row['use_date']) ?></td>
                            <td>
                                <?php if ((int)$row['use_morning'] === 1): ?><?= h($slotMap['morning']) ?><br><?php endif; ?>
                                <?php if ((int)$row['use_afternoon'] === 1): ?><?= h($slotMap['afternoon']) ?><br><?php endif; ?>
                                <?php if ((int)$row['use_evening'] === 1): ?><?= h($slotMap['night']) ?><br><?php endif; ?>
                                <?php if ((int)$row['use_midday_extension'] === 1): ?><?= h($extMap['midday']) ?><br><?php endif; ?>
                                <?php if ((int)$row['use_evening_extension'] === 1): ?><?= h($extMap['evening']) ?><br><?php endif; ?>
                                <?php if ((int)$row['ac_requested'] === 1): ?><small>空調利用あり</small><?php endif; ?>
                            </td>
                            <td><?= h($row['event_name']) ?><br><small><?= h((string)$row['event_description']) ?></small></td>
                            <td><?= number_format((int)$row['total_amount']) ?>円<br><small><?= h($row['payment_status']) ?></small></td>
                            <td><span class="status status-<?= h($row['status']) ?>"><?= h($row['status']) ?></span></td>
                            <td>
                                <form method="post" action="/reservation_update.php" class="inline-form">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <select name="status">
                                        <option value="pending" <?= $row['status'] === 'pending' ? 'selected' : '' ?>>pending</option>
                                        <option value="approved" <?= $row['status'] === 'approved' ? 'selected' : '' ?>>approved</option>
                                        <option value="rejected" <?= $row['status'] === 'rejected' ? 'selected' : '' ?>>rejected</option>
                                        <option value="cancelled" <?= $row['status'] === 'cancelled' ? 'selected' : '' ?>>cancelled</option>
                                    </select>
                                    <select name="payment_status">
                                        <option value="unpaid" <?= $row['payment_status'] === 'unpaid' ? 'selected' : '' ?>>unpaid</option>
                                        <option value="paid" <?= $row['payment_status'] === 'paid' ? 'selected' : '' ?>>paid</option>
                                        <option value="refunded" <?= $row['payment_status'] === 'refunded' ? 'selected' : '' ?>>refunded</option>
                                    </select>
                                    <input type="text" name="note" value="<?= h((string)$row['note']) ?>" placeholder="メモ" maxlength="255">
                                    <button type="submit" class="btn btn-primary">更新</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>

