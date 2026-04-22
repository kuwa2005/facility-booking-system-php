<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin.php');
}

$id = (int)($_POST['id'] ?? 0);
$status = trim((string)($_POST['status'] ?? ''));
$paymentStatus = trim((string)($_POST['payment_status'] ?? 'unpaid'));
$note = trim((string)($_POST['note'] ?? ''));

$allowed = ['pending', 'approved', 'rejected', 'cancelled'];
$allowedPayment = ['unpaid', 'paid', 'refunded'];
if ($id <= 0 || !in_array($status, $allowed, true) || !in_array($paymentStatus, $allowedPayment, true)) {
    redirect('/admin.php');
}

$pdo = getPdo();
$stmt = $pdo->prepare("UPDATE applications SET status = ?, payment_status = ?, note = ? WHERE id = ?");
$stmt->execute([$status, $paymentStatus, $note === '' ? null : $note, $id]);

redirect('/admin.php');

