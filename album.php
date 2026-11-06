<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM albums WHERE id = ? AND published = 1');
$st->execute([$id]);
$album = $st->fetch();
if (!$album) {
    http_response_code(404);
    exit('Альбом не найден. <a href="index.php#albums">Вернуться к альбомам</a>');
}
$ph = db()->prepare('SELECT file, caption FROM album_photos WHERE album_id = ? ORDER BY sort_order, id');
$ph->execute([$id]);
$photos = $ph->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($album['title']) ?> | <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
  <div class="wrap header-inner">
    <a href="index.php" class="brand"><span class="brand-mark">МГЕР</span><span class="brand-text"><b>Таганрог</b><small>Молодая гвардия Единой России</small></span></a>
    <a href="index.php#albums" class="btn btn-ghost">← Все альбомы</a>
  </div>
</header>
<main class="wrap section">
  <p class="eyebrow">Фотоальбом</p>
  <h1><?= e($album['title']) ?></h1>
  <?php if ($album['description']): ?><p class="lead-text"><?= nl2br(e($album['description'])) ?></p><?php endif; ?>
  <div class="photo-grid public">
    <?php foreach ($photos as $p): ?>
      <a href="uploads/<?= e($p['file']) ?>" target="_blank" rel="noopener"><img src="uploads/<?= e($p['file']) ?>" alt="" loading="lazy"></a>
    <?php endforeach; ?>
  </div>
  <?php if (!$photos): ?><p class="empty">В альбоме пока нет фотографий.</p><?php endif; ?>
</main>
</body>
</html>
