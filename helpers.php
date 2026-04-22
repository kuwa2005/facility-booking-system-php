<?php

declare(strict_types=1);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function reservationSlots(): array
{
    return [
        'morning' => '午前 (09:00-12:00)',
        'afternoon' => '午後 (13:00-17:00)',
        'night' => '夜間 (18:00-21:30)',
    ];
}

function extensionSlots(): array
{
    return [
        'midday' => '正午延長 (12:00-13:00)',
        'evening' => '夕方延長 (17:00-18:00)',
    ];
}

