<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$error = '';
$notice = '';

if ($token === '') {
    $error = '無効なアクセスです';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token !== '') {
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    if ($password !== $passwordConfirm) {
        $error = 'パスワード確認が一致しません';
    } elseif (strlen($password) < 8) {
        $error = 'パスワードは8文字以上で入力してください';
    } else {
        $pdo = getPdo();
        $stmt = $pdo->prepare(
            "SELECT id FROM users
             WHERE reset_token = ?
               AND reset_token_expires_at IS NOT NULL
               AND reset_token_expires_at >= NOW()
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'トークンが無効または期限切れです';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $up = $pdo->prepare(
                "UPDATE users
                 SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL
                 WHERE id = ?"
            );
            $up->execute([$hash, (int)$user['id']]);
            redirect('/user_login.php?notice=' . urlencode('パスワードを更新しました'));
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新しいパスワード設定</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="public-header">
    <div class="container public-header-inner">
        <h1 class="brand"><a href="/index.php">施設予約システム（PHP DEMO）</a></h1>
    </div>
</header>

<main class="main-section">
    <div class="container" style="max-width: 620px;">
        <section class="card">
            <h2 class="card-title">新しいパスワード設定</h2>
            <?php if ($error !== ''): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
            <?php if ($notice !== ''): ?><div class="notice"><?= h($notice) ?></div><?php endif; ?>
            <?php if ($token !== ''): ?>
                <form method="post" class="form-grid">
                    <input type="hidden" name="token" value="<?= h($token) ?>">
                    <label>新しいパスワード
                        <input type="password" name="password" required minlength="8">
                    </label>
                    <label>新しいパスワード（確認）
                        <input type="password" name="password_confirm" required minlength="8">
                    </label>
                    <button type="submit" class="btn btn-primary btn-block">パスワードを更新</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>

