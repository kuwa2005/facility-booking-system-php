<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAdminLogin();
$pdo = getPdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if ($action === 'add') {
        $date = trim((string)($_POST['date'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($date !== '') {
            $stmt = $pdo->prepare("INSERT INTO closed_dates (date, reason) VALUES (?, ?) ON DUPLICATE KEY UPDATE reason = VALUES(reason)");
            $stmt->execute([$date, $reason === '' ? null : $reason]);
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM closed_dates WHERE id = ?");
            $stmt->execute([$id]);
        }
    }

    redirect('/closed_dates_admin.php');
}

$rows = $pdo->query("SELECT id, date, reason, created_at FROM closed_dates ORDER BY date ASC")->fetchAll();
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>休館日管理</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="staff-header">
    <div class="staff-header-inner">
        <h1>施設予約システム（DEMO） - 職員管理画面</h1>
        <div class="staff-header-links">
            <a class="link-button" href="/admin.php">ダッシュボード</a>
            <a class="link-button danger" href="/logout.php">ログアウト</a>
        </div>
    </div>
</header>

<div class="staff-layout">
    <aside class="staff-sidebar">
        <div class="group-title">メインメニュー</div>
        <a href="/admin.php">ダッシュボード</a>
        <a href="/admin.php">予約管理</a>
        <div class="group-title">施設管理</div>
        <a href="/equipment_admin.php">設備管理</a>
        <a href="/closed_dates_admin.php" class="active">休館日管理</a>
    </aside>

    <main class="staff-main">
        <section class="card">
            <h2 class="card-title">休館日を追加</h2>
            <form method="post" class="form-grid two-col">
                <input type="hidden" name="action" value="add">
                <label>休館日
                    <input type="date" name="date" required>
                </label>
                <label>理由
                    <input type="text" name="reason" maxlength="255" placeholder="例: 年末年始、点検">
                </label>
                <button class="btn btn-primary" type="submit" style="grid-column:1/-1;">登録する</button>
            </form>
        </section>

        <section class="card">
            <h2 class="card-title">登録済み休館日</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>日付</th>
                        <th>理由</th>
                        <th>登録日</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td><?= h($row['date']) ?></td>
                            <td><?= h((string)$row['reason']) ?></td>
                            <td><?= h((string)$row['created_at']) ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('削除しますか？');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <button class="btn btn-danger" type="submit">削除</button>
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

