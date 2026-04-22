<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAdminLogin();
$pdo = getPdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if ($action === 'create') {
        $title = trim((string)($_POST['title'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        if ($title !== '' && $body !== '') {
            $stmt = $pdo->prepare(
                "INSERT INTO announcements (title, body, is_published, published_at)
                 VALUES (?, ?, 0, NULL)"
            );
            $stmt->execute([$title, $body]);
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        if ($id > 0 && $title !== '' && $body !== '') {
            $stmt = $pdo->prepare("UPDATE announcements SET title = ?, body = ? WHERE id = ?");
            $stmt->execute([$title, $body, $id]);
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare(
                "UPDATE announcements
                 SET is_published = CASE WHEN is_published = 1 THEN 0 ELSE 1 END,
                     published_at = CASE WHEN is_published = 1 THEN NULL ELSE NOW() END
                 WHERE id = ?"
            );
            $stmt->execute([$id]);
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
        }
    }

    redirect('/announcements_admin.php');
}

$rows = $pdo->query("SELECT * FROM announcements ORDER BY COALESCE(published_at, created_at) DESC, id DESC")->fetchAll();
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>お知らせ管理</title>
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
        <div class="group-title">コミュニケーション</div>
        <a href="/announcements_admin.php" class="active">お知らせ管理</a>
        <div class="group-title">施設管理</div>
        <a href="/equipment_admin.php">設備管理</a>
        <a href="/closed_dates_admin.php">休館日管理</a>
    </aside>

    <main class="staff-main">
        <section class="card">
            <h2 class="card-title">お知らせを作成</h2>
            <form method="post" class="form-grid">
                <input type="hidden" name="action" value="create">
                <label>タイトル
                    <input type="text" name="title" maxlength="255" required>
                </label>
                <label>本文
                    <textarea name="body" rows="5" required></textarea>
                </label>
                <button class="btn btn-primary" type="submit">作成する</button>
            </form>
        </section>

        <section class="card">
            <h2 class="card-title">お知らせ一覧</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>状態</th>
                        <th>タイトル/本文</th>
                        <th>公開日</th>
                        <th>更新</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td><?= (int)$row['is_published'] === 1 ? '公開' : '下書き' ?></td>
                            <td><strong><?= h($row['title']) ?></strong><br><small><?= nl2br(h((string)$row['body'])) ?></small></td>
                            <td><?= h((string)($row['published_at'] ?? '-')) ?></td>
                            <td>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <input type="text" name="title" value="<?= h($row['title']) ?>" required>
                                    <textarea name="body" rows="3" required><?= h((string)$row['body']) ?></textarea>
                                    <button class="btn btn-primary" type="submit">保存</button>
                                </form>
                            </td>
                            <td>
                                <form method="post" style="margin-bottom:0.4rem;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <button class="btn btn-outline" type="submit"><?= (int)$row['is_published'] === 1 ? '非公開' : '公開' ?></button>
                                </form>
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

