<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

startSessionIfNeeded();
if (isUserLoggedIn()) {
    redirect('/my_page.php');
}

$error = '';
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    if ($password !== $passwordConfirm) {
        $error = 'パスワード確認が一致しません';
    } else {
        [$ok, $message] = registerUser($name, $email, $password);
        if ($ok) {
            $notice = $message . ' ログイン画面からサインインしてください。';
        } else {
            $error = $message;
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録</title>
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
            <h2 class="card-title">新規会員登録</h2>
            <?php if ($error !== ''): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
            <?php if ($notice !== ''): ?><div class="notice"><?= h($notice) ?></div><?php endif; ?>
            <form method="post" class="form-grid">
                <label>氏名
                    <input type="text" name="name" required maxlength="120">
                </label>
                <label>メールアドレス
                    <input type="email" name="email" required maxlength="190">
                </label>
                <label>パスワード（8文字以上）
                    <input type="password" name="password" required minlength="8">
                </label>
                <label>パスワード確認
                    <input type="password" name="password_confirm" required minlength="8">
                </label>
                <button type="submit" class="btn btn-primary btn-block">登録する</button>
            </form>
            <p style="margin-top: 0.8rem;">すでにアカウントをお持ちの場合は <a href="/user_login.php" style="color: #2563eb;">ログイン</a></p>
        </section>
    </div>
</main>
</body>
</html>

