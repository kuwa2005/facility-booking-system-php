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

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

$todayReservationsStmt = $pdo->prepare(
    "SELECT COUNT(*) AS cnt
     FROM usages u
     INNER JOIN applications a ON a.id = u.application_id
     WHERE u.use_date = ?
       AND a.status IN ('pending', 'approved')"
);
$todayReservationsStmt->execute([$today]);
$todayReservations = (int)($todayReservationsStmt->fetch()['cnt'] ?? 0);

$upcomingReservationsStmt = $pdo->prepare(
    "SELECT COUNT(*) AS cnt
     FROM usages u
     INNER JOIN applications a ON a.id = u.application_id
     WHERE u.use_date > ?
       AND a.status IN ('pending', 'approved')"
);
$upcomingReservationsStmt->execute([$today]);
$upcomingReservations = (int)($upcomingReservationsStmt->fetch()['cnt'] ?? 0);

$monthlyRevenueStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(a.total_amount), 0) AS total
     FROM applications a
     INNER JOIN usages u ON u.application_id = a.id
     WHERE u.use_date BETWEEN ? AND ?
       AND a.status IN ('approved')
       AND a.payment_status = 'paid'"
);
$monthlyRevenueStmt->execute([$monthStart, $monthEnd]);
$monthlyRevenue = (int)($monthlyRevenueStmt->fetch()['total'] ?? 0);

$recentApplicationsStmt = $pdo->query(
    "SELECT
        a.id,
        a.event_name,
        a.applicant_representative,
        a.total_amount,
        a.payment_status,
        a.created_at
     FROM applications a
     ORDER BY a.created_at DESC
     LIMIT 5"
);
$recentApplications = $recentApplicationsStmt->fetchAll();

$todayUsageStmt = $pdo->prepare(
    "SELECT
        u.id,
        r.name AS room_name,
        a.event_name,
        u.use_morning,
        u.use_afternoon,
        u.use_evening,
        u.ac_requested
     FROM usages u
     INNER JOIN rooms r ON r.id = u.room_id
     INNER JOIN applications a ON a.id = u.application_id
     WHERE u.use_date = ?
       AND a.status IN ('pending', 'approved')
     ORDER BY u.id DESC
     LIMIT 8"
);
$todayUsageStmt->execute([$today]);
$todayUsages = $todayUsageStmt->fetchAll();
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
        <div class="group-title">コミュニケーション</div>
        <a href="/announcements_admin.php">お知らせ管理</a>
        <div class="group-title">施設管理</div>
        <a href="/admin.php">部屋管理（モック）</a>
        <a href="/equipment_admin.php">設備管理</a>
        <a href="/closed_dates_admin.php">休館日管理</a>
    </aside>

    <main class="staff-main">
        <section style="margin-bottom: 1rem;">
            <h2 class="page-title">ダッシュボード</h2>
            <p class="page-description">Node版の職員画面レイアウトを意識したPHPモック</p>
        </section>

        <section class="stats-grid">
            <article class="stat-box bg1"><div>本日の予約</div><div class="num"><?= $todayReservations ?></div><small>date: <?= h($today) ?></small></article>
            <article class="stat-box bg2"><div>今後の予約</div><div class="num"><?= $upcomingReservations ?></div><small>明日以降</small></article>
            <article class="stat-box bg3"><div>未決済</div><div class="num"><?= $unpaidCount ?></div><small>全期間</small></article>
            <article class="stat-box bg4"><div>今月売上（入金済）</div><div class="num">¥<?= number_format($monthlyRevenue) ?></div><small><?= h($monthStart) ?> - <?= h($monthEnd) ?></small></article>
        </section>

        <section class="two-column-cards">
            <article class="card">
                <h3 class="card-title">最近の申請</h3>
                <?php if (count($recentApplications) === 0): ?>
                    <p class="room-meta">申請データがありません。</p>
                <?php else: ?>
                    <ul class="dash-list">
                        <?php foreach ($recentApplications as $app): ?>
                            <li>
                                <strong>#<?= (int)$app['id'] ?> <?= h($app['event_name']) ?></strong><br>
                                <small><?= h($app['applicant_representative']) ?> / <?= number_format((int)$app['total_amount']) ?>円 / <?= h($app['payment_status']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>

            <article class="card">
                <h3 class="card-title">本日の利用</h3>
                <?php if (count($todayUsages) === 0): ?>
                    <p class="room-meta">本日の利用予定はありません。</p>
                <?php else: ?>
                    <ul class="dash-list">
                        <?php foreach ($todayUsages as $u): ?>
                            <li>
                                <strong><?= h($u['room_name']) ?> / <?= h($u['event_name']) ?></strong><br>
                                <small>
                                    <?= (int)$u['use_morning'] === 1 ? '午前 ' : '' ?>
                                    <?= (int)$u['use_afternoon'] === 1 ? '午後 ' : '' ?>
                                    <?= (int)$u['use_evening'] === 1 ? '夜間 ' : '' ?>
                                    <?= (int)$u['ac_requested'] === 1 ? '/ 空調あり' : '' ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>
        </section>

        <section class="card">
            <h3 class="card-title">クイックアクション</h3>
            <div class="quick-actions">
                <a href="/admin.php" class="btn btn-primary">予約管理</a>
                <a href="/equipment_admin.php" class="btn btn-outline">設備管理</a>
                <a href="/closed_dates_admin.php" class="btn btn-outline">休館日管理</a>
            </div>
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

