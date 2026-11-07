<?php
require_once __DIR__ . '/../config.php';

date_default_timezone_set('Europe/Moscow');
mb_internal_encoding('UTF-8');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function session_start_secure(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}

function csrf_token(): string {
    session_start_secure();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    session_start_secure();
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(403);
        exit('Сессия истекла. Вернитесь назад и обновите страницу.');
    }
}

function require_admin(): void {
    session_start_secure();
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function setting_map(): array {
    $rows = db()->query('SELECT `key`, `value` FROM settings')->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[$r['key']] = $r['value'];
    }
    return $map;
}

// Сохраняет загруженное изображение и возвращает имя файла или null.
function save_image(array $file): ?string {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Файл больше 5 МБ.');
    }
    $info = @getimagesize($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$info || !isset($allowed[$info['mime']])) {
        throw new RuntimeException('Допустимы только JPG, PNG и WebP.');
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$info['mime']];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        throw new RuntimeException('Не удалось сохранить файл.');
    }
    return $name;
}

// Ограничение частоты запросов с одного IP (защита от перебора пароля и спама заявками).
// Возвращает true, если запрос разрешён. IP хранится в виде хеша, сам адрес не сохраняется.
function rate_limit(string $action, int $maxHits, int $windowSeconds): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = hash('sha256', $ip . '|' . SITE_NAME);
    $now = time();
    $pdo = db();
    $st = $pdo->prepare('SELECT hits, window_start FROM rate_limits WHERE ip_hash = ? AND action = ?');
    $st->execute([$key, $action]);
    $row = $st->fetch();
    if (!$row || $now - (int)$row['window_start'] > $windowSeconds) {
        $pdo->prepare('REPLACE INTO rate_limits (ip_hash, action, hits, window_start) VALUES (?, ?, 1, ?)')
            ->execute([$key, $action, $now]);
        return true;
    }
    if ((int)$row['hits'] >= $maxHits) {
        return false;
    }
    $pdo->prepare('UPDATE rate_limits SET hits = hits + 1 WHERE ip_hash = ? AND action = ?')->execute([$key, $action]);
    return true;
}
