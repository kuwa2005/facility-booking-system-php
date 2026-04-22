<?php

declare(strict_types=1);

function loadEnv(string $path): array
{
    $env = [];
    if (!is_file($path)) {
        return $env;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $env;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $env[$key] = $value;
    }

    return $env;
}

function envValue(array $env, string $key, string $default = ''): string
{
    return array_key_exists($key, $env) ? $env[$key] : $default;
}

