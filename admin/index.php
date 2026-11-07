<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$csrf = csrf_token();
$tab = $_GET['tab'] ?? 'apps';
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
$edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$album_id = isset($_GET['album']) ? (int)$_GET['album'] : 0;

$apps = db()->query('SELECT * FROM applications ORDER BY created_at DESC')->fetchAll();
$articles = db()->query('SELECT * FROM articles ORDER BY created_at DESC')->fetchAll();
$albums = db()->query('SELECT a.*, (SELECT COUNT(*) FROM album_photos p WHERE p.album_id=a.id) AS n FROM albums a ORDER BY created_at DESC')->fetchAll();
$s = setting_map();
$actives = db()->query('SELECT * FROM active_members ORDER BY sort_order, id')->fetchAll();

$editArticle = null;
if ($edit) {
    $st = db()->prepare('SELECT * FROM articles WHERE id=?'); $st->execute([$edit]); $editArticle = $st->fetch() ?: null;
}
$currentAlbum = null; $photos = [];
if ($album_id) {
    $st = db()->prepare('SELECT * FROM albums WHERE id=?'); $st->execute([$album_id]); $currentAlbum = $st->fetch() ?: null;
    $st = db()->prepare('SELECT * FROM album_photos WHERE album_id=? ORDER BY sort_order, id'); $st->execute([$album_id]); $photos = $st->fetchAll();
}
function tab_link(string $t): string { return 'index.php?tab=' . $t; }
function dt(?string $d): string { return $d ? date('d.m.Y H:i', strtotime($d)) : ''; }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Панель администратора | <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body class="admin-page">
<header class="site-header">
  <div class="wrap header-inner">
    <span class="brand"><span class="brand-mark">МГЕР</span><span class="brand-text"><b>Панель администратора</b></span></span>
    <nav class="nav open">
      <a href="../index.php" target="_blank">Открыть сайт</a>
      <a href="../logout.php">Выйти</a>
    </nav>
  </div>
</header>

<main class="wrap admin">
  <?php if ($msg): ?><div class="notice ok"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="notice err"><?= e($err) ?></div><?php endif; ?>

  <div class="tabs">
    <a class="tab <?= $tab==='apps'?'active':'' ?>" href="<?= tab_link('apps') ?>">Заявки<?php $new = count(array_filter($apps, fn($a)=>!$a['processed'])); if ($new) echo " <b>($new)</b>"; ?></a>
    <a class="tab <?= $tab==='articles'?'active':'' ?>" href="<?= tab_link('articles') ?>">Новости и события</a>
    <a class="tab <?= $tab==='albums'?'active':'' ?>" href="<?= tab_link('albums') ?>">Альбомы</a>
    <a class="tab <?= $tab==='actives'?'active':'' ?>" href="<?= tab_link('actives') ?>">Самые активные</a>
    <a class="tab <?= $tab==='settings'?'active':'' ?>" href="<?= tab_link('settings') ?>">Тексты и руководство</a>
  </div>

  <?php if ($tab === 'apps'): ?>
    <section class="panel">
      <h2>Заявки на вступление</h2>
      <?php if (!$apps): ?><p class="muted">Заявок пока нет.</p><?php endif; ?>
      <div class="table-wrap">
      <table>
        <thead><tr><th>Дата</th><th>Имя</th><th>Возраст</th><th>Контакт</th><th>Направление</th><th>Комментарий</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($apps as $a): ?>
          <tr class="<?= $a['processed'] ? 'done' : '' ?>">
            <td><?= e(dt($a['created_at'])) ?></td>
            <td><?= e($a['name']) ?></td>
            <td><?= (int)$a['age'] ?></td>
            <td><?= e($a['contact']) ?></td>
            <td><?= e($a['direction']) ?></td>
            <td><?= nl2br(e($a['message'])) ?></td>
            <td class="actions">
              <form method="post" action="action.php"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="app_toggle"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-small"><?= $a['processed'] ? 'Вернуть' : 'Обработана' ?></button></form>
              <form method="post" action="action.php" onsubmit="return confirm('Удалить заявку?')"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="app_delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-small danger">Удалить</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </section>

  <?php elseif ($tab === 'articles'): ?>
    <section class="panel">
      <h2><?= $editArticle ? 'Редактирование' : 'Новая публикация' ?></h2>
      <form method="post" action="action.php" enctype="multipart/form-data" class="form">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="article_save">
        <input type="hidden" name="id" value="<?= (int)($editArticle['id'] ?? 0) ?>">
        <div class="row">
          <label>Тип
            <select name="category">
              <option value="news" <?= ($editArticle['category'] ?? '')==='news'?'selected':'' ?>>Новость</option>
              <option value="event" <?= ($editArticle['category'] ?? '')==='event'?'selected':'' ?>>Мероприятие</option>
            </select>
          </label>
          <label>Дата мероприятия (для событий)<input type="date" name="event_date" value="<?= e($editArticle['event_date'] ?? '') ?>"></label>
        </div>
        <label>Заголовок *<input name="title" required maxlength="200" value="<?= e($editArticle['title'] ?? '') ?>"></label>
        <label>Место (для мероприятий)<input name="place" maxlength="200" value="<?= e($editArticle['place'] ?? '') ?>"></label>
        <label>Текст<textarea name="body" rows="8"><?= e($editArticle['body'] ?? '') ?></textarea></label>
        <label>Обложка (JPG, PNG, WebP до 5 МБ)<input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label>
        <label class="check"><input type="checkbox" name="published" <?= (!$editArticle || $editArticle['published']) ? 'checked' : '' ?>> Опубликовать на сайте</label>
        <div class="actions">
          <button class="btn btn-primary">Сохранить</button>
          <?php if ($editArticle): ?><a class="btn" href="index.php?tab=articles">Отмена</a><?php endif; ?>
        </div>
      </form>
    </section>
    <section class="panel">
      <h2>Все публикации</h2>
      <div class="table-wrap"><table>
        <thead><tr><th>Дата</th><th>Тип</th><th>Заголовок</th><th>Статус</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($articles as $a): ?>
          <tr>
            <td><?= e(dt($a['created_at'])) ?></td>
            <td><?= $a['category']==='event' ? 'Мероприятие' : 'Новость' ?></td>
            <td><?= e($a['title']) ?></td>
            <td><?= $a['published'] ? 'На сайте' : '<span class="muted">скрыто</span>' ?></td>
            <td class="actions">
              <a class="btn btn-small" href="index.php?tab=articles&edit=<?= (int)$a['id'] ?>">Изменить</a>
              <form method="post" action="action.php"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="article_toggle"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-small"><?= $a['published'] ? 'Скрыть' : 'Опубликовать' ?></button></form>
              <form method="post" action="action.php" onsubmit="return confirm('Удалить публикацию?')"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="article_delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-small danger">Удалить</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </section>

  <?php elseif ($tab === 'albums'): ?>
    <?php if ($currentAlbum): ?>
      <section class="panel">
        <a href="index.php?tab=albums">← Все альбомы</a>
        <h2><?= e($currentAlbum['title']) ?></h2>
        <form method="post" action="action.php" enctype="multipart/form-data" class="form">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="photos_add">
          <input type="hidden" name="album_id" value="<?= (int)$currentAlbum['id'] ?>">
          <label>Добавить фотографии (можно несколько сразу)<input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp"></label>
          <button class="btn btn-primary">Загрузить</button>
        </form>
      </section>
      <section class="panel">
        <h2>Фотографии (<?= count($photos) ?>)</h2>
        <div class="photo-grid">
          <?php foreach ($photos as $p): ?>
            <div class="photo-item">
              <img src="../uploads/<?= e($p['file']) ?>" alt="">
              <form method="post" action="action.php" onsubmit="return confirm('Удалить фото?')"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="photo_delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="album_id" value="<?= (int)$currentAlbum['id'] ?>"><button class="btn btn-small danger">Удалить</button></form>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if (!$photos): ?><p class="muted">В альбоме пока нет фото. Первое фото станет обложкой.</p><?php endif; ?>
      </section>
      <section class="panel">
        <h2>Настройки альбома</h2>
        <form method="post" action="action.php" class="form">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="album_save">
          <input type="hidden" name="id" value="<?= (int)$currentAlbum['id'] ?>">
          <label>Название *<input name="title" required maxlength="200" value="<?= e($currentAlbum['title']) ?>"></label>
          <label>Описание<textarea name="description" rows="3"><?= e($currentAlbum['description']) ?></textarea></label>
          <label class="check"><input type="checkbox" name="published" <?= $currentAlbum['published'] ? 'checked' : '' ?>> Показывать на сайте</label>
          <div class="actions"><button class="btn btn-primary">Сохранить</button></div>
        </form>
        <form method="post" action="action.php" onsubmit="return confirm('Удалить альбом вместе со всеми фото?')" style="margin-top:14px">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="album_delete"><input type="hidden" name="id" value="<?= (int)$currentAlbum['id'] ?>">
          <button class="btn danger">Удалить альбом</button>
        </form>
      </section>
    <?php else: ?>
      <section class="panel">
        <h2>Новый альбом</h2>
        <form method="post" action="action.php" class="form">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="album_save">
          <input type="hidden" name="id" value="0">
          <label>Название *<input name="title" required maxlength="200"></label>
          <label>Описание<textarea name="description" rows="2"></textarea></label>
          <label class="check"><input type="checkbox" name="published" checked> Показывать на сайте</label>
          <button class="btn btn-primary">Создать альбом</button>
        </form>
      </section>
      <section class="panel">
        <h2>Все альбомы</h2>
        <div class="table-wrap"><table>
          <thead><tr><th>Название</th><th>Фото</th><th>Статус</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($albums as $al): ?>
            <tr>
              <td><?= e($al['title']) ?></td>
              <td><?= (int)$al['n'] ?></td>
              <td><?= $al['published'] ? 'На сайте' : '<span class="muted">скрыт</span>' ?></td>
              <td class="actions"><a class="btn btn-small" href="index.php?tab=albums&album=<?= (int)$al['id'] ?>">Открыть</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
        <?php if (!$albums): ?><p class="muted">Альбомов пока нет.</p><?php endif; ?>
      </section>
    <?php endif; ?>

  <?php elseif ($tab === 'actives'): ?>
    <section class="panel">
      <h2>Добавить участника</h2>
      <form method="post" action="action.php" enctype="multipart/form-data" class="form">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="active_add">
        <div class="row">
          <label>Фамилия и имя *<input name="full_name" required maxlength="120"></label>
          <label>Фото (JPG, PNG, WebP до 5 МБ)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
        </div>
        <div class="actions"><button class="btn btn-primary">Добавить</button></div>
      </form>
    </section>
    <section class="panel">
      <h2>Участники на сайте (<?= count($actives) ?>)</h2>
      <?php if (!$actives): ?><p class="muted">Пока никого нет. Раздел на сайте будет пустым.</p><?php endif; ?>
      <div class="photo-grid">
        <?php foreach ($actives as $m): ?>
          <div class="photo-item">
            <?php if ($m['photo']): ?><img src="../uploads/<?= e($m['photo']) ?>" alt=""><?php endif; ?>
            <p><?= e($m['full_name']) ?></p>
            <form method="post" action="action.php" onsubmit="return confirm('Удалить участника?')"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="active_delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn btn-small danger">Удалить</button></form>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php elseif ($tab === 'settings'): ?>
    <section class="panel">
      <h2>Тексты и контакты сайта</h2>
      <form method="post" action="action.php" enctype="multipart/form-data" class="form">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="settings_save">
        <label>Описание «Кто мы»<textarea name="about_text" rows="4"><?= e($s['about_text'] ?? '') ?></textarea></label>
        <div class="row">
          <label>Участников<input type="number" min="0" name="members_count" value="<?= e($s['members_count'] ?? '') ?>"></label>
          <label>Проведено мероприятий<input type="number" min="0" name="events_count" value="<?= e($s['events_count'] ?? '') ?>"></label>
        </div>
        <div class="row">
          <label>Адрес<input name="address" value="<?= e($s['address'] ?? '') ?>"></label>
          <label>Телефон<input name="phone" value="<?= e($s['phone'] ?? '') ?>"></label>
          <label>Почта<input type="email" name="email" value="<?= e($s['email'] ?? '') ?>"></label>
          <label>Соцсети (ссылки)<input name="socials" value="<?= e($s['socials'] ?? '') ?>"></label>
        </div>
        <h3>Руководство</h3>
        <div class="row">
          <label>Руководитель: ФИО<input name="leader_name" value="<?= e($s['leader_name'] ?? '') ?>"></label>
          <label>Должность<input name="leader_role" value="<?= e($s['leader_role'] ?? '') ?>"></label>
          <label>Фото руководителя<input type="file" name="leader_photo" accept="image/jpeg,image/png,image/webp"></label>
        </div>
        <div class="row">
          <label>Первый заместитель: ФИО<input name="deputy_name" value="<?= e($s['deputy_name'] ?? '') ?>"></label>
          <label>Должность<input name="deputy_role" value="<?= e($s['deputy_role'] ?? '') ?>"></label>
          <label>Фото заместителя<input type="file" name="deputy_photo" accept="image/jpeg,image/png,image/webp"></label>
        </div>
        <div class="actions"><button class="btn btn-primary">Сохранить</button></div>
      </form>
    </section>
  <?php endif; ?>
</main>
</body>
</html>
