<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = getPdo();

function hasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS cnt
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    $row = $stmt->fetch();
    return (int)($row['cnt'] ?? 0) > 0;
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NULL,
        organization_name VARCHAR(255) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        email_verified TINYINT(1) NOT NULL DEFAULT 1,
        reset_token VARCHAR(120) NULL,
        reset_token_expires_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

if (!hasColumn($pdo, 'users', 'reset_token')) {
    $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(120) NULL");
}
if (!hasColumn($pdo, 'users', 'reset_token_expires_at')) {
    $pdo->exec("ALTER TABLE users ADD COLUMN reset_token_expires_at DATETIME NULL");
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        capacity INT NOT NULL DEFAULT 0,
        base_fee INT NOT NULL DEFAULT 0,
        base_price_morning INT UNSIGNED NOT NULL DEFAULT 0,
        base_price_afternoon INT UNSIGNED NOT NULL DEFAULT 0,
        base_price_evening INT UNSIGNED NOT NULL DEFAULT 0,
        extension_price_midday INT UNSIGNED NOT NULL DEFAULT 0,
        extension_price_evening INT UNSIGNED NOT NULL DEFAULT 0,
        ac_price_per_hour INT UNSIGNED NOT NULL DEFAULT 0,
        description TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

if (!hasColumn($pdo, 'rooms', 'base_price_morning')) {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN base_price_morning INT UNSIGNED NOT NULL DEFAULT 0");
}
if (!hasColumn($pdo, 'rooms', 'base_price_afternoon')) {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN base_price_afternoon INT UNSIGNED NOT NULL DEFAULT 0");
}
if (!hasColumn($pdo, 'rooms', 'base_price_evening')) {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN base_price_evening INT UNSIGNED NOT NULL DEFAULT 0");
}
if (!hasColumn($pdo, 'rooms', 'extension_price_midday')) {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN extension_price_midday INT UNSIGNED NOT NULL DEFAULT 0");
}
if (!hasColumn($pdo, 'rooms', 'extension_price_evening')) {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN extension_price_evening INT UNSIGNED NOT NULL DEFAULT 0");
}
if (!hasColumn($pdo, 'rooms', 'ac_price_per_hour')) {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN ac_price_per_hour INT UNSIGNED NOT NULL DEFAULT 0");
}
if (!hasColumn($pdo, 'rooms', 'description')) {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN description TEXT NULL");
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        applicant_representative VARCHAR(120) NOT NULL,
        applicant_phone VARCHAR(50) NOT NULL,
        applicant_email VARCHAR(190) NOT NULL,
        event_name VARCHAR(255) NOT NULL,
        event_description TEXT NULL,
        entrance_fee_type ENUM('free', 'paid') NOT NULL DEFAULT 'free',
        entrance_fee_amount INT UNSIGNED NOT NULL DEFAULT 0,
        total_amount INT UNSIGNED NOT NULL DEFAULT 0,
        status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
        payment_status ENUM('unpaid', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
        note VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

if (!hasColumn($pdo, 'applications', 'user_id')) {
    $pdo->exec("ALTER TABLE applications ADD COLUMN user_id INT NULL");
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS usages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        room_id INT NOT NULL,
        use_date DATE NOT NULL,
        use_morning TINYINT(1) NOT NULL DEFAULT 0,
        use_afternoon TINYINT(1) NOT NULL DEFAULT 0,
        use_evening TINYINT(1) NOT NULL DEFAULT 0,
        use_midday_extension TINYINT(1) NOT NULL DEFAULT 0,
        use_evening_extension TINYINT(1) NOT NULL DEFAULT 0,
        ac_requested TINYINT(1) NOT NULL DEFAULT 0,
        ac_hours DECIMAL(4,1) DEFAULT NULL,
        room_charge INT UNSIGNED NOT NULL DEFAULT 0,
        extension_charge INT UNSIGNED NOT NULL DEFAULT 0,
        ac_charge INT UNSIGNED NOT NULL DEFAULT 0,
        subtotal_amount INT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_usage_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
        CONSTRAINT fk_usage_room FOREIGN KEY (room_id) REFERENCES rooms(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS equipment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category ENUM('stage', 'lighting', 'sound', 'other') NOT NULL DEFAULT 'other',
        name VARCHAR(255) NOT NULL,
        price_type ENUM('per_slot', 'flat', 'free') NOT NULL DEFAULT 'per_slot',
        unit_price INT UNSIGNED NOT NULL DEFAULT 0,
        max_quantity INT UNSIGNED NOT NULL DEFAULT 1,
        enabled TINYINT(1) NOT NULL DEFAULT 1,
        remark TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS closed_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL UNIQUE,
        reason VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$countStmt = $pdo->query("SELECT COUNT(*) AS cnt FROM rooms");
$count = (int)($countStmt->fetch()['cnt'] ?? 0);

if ($count === 0) {
    $insert = $pdo->prepare(
        "INSERT INTO rooms (
            name, capacity, base_fee,
            base_price_morning, base_price_afternoon, base_price_evening,
            extension_price_midday, extension_price_evening, ac_price_per_hour, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $insert->execute(['多目的ホール', 200, 0, 15000, 20000, 18000, 3000, 3000, 1000, '大型イベント対応の多目的ホール']);
    $insert->execute(['小会議室1', 20, 0, 3000, 4000, 3500, 500, 500, 300, '少人数向け会議室']);
    $insert->execute(['小会議室2', 20, 0, 3000, 4000, 3500, 500, 500, 300, '少人数向け会議室']);
}

$equipCountStmt = $pdo->query("SELECT COUNT(*) AS cnt FROM equipment");
$equipCount = (int)($equipCountStmt->fetch()['cnt'] ?? 0);
if ($equipCount === 0) {
    $eq = $pdo->prepare(
        "INSERT INTO equipment (category, name, price_type, unit_price, max_quantity, enabled, remark)
         VALUES (?, ?, ?, ?, ?, 1, ?)"
    );
    $eq->execute(['stage', '演台', 'per_slot', 500, 1, '標準演台']);
    $eq->execute(['lighting', '照明一式', 'flat', 3000, 1, 'ホール用照明セット']);
    $eq->execute(['sound', 'ワイヤレスマイク', 'per_slot', 500, 4, '音響セット']);
}

echo "Database initialized successfully.\n";

