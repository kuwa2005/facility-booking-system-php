<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

logoutUser();
redirect('/user_login.php?notice=' . urlencode('ログアウトしました'));

