<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function getPdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $env = loadEnv(__DIR__ . '/.env');
    $host = envValue($env, 'DB_HOST', 'localhost');
    $port = envValue($env, 'DB_PORT', '3306');
    $dbName = envValue($env, 'DB_NAME');
    $user = envValue($env, 'DB_USER');
    $password = envValue($env, 'DB_PASSWORD');

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName);
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

