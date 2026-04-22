<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$pdo = getPdo();
$rooms = $pdo->query(
    "SELECT id, name, capacity, base_price_morning, base_price_afternoon, base_price_evening
     FROM rooms WHERE is_active = 1 ORDER BY id"
)->fetchAll();
$equipmentList = $pdo->query(
    "SELECT id, category, name, price_type, unit_price, max_quantity
     FROM equipment
     WHERE enabled = 1
     ORDER BY category, id"
)->fetchAll();
$slots = reservationSlots();
$extensions = extensionSlots();
$flash = $_GET['flash'] ?? '';
$user = currentUser();
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
<header class="public-header">
    <div class="container public-header-inner">
        <h1 class="brand"><a href="/index.php">施設予約システム（PHP DEMO）</a></h1>
        <nav class="public-nav">
            <a href="/index.php">ホーム</a>
            <a href="/rooms.php">施設一覧</a>
            <a href="/availability.php">空き状況</a>
            <a href="/index.php#booking-form">予約申請</a>
            <?php if ($user): ?>
                <a href="/my_page.php">マイページ</a>
                <a href="/my_reservations.php">予約一覧</a>
                <a href="/user_logout.php">ログアウト</a>
            <?php else: ?>
                <a href="/user_login.php">ユーザーログイン</a>
                <a href="/register.php">新規登録</a>
            <?php endif; ?>
            <a href="/login.php">職員</a>
        </nav>
    </div>
</header>

<section class="hero">
    <div class="container">
        <h2>施設をかんたん予約</h2>
        <p>Node版デザインをベースにしたPHP移植モック画面です</p>
    </div>
</section>

<main class="main-section">
    <div class="container">
        <?php if ($flash !== ''): ?>
            <div class="notice"><?= h($flash) ?></div>
        <?php endif; ?>

        <section id="rooms" class="card">
            <h2 class="card-title">利用可能な施設</h2>
            <div class="room-grid">
                <?php foreach ($rooms as $room): ?>
                    <article class="room-card">
                        <h3><?= h($room['name']) ?></h3>
                        <div class="room-meta">定員: <?= (int)$room['capacity'] ?> 名</div>
                        <div class="room-price">
                            ¥<?= number_format((int)$room['base_price_morning']) ?>〜
                        </div>
                        <p style="margin-top:0.8rem;">
                            <a class="btn btn-outline" href="/room_detail.php?id=<?= (int)$room['id'] ?>">詳細を見る</a>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
            <p><a class="btn btn-primary" href="/rooms.php">すべての施設を見る</a></p>
        </section>

        <section id="booking-form" class="card">
            <h2 class="card-title">予約申請フォーム</h2>
            <form method="post" action="/reserve.php" class="form-grid two-col">
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
                            <option value="<?= (int)$room['id'] ?>"><?= h($room['name']) ?> (定員 <?= (int)$room['capacity'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>利用日
                    <input type="date" name="use_date" required>
                </label>
                <fieldset>
                    <legend>時間帯（複数選択可）</legend>
                    <div class="checkbox-group">
                        <?php foreach ($slots as $key => $label): ?>
                            <label class="checkbox-inline"><input type="checkbox" name="slots[]" value="<?= h($key) ?>"> <?= h($label) ?></label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>延長時間帯（任意）</legend>
                    <div class="checkbox-group">
                        <?php foreach ($extensions as $key => $label): ?>
                            <label class="checkbox-inline"><input type="checkbox" name="extensions[]" value="<?= h($key) ?>"> <?= h($label) ?></label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <label class="checkbox-inline"><input type="checkbox" name="ac_requested" value="1"> 空調を使用する</label>
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
                <fieldset style="grid-column: 1 / -1;">
                    <legend>設備（任意）</legend>
                    <div class="equipment-grid">
                        <?php foreach ($equipmentList as $eq): ?>
                            <label class="equipment-item">
                                <span>
                                    <?= h($eq['name']) ?>
                                    <small>(<?= h($eq['price_type']) ?> / <?= number_format((int)$eq['unit_price']) ?>円)</small>
                                </span>
                                <input type="number" name="equipment_qty[<?= (int)$eq['id'] ?>]" min="0" max="<?= (int)$eq['max_quantity'] ?>" value="0">
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <button type="submit" class="btn btn-primary btn-block" style="grid-column: 1 / -1;">予約を申請する</button>
            </form>
        </section>

        <section class="feature-grid">
            <article class="feature-card">
                <span class="feature-icon">📅</span>
                <h3>簡単予約</h3>
                <p>24時間オンラインで申請可能</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">💳</span>
                <h3>決済対応予定</h3>
                <p>Node版の支払機能を順次移植</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">📊</span>
                <h3>職員管理</h3>
                <p>管理画面で状態更新・運用管理</p>
            </article>
        </section>
    </div>
</main>

<footer class="public-footer">
    <div class="container">
        <p>&copy; 2026 施設予約システム（PHP DEMO）</p>
        <p>
            <a href="/index.php">ホーム</a> |
            <a href="/user_login.php">ユーザーログイン</a> |
            <a href="/register.php">新規登録</a> |
            <a href="/login.php">職員ログイン</a>
        </p>
    </div>
</footer>
</body>
</html>

