<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function startSessionIfNeeded(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function getAdminCredentials(): array
{
    $env = loadEnv(__DIR__ . '/.env');
    return [
        'email' => envValue($env, 'ADMIN_EMAIL', 'admin@example.com'),
        'password' => envValue($env, 'ADMIN_PASSWORD', 'admin123'),
    ];
}

function isAdminLoggedIn(): bool
{
    startSessionIfNeeded();
    return !empty($_SESSION['is_admin']);
}

function requireAdminLogin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function isUserLoggedIn(): bool
{
    startSessionIfNeeded();
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array
{
    startSessionIfNeeded();
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => (int)$_SESSION['user_id'],
        'name' => (string)($_SESSION['user_name'] ?? ''),
        'email' => (string)($_SESSION['user_email'] ?? ''),
    ];
}

function requireUserLogin(): void
{
    if (!isUserLoggedIn()) {
        header('Location: /user_login.php');
        exit;
    }
}

function registerUser(string $name, string $email, string $password): array
{
    $pdo = getPdo();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'メールアドレス形式が正しくありません'];
    }
    if (mb_strlen($name) < 2) {
        return [false, '名前は2文字以上で入力してください'];
    }
    if (strlen($password) < 8) {
        return [false, 'パスワードは8文字以上で入力してください'];
    }

    $exists = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $exists->execute([$email]);
    if ($exists->fetch()) {
        return [false, 'このメールアドレスは既に登録されています'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, password_hash, is_active, email_verified)
         VALUES (?, ?, ?, 1, 1)"
    );
    $stmt->execute([$name, $email, $hash]);
    return [true, '会員登録が完了しました'];
}

function loginUser(string $email, string $password): array
{
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        "SELECT id, name, email, password_hash, is_active
         FROM users
         WHERE email = ?
         LIMIT 1"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        return [false, 'ログイン情報が正しくありません'];
    }
    if ((int)$user['is_active'] !== 1) {
        return [false, 'このアカウントは現在無効です'];
    }

    startSessionIfNeeded();
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = (string)$user['name'];
    $_SESSION['user_email'] = (string)$user['email'];

    return [true, 'ログインしました'];
}

function logoutUser(): void
{
    startSessionIfNeeded();
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
}

