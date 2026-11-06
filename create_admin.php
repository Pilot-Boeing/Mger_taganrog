<?php
// ОДНОРАЗОВЫЙ скрипт. Откройте его в браузере один раз, создайте администратора
// и СРАЗУ удалите этот файл с хостинга.
require_once __DIR__ . '/includes/bootstrap.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.@-]{3,64}$/', $login)) {
        $message = 'Логин: 3–64 символа, латиница, цифры, точка, дефис или подчёркивание.';
    } elseif (mb_strlen($password) < 12) {
        $message = 'Пароль должен быть не короче 12 символов.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('INSERT INTO admins (login, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)');
        $stmt->execute([$login, $hash]);
        $message = 'Администратор создан. Теперь удалите файл create_admin.php с хостинга.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Создание администратора</title></head>
<body style="font-family:Arial;max-width:420px;margin:40px auto;">
<h2>Создание администратора</h2>
<p><?= e($message) ?></p>
<form method="post">
  <p><label>Логин<br><input name="login" required></label></p>
  <p><label>Пароль (от 12 символов)<br><input name="password" type="password" required></label></p>
  <p><button type="submit">Создать</button></p>
</form>
</body>
</html>
