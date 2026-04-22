<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$error = '';
$notice = '';
$devToken = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'メールアドレス形式が正しくありません';
    } else {
        $pdo = getPdo();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(16));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            $up = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
            $up->execute([$token, $expiresAt, (int)$user['id']]);
            $devToken = $token;
        }

        $notice = 'パスワード再設定情報を受け付けました。';
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワード再設定</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="public-header">
    <div class="container public-header-inner">
        <h1 class="brand"><a href="/index.php">施設予約システム（PHP DEMO）</a></h1>
        <nav class="public-nav">
            <a href="/index.php">ホーム</a>
            <a href="/user_login.php">ログイン</a>
        </nav>
    </div>
</header>

<main class="main-section">
    <div class="container" style="max-width: 620px;">
        <section class="card">
            <h2 class="card-title">パスワード再設定</h2>
            <?php if ($error !== ''): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
            <?php if ($notice !== ''): ?><div class="notice"><?= h($notice) ?></div><?php endif; ?>
            <form method="post" class="form-grid">
                <label>登録メールアドレス
                    <input type="email" name="email" required maxlength="190">
                </label>
                <button type="submit" class="btn btn-primary btn-block">再設定情報を発行</button>
            </form>

            <?php if ($devToken !== ''): ?>
                <div class="notice" style="margin-top: 1rem;">
                    開発モック: 再設定リンク  
                    <a style="color:#166534;" href="/reset_password.php?token=<?= h($devToken) ?>">/reset_password.php?token=<?= h($devToken) ?></a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>

