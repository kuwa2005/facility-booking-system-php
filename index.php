<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$pdo = getPdo();
$rooms = $pdo->query(
    "SELECT id, name, capacity, base_price_morning, base_price_afternoon, base_price_evening
     FROM rooms WHERE is_active = 1 ORDER BY id"
)->fetchAll();
$slots = reservationSlots();
$extensions = extensionSlots();
$flash = $_GET['flash'] ?? '';
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>施設予約システム</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<main class="container">
    <header class="header">
        <h1>施設予約システム (PHP版)</h1>
        <a class="link-button" href="/login.php">管理者ログイン</a>
    </header>

    <?php if ($flash !== ''): ?>
        <div class="notice"><?= h($flash) ?></div>
    <?php endif; ?>

    <section class="card">
        <h2>予約申請フォーム</h2>
        <form method="post" action="/reserve.php" class="form-grid">
            <label>申請者名
                <input type="text" name="applicant_name" required maxlength="120">
            </label>
            <label>メールアドレス
                <input type="email" name="applicant_email" required maxlength="190">
            </label>
            <label>電話番号
                <input type="text" name="applicant_phone" required maxlength="50">
            </label>
            <label>イベント名
                <input type="text" name="event_name" required maxlength="255">
            </label>
            <label>部屋
                <select name="room_id" required>
                    <option value="">選択してください</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= (int)$room['id'] ?>">
                            <?= h($room['name']) ?> (定員 <?= (int)$room['capacity'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>利用日
                <input type="date" name="use_date" required>
            </label>
            <fieldset>
                <legend>時間帯（複数選択可）</legend>
                <?php foreach ($slots as $key => $label): ?>
                    <label><input type="checkbox" name="slots[]" value="<?= h($key) ?>"> <?= h($label) ?></label>
                <?php endforeach; ?>
            </fieldset>
            <fieldset>
                <legend>延長時間帯（任意）</legend>
                <?php foreach ($extensions as $key => $label): ?>
                    <label><input type="checkbox" name="extensions[]" value="<?= h($key) ?>"> <?= h($label) ?></label>
                <?php endforeach; ?>
            </fieldset>
            <label><input type="checkbox" name="ac_requested" value="1"> 空調を使用する</label>
            <label>入場料
                <select name="entrance_fee_type">
                    <option value="free">無料</option>
                    <option value="paid">有料</option>
                </select>
            </label>
            <label>入場料金額（有料時）
                <input type="number" name="entrance_fee_amount" min="0" value="0">
            </label>
            <label>利用目的
                <input type="text" name="purpose" required maxlength="255">
            </label>
            <button type="submit">予約を申請する</button>
        </form>
    </section>
</main>
</body>
</html>

