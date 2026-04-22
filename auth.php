<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

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

