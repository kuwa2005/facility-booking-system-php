<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

$applicantName = trim((string)($_POST['applicant_name'] ?? ''));
$applicantEmail = trim((string)($_POST['applicant_email'] ?? ''));
$applicantPhone = trim((string)($_POST['applicant_phone'] ?? ''));
$eventName = trim((string)($_POST['event_name'] ?? ''));
$roomId = (int)($_POST['room_id'] ?? 0);
$useDate = trim((string)($_POST['use_date'] ?? ''));
$slots = $_POST['slots'] ?? [];
$extensions = $_POST['extensions'] ?? [];
$acRequested = (int)($_POST['ac_requested'] ?? 0) === 1;
$entranceFeeType = trim((string)($_POST['entrance_fee_type'] ?? 'free'));
$entranceFeeAmount = (int)($_POST['entrance_fee_amount'] ?? 0);
$purpose = trim((string)($_POST['purpose'] ?? ''));

$validSlots = array_keys(reservationSlots());
$validExtensions = array_keys(extensionSlots());
$slotSet = [];
foreach ((array)$slots as $slot) {
    $slotName = trim((string)$slot);
    if (in_array($slotName, $validSlots, true)) {
        $slotSet[$slotName] = true;
    }
}
$extensionSet = [];
foreach ((array)$extensions as $ext) {
    $extName = trim((string)$ext);
    if (in_array($extName, $validExtensions, true)) {
        $extensionSet[$extName] = true;
    }
}

if (
    $applicantName === '' ||
    $applicantEmail === '' ||
    $applicantPhone === '' ||
    $eventName === '' ||
    $roomId <= 0 ||
    $useDate === '' ||
    count($slotSet) === 0 ||
    $purpose === ''
) {
    redirect('/index.php?flash=' . urlencode('入力内容に不備があります'));
}

if (!filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
    redirect('/index.php?flash=' . urlencode('メールアドレス形式が正しくありません'));
}
if (!in_array($entranceFeeType, ['free', 'paid'], true)) {
    $entranceFeeType = 'free';
}
if ($entranceFeeType === 'free') {
    $entranceFeeAmount = 0;
}
if ($entranceFeeAmount < 0) {
    $entranceFeeAmount = 0;
}

$pdo = getPdo();

$checkRoom = $pdo->prepare(
    "SELECT id, base_price_morning, base_price_afternoon, base_price_evening, extension_price_midday, extension_price_evening, ac_price_per_hour
     FROM rooms WHERE id = ? AND is_active = 1"
);
$checkRoom->execute([$roomId]);
$room = $checkRoom->fetch();
if (!$room) {
    redirect('/index.php?flash=' . urlencode('部屋が見つかりません'));
}

$dup = $pdo->prepare(
    "SELECT u.id
     FROM usages u
     INNER JOIN applications a ON a.id = u.application_id
     WHERE u.room_id = ? AND u.use_date = ?
       AND a.status IN ('pending', 'approved')
       AND (
         (u.use_morning = 1 AND ? = 1) OR
         (u.use_afternoon = 1 AND ? = 1) OR
         (u.use_evening = 1 AND ? = 1)
       )
     LIMIT 1"
);
$dup->execute([
    $roomId,
    $useDate,
    isset($slotSet['morning']) ? 1 : 0,
    isset($slotSet['afternoon']) ? 1 : 0,
    isset($slotSet['night']) ? 1 : 0,
]);
if ($dup->fetch()) {
    redirect('/index.php?flash=' . urlencode('その時間帯は既に申請または承認済みです'));
}

$roomCharge = 0;
if (isset($slotSet['morning'])) {
    $roomCharge += (int)$room['base_price_morning'];
}
if (isset($slotSet['afternoon'])) {
    $roomCharge += (int)$room['base_price_afternoon'];
}
if (isset($slotSet['night'])) {
    $roomCharge += (int)$room['base_price_evening'];
}
$extensionCharge = 0;
if (isset($extensionSet['midday'])) {
    $extensionCharge += (int)$room['extension_price_midday'];
}
if (isset($extensionSet['evening'])) {
    $extensionCharge += (int)$room['extension_price_evening'];
}
$acCharge = $acRequested ? (int)$room['ac_price_per_hour'] * 3 : 0;

$multiplier = 1.0;
if ($entranceFeeType === 'paid' && $entranceFeeAmount >= 1 && $entranceFeeAmount <= 3000) {
    $multiplier = 1.5;
} elseif ($entranceFeeType === 'paid' && $entranceFeeAmount > 3000) {
    $multiplier = 2.0;
}

$roomChargeAfterMultiplier = (int)round(($roomCharge + $extensionCharge) * $multiplier);
$subtotal = $roomChargeAfterMultiplier + $acCharge;

$pdo->beginTransaction();
try {
    $appStmt = $pdo->prepare(
        "INSERT INTO applications (
            applicant_representative, applicant_phone, applicant_email,
            event_name, event_description, entrance_fee_type, entrance_fee_amount, total_amount
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $appStmt->execute([
        $applicantName,
        $applicantPhone,
        $applicantEmail,
        $eventName,
        $purpose,
        $entranceFeeType,
        $entranceFeeAmount,
        $subtotal,
    ]);
    $applicationId = (int)$pdo->lastInsertId();

    $usageStmt = $pdo->prepare(
        "INSERT INTO usages (
            application_id, room_id, use_date,
            use_morning, use_afternoon, use_evening,
            use_midday_extension, use_evening_extension,
            ac_requested, room_charge, extension_charge, ac_charge, subtotal_amount
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $usageStmt->execute([
        $applicationId,
        $roomId,
        $useDate,
        isset($slotSet['morning']) ? 1 : 0,
        isset($slotSet['afternoon']) ? 1 : 0,
        isset($slotSet['night']) ? 1 : 0,
        isset($extensionSet['midday']) ? 1 : 0,
        isset($extensionSet['evening']) ? 1 : 0,
        $acRequested ? 1 : 0,
        $roomChargeAfterMultiplier,
        $extensionCharge,
        $acCharge,
        $subtotal,
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect('/index.php?flash=' . urlencode('予約申請に失敗しました'));
}

redirect('/index.php?flash=' . urlencode('予約申請を受け付けました'));

