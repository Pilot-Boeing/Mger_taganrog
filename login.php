<?php
require_once __DIR__ . '/includes/bootstrap.php';
session_start_secure();

if (!empty($_SESSION['admin_id'])) {
    redirect('admin/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!rate_limit('login', 5, 900)) {
        $error = 'Слишком много попыток входа. Подождите 15 минут.';
    }
    if ($error === '') {
        $login = trim($_POST['login'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE login = ? LIMIT 1');
        $stmt->execute([$login]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            redirect('admin/index.php');
        }
        usleep(800000);
        $error = 'Неверный логин или пароль.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Вход | <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
<main class="login-box">
  <p class="brand-mark big">МГЕР</p>
  <h1>Вход для администратора</h1>
  <form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Логин<input name="login" required autocomplete="username" maxlength="64"></label>
    <label>Пароль<input name="password" type="password" required autocomplete="current-password"></label>
    <?php if ($error): ?><div class="notice err"><?= e($error) ?></div><?php endif; ?>
    <button type="submit" class="btn btn-primary" style="width:100%">Войти</button>
  </form>
  <p class="back"><a href="index.php">← На сайт</a></p>
</main>
</body>
</html>
