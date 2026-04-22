<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

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
$equipmentQtyInput = (array)($_POST['equipment_qty'] ?? []);
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
$user = currentUser();
$userId = $user ? (int)$user['id'] : null;

$checkRoom = $pdo->prepare(
    "SELECT id, base_price_morning, base_price_afternoon, base_price_evening, extension_price_midday, extension_price_evening, ac_price_per_hour
     FROM rooms WHERE id = ? AND is_active = 1"
);
$checkRoom->execute([$roomId]);
$room = $checkRoom->fetch();
if (!$room) {
    redirect('/index.php?flash=' . urlencode('部屋が見つかりません'));
}

$closedStmt = $pdo->prepare("SELECT id, reason FROM closed_dates WHERE date = ? LIMIT 1");
$closedStmt->execute([$useDate]);
$closed = $closedStmt->fetch();
if ($closed) {
    $reason = trim((string)($closed['reason'] ?? ''));
    $message = $reason !== '' ? '選択日が休館日のため予約できません: ' . $reason : '選択日が休館日のため予約できません';
    redirect('/index.php?flash=' . urlencode($message));
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
$mainSlotCount = count($slotSet);

$selectedEquipment = [];
foreach ($equipmentQtyInput as $eqIdRaw => $qtyRaw) {
    $eqId = (int)$eqIdRaw;
    $qty = (int)$qtyRaw;
    if ($eqId > 0 && $qty > 0) {
        $selectedEquipment[$eqId] = $qty;
    }
}

$equipmentCharge = 0;
$equipmentLines = [];
if (count($selectedEquipment) > 0) {
    $ids = array_keys($selectedEquipment);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $eqStmt = $pdo->prepare(
        "SELECT id, name, price_type, unit_price, max_quantity
         FROM equipment
         WHERE enabled = 1 AND id IN ($placeholders)"
    );
    $eqStmt->execute($ids);
    $eqRows = $eqStmt->fetchAll();

    foreach ($eqRows as $eq) {
        $eqId = (int)$eq['id'];
        $qty = min($selectedEquipment[$eqId] ?? 0, (int)$eq['max_quantity']);
        if ($qty <= 0) {
            continue;
        }
        $lineAmount = 0;
        if ($eq['price_type'] === 'per_slot') {
            $lineAmount = (int)$eq['unit_price'] * $qty * $mainSlotCount;
        } elseif ($eq['price_type'] === 'flat') {
            $lineAmount = (int)$eq['unit_price'] * $qty;
        } else {
            $lineAmount = 0;
        }

        $equipmentCharge += $lineAmount;
        $equipmentLines[] = [
            'equipment_id' => $eqId,
            'quantity' => $qty,
            'slot_count' => $mainSlotCount,
            'line_amount' => $lineAmount,
        ];
    }
}

$multiplier = 1.0;
if ($entranceFeeType === 'paid' && $entranceFeeAmount >= 1 && $entranceFeeAmount <= 3000) {
    $multiplier = 1.5;
} elseif ($entranceFeeType === 'paid' && $entranceFeeAmount > 3000) {
    $multiplier = 2.0;
}

$roomChargeAfterMultiplier = (int)round(($roomCharge + $extensionCharge) * $multiplier);
$subtotal = $roomChargeAfterMultiplier + $equipmentCharge + $acCharge;

$pdo->beginTransaction();
try {
    $appStmt = $pdo->prepare(
        "INSERT INTO applications (
            user_id,
            applicant_representative, applicant_phone, applicant_email,
            event_name, event_description, entrance_fee_type, entrance_fee_amount, total_amount
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $appStmt->execute([
        $userId,
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
            ac_requested, room_charge, extension_charge, equipment_charge, ac_charge, subtotal_amount
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
        $equipmentCharge,
        $acCharge,
        $subtotal,
    ]);
    $usageId = (int)$pdo->lastInsertId();

    if (count($equipmentLines) > 0) {
        $eqInsert = $pdo->prepare(
            "INSERT INTO usage_equipment (usage_id, equipment_id, quantity, slot_count, line_amount)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($equipmentLines as $line) {
            $eqInsert->execute([
                $usageId,
                $line['equipment_id'],
                $line['quantity'],
                $line['slot_count'],
                $line['line_amount'],
            ]);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect('/index.php?flash=' . urlencode('予約申請に失敗しました'));
}

redirect('/index.php?flash=' . urlencode('予約申請を受け付けました'));

