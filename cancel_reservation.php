<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireUserLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/my_reservations.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('/my_reservations.php?flash=' . urlencode('不正なリクエストです'));
}

$pdo = getPdo();
$stmt = $pdo->prepare(
    "UPDATE applications
     SET status = 'cancelled', updated_at = NOW()
     WHERE id = ? AND user_id = ? AND status IN ('pending', 'approved')"
);
$stmt->execute([$id, (int)$user['id']]);

if ($stmt->rowCount() < 1) {
    redirect('/my_reservations.php?flash=' . urlencode('キャンセルできませんでした'));
}

redirect('/my_reservations.php?flash=' . urlencode('予約をキャンセルしました'));

