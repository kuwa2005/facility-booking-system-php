<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

startSessionIfNeeded();
if (isUserLoggedIn()) {
    redirect('/my_page.php');
}

$error = '';
$notice = trim((string)($_GET['notice'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    [$ok, $message] = loginUser($email, $password);
    if ($ok) {
        redirect('/my_page.php');
    }
    $error = $message;
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ユーザーログイン</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="public-header">
    <div class="container public-header-inner">
        <h1 class="brand"><a href="/index.php">施設予約システム（PHP DEMO）</a></h1>
        <nav class="public-nav">
            <a href="/index.php">ホーム</a>
            <a href="/register.php">新規登録</a>
        </nav>
    </div>
</header>

<main class="main-section">
    <div class="container" style="max-width: 560px;">
        <section class="card">
            <h2 class="card-title">ユーザーログイン</h2>
            <?php if ($error !== ''): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
            <?php if ($notice !== ''): ?><div class="notice"><?= h($notice) ?></div><?php endif; ?>
            <form method="post" class="form-grid">
                <label>メールアドレス
                    <input type="email" name="email" required maxlength="190">
                </label>
                <label>パスワード
                    <input type="password" name="password" required>
                </label>
                <button type="submit" class="btn btn-primary btn-block">ログイン</button>
            </form>
            <p style="margin-top: 0.8rem;">
                <a href="/forgot_password.php" style="color: #2563eb;">パスワードを忘れた場合</a>
            </p>
        </section>
    </div>
</main>
</body>
</html>

