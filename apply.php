<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php#apply');
}
if (!rate_limit('apply', 5, 600)) {
    redirect('index.php?error=' . urlencode('Слишком много заявок. Попробуйте позже.') . '#apply');
}

$name = trim($_POST['name'] ?? '');
$age = (int)($_POST['age'] ?? 0);
$contact = trim($_POST['contact'] ?? '');
$direction = trim($_POST['direction'] ?? '');
$message = trim($_POST['message'] ?? '');
$consent = isset($_POST['consent']);

$allowedDirections = ['', 'Волонтёрство', 'Образование', 'Патриотика', 'Спорт и досуг'];

if ($name === '' || mb_strlen($name) > 100 || $age < 14 || $age > 35 || $contact === '' || mb_strlen($contact) > 120
    || !in_array($direction, $allowedDirections, true) || mb_strlen($message) > 1000 || !$consent) {
    redirect('index.php?error=' . urlencode('Проверьте заполнение формы: обязательные поля и согласие.') . '#apply');
}

$stmt = db()->prepare('INSERT INTO applications (name, age, contact, direction, message) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$name, $age, $contact, $direction ?: null, $message ?: null]);

redirect('index.php?sent=1#apply');
