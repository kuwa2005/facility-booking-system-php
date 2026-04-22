<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

startSessionIfNeeded();

if (isAdminLoggedIn()) {
    redirect('/admin.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));
    $credentials = getAdminCredentials();

    if ($email === $credentials['email'] && $password === $credentials['password']) {
        $_SESSION['is_admin'] = true;
        redirect('/admin.php');
    }

    $error = 'ログイン情報が正しくありません';
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ログイン</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<main class="container narrow">
    <section class="card">
        <h1>管理者ログイン</h1>
        <?php if ($error !== ''): ?>
            <div class="error"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" class="form-grid">
            <label>メールアドレス
                <input type="email" name="email" required>
            </label>
            <label>パスワード
                <input type="password" name="password" required>
            </label>
            <button type="submit">ログイン</button>
        </form>
        <a href="/index.php">利用者画面に戻る</a>
    </section>
</main>
</body>
</html>

