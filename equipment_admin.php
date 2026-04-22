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
        $stmt = $pdo->prepare(
            "INSERT INTO equipment (category, name, price_type, unit_price, max_quantity, enabled, remark)
             VALUES (?, ?, ?, ?, ?, 1, ?)"
        );
        $stmt->execute([
            trim((string)($_POST['category'] ?? 'other')),
            trim((string)($_POST['name'] ?? '')),
            trim((string)($_POST['price_type'] ?? 'per_slot')),
            max(0, (int)($_POST['unit_price'] ?? 0)),
            max(1, (int)($_POST['max_quantity'] ?? 1)),
            trim((string)($_POST['remark'] ?? '')),
        ]);
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare(
                "UPDATE equipment
                 SET category = ?, name = ?, price_type = ?, unit_price = ?, max_quantity = ?, remark = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                trim((string)($_POST['category'] ?? 'other')),
                trim((string)($_POST['name'] ?? '')),
                trim((string)($_POST['price_type'] ?? 'per_slot')),
                max(0, (int)($_POST['unit_price'] ?? 0)),
                max(1, (int)($_POST['max_quantity'] ?? 1)),
                trim((string)($_POST['remark'] ?? '')),
                $id,
            ]);
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE equipment SET enabled = CASE WHEN enabled=1 THEN 0 ELSE 1 END WHERE id = ?")->execute([$id]);
        }
    }

    redirect('/equipment_admin.php');
}

$rows = $pdo->query("SELECT * FROM equipment ORDER BY category, id")->fetchAll();
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>設備管理</title>
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
        <a href="/equipment_admin.php" class="active">設備管理</a>
    </aside>
    <main class="staff-main">
        <section class="card">
            <h2 class="card-title">設備を追加</h2>
            <form method="post" class="form-grid two-col">
                <input type="hidden" name="action" value="create">
                <label>カテゴリ
                    <select name="category">
                        <option value="stage">stage</option>
                        <option value="lighting">lighting</option>
                        <option value="sound">sound</option>
                        <option value="other">other</option>
                    </select>
                </label>
                <label>名称
                    <input name="name" required maxlength="255">
                </label>
                <label>価格種別
                    <select name="price_type">
                        <option value="per_slot">per_slot</option>
                        <option value="flat">flat</option>
                        <option value="free">free</option>
                    </select>
                </label>
                <label>単価
                    <input type="number" name="unit_price" min="0" value="0">
                </label>
                <label>最大数量
                    <input type="number" name="max_quantity" min="1" value="1">
                </label>
                <label>備考
                    <input name="remark" maxlength="255">
                </label>
                <button class="btn btn-primary" type="submit" style="grid-column:1/-1;">追加する</button>
            </form>
        </section>

        <section class="card">
            <h2 class="card-title">設備一覧</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>状態</th><th>名称</th><th>料金設定</th><th>更新</th><th>有効/無効</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int)$r['id'] ?></td>
                            <td><?= (int)$r['enabled'] === 1 ? 'enabled' : 'disabled' ?></td>
                            <td><?= h($r['category']) ?> / <?= h($r['name']) ?></td>
                            <td><?= h($r['price_type']) ?> / <?= number_format((int)$r['unit_price']) ?>円 / max <?= (int)$r['max_quantity'] ?></td>
                            <td>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input name="name" value="<?= h($r['name']) ?>" required>
                                    <select name="category">
                                        <option value="stage" <?= $r['category'] === 'stage' ? 'selected' : '' ?>>stage</option>
                                        <option value="lighting" <?= $r['category'] === 'lighting' ? 'selected' : '' ?>>lighting</option>
                                        <option value="sound" <?= $r['category'] === 'sound' ? 'selected' : '' ?>>sound</option>
                                        <option value="other" <?= $r['category'] === 'other' ? 'selected' : '' ?>>other</option>
                                    </select>
                                    <select name="price_type">
                                        <option value="per_slot" <?= $r['price_type'] === 'per_slot' ? 'selected' : '' ?>>per_slot</option>
                                        <option value="flat" <?= $r['price_type'] === 'flat' ? 'selected' : '' ?>>flat</option>
                                        <option value="free" <?= $r['price_type'] === 'free' ? 'selected' : '' ?>>free</option>
                                    </select>
                                    <input type="number" name="unit_price" min="0" value="<?= (int)$r['unit_price'] ?>">
                                    <input type="number" name="max_quantity" min="1" value="<?= (int)$r['max_quantity'] ?>">
                                    <input name="remark" value="<?= h((string)$r['remark']) ?>">
                                    <button class="btn btn-primary" type="submit">保存</button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button class="btn btn-outline" type="submit"><?= (int)$r['enabled'] === 1 ? '無効化' : '有効化' ?></button>
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

