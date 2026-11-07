<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
csrf_check();

$action = $_POST['action'] ?? '';
$pdo = db();

function back(string $tab, string $msg = '', string $err = '', string $extra = ''): void {
    $q = 'index.php?tab=' . urlencode($tab);
    if ($msg) $q .= '&msg=' . urlencode($msg);
    if ($err) $q .= '&err=' . urlencode($err);
    if ($extra) $q .= '&' . $extra;
    redirect($q);
}

function post_int(string $key): int { return (int)($_POST[$key] ?? 0); }

try {
    switch ($action) {

        // ===== Заявки =====
        case 'app_toggle':
            $pdo->prepare('UPDATE applications SET processed = 1 - processed WHERE id = ?')->execute([post_int('id')]);
            back('apps', 'Статус заявки изменён');

        case 'app_delete':
            $pdo->prepare('DELETE FROM applications WHERE id = ?')->execute([post_int('id')]);
            back('apps', 'Заявка удалена');

        // ===== Новости и мероприятия =====
        case 'article_save':
            $id = post_int('id');
            $category = ($_POST['category'] ?? 'news') === 'event' ? 'event' : 'news';
            $title = trim($_POST['title'] ?? '');
            $body = trim($_POST['body'] ?? '');
            $place = trim($_POST['place'] ?? '');
            $eventDate = trim($_POST['event_date'] ?? '') ?: null;
            $published = isset($_POST['published']) ? 1 : 0;
            if ($title === '') back('articles', '', 'Укажите заголовок');

            $cover = save_image($_FILES['cover'] ?? []);
            if ($id) {
                $pdo->prepare('UPDATE articles SET category=?, title=?, body=?, place=?, event_date=?, published=?' . ($cover ? ', cover=?' : '') . ' WHERE id=?')
                    ->execute(array_merge([$category, $title, $body, $place ?: null, $eventDate, $published], $cover ? [$cover, $id] : [$id]));
            } else {
                $pdo->prepare('INSERT INTO articles (category, title, body, place, event_date, published, cover) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$category, $title, $body, $place ?: null, $eventDate, $published, $cover]);
            }
            back('articles', 'Публикация сохранена');

        case 'article_toggle':
            $pdo->prepare('UPDATE articles SET published = 1 - published WHERE id = ?')->execute([post_int('id')]);
            back('articles', 'Статус публикации изменён');

        case 'article_delete':
            $pdo->prepare('DELETE FROM articles WHERE id = ?')->execute([post_int('id')]);
            back('articles', 'Публикация удалена');

        // ===== Альбомы =====
        case 'album_save':
            $id = post_int('id');
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $published = isset($_POST['published']) ? 1 : 0;
            if ($title === '') back('albums', '', 'Укажите название альбома');
            if ($id) {
                $pdo->prepare('UPDATE albums SET title=?, description=?, published=? WHERE id=?')->execute([$title, $description, $published, $id]);
                back('albums', 'Альбом сохранён', '', 'album=' . $id);
            }
            $pdo->prepare('INSERT INTO albums (title, description, published) VALUES (?,?,?)')->execute([$title, $description, $published]);
            back('albums', 'Альбом создан', '', 'album=' . $pdo->lastInsertId());

        case 'album_delete':
            $files = $pdo->prepare('SELECT file FROM album_photos WHERE album_id = ?');
            $files->execute([post_int('id')]);
            foreach ($files->fetchAll() as $f) {
                @unlink(UPLOAD_DIR . $f['file']);
            }
            $pdo->prepare('DELETE FROM albums WHERE id = ?')->execute([post_int('id')]);
            back('albums', 'Альбом удалён');

        case 'photos_add':
            $albumId = post_int('album_id');
            $count = 0;
            if (!empty($_FILES['photos']['name'][0])) {
                $n = count($_FILES['photos']['name']);
                for ($i = 0; $i < $n; $i++) {
                    $file = [
                        'name' => $_FILES['photos']['name'][$i],
                        'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                        'size' => $_FILES['photos']['size'][$i],
                        'error' => $_FILES['photos']['error'][$i],
                    ];
                    $name = save_image($file);
                    if ($name) {
                        $pdo->prepare('INSERT INTO album_photos (album_id, file, sort_order) VALUES (?,?,?)')
                            ->execute([$albumId, $name, $i]);
                        $count++;
                    }
                }
            }
            back('albums', $count ? "Загружено фото: $count" : '', $count ? '' : 'Фото не загружены. Проверьте формат и размер.', 'album=' . $albumId);

        case 'photo_delete':
            $st = $pdo->prepare('SELECT file FROM album_photos WHERE id = ?');
            $st->execute([post_int('id')]);
            if ($row = $st->fetch()) {
                @unlink(UPLOAD_DIR . $row['file']);
            }
            $pdo->prepare('DELETE FROM album_photos WHERE id = ?')->execute([post_int('id')]);
            back('albums', 'Фото удалено', '', 'album=' . post_int('album_id'));

        // ===== Самые активные =====
        case 'active_add':
            $name = trim($_POST['full_name'] ?? '');
            if ($name === '') back('actives', '', 'Укажите фамилию и имя');
            $photo = save_image($_FILES['photo'] ?? []);
            $pdo->prepare('INSERT INTO active_members (full_name, photo, sort_order) VALUES (?, ?, (SELECT COALESCE(MAX(x.sort_order),0)+1 FROM active_members x))')
                ->execute([$name, $photo]);
            back('actives', 'Участник добавлен');

        case 'active_delete':
            $st = $pdo->prepare('SELECT photo FROM active_members WHERE id = ?');
            $st->execute([post_int('id')]);
            if ($row = $st->fetch()) {
                if ($row['photo']) @unlink(UPLOAD_DIR . $row['photo']);
            }
            $pdo->prepare('DELETE FROM active_members WHERE id = ?')->execute([post_int('id')]);
            back('actives', 'Участник удалён');

        // ===== Тексты и руководство =====
        case 'settings_save':
            $fields = ['about_text', 'members_count', 'events_count', 'address', 'phone', 'email', 'socials',
                       'leader_name', 'leader_role', 'deputy_name', 'deputy_role'];
            $stmt = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
            foreach ($fields as $f) {
                $stmt->execute([$f, trim((string)($_POST[$f] ?? ''))]);
            }
            foreach (['leader_photo', 'deputy_photo'] as $p) {
                $name = save_image($_FILES[$p] ?? []);
                if ($name) {
                    $stmt->execute([$p, $name]);
                }
            }
            back('settings', 'Тексты и контакты сохранены');

        default:
            back('apps', '', 'Неизвестное действие');
    }
} catch (Throwable $e) {
    $tab = in_array($action, ['article_save','article_toggle','article_delete'], true) ? 'articles'
         : (in_array($action, ['active_add','active_delete'], true) ? 'actives' : (in_array($action, ['album_save','album_delete','photos_add','photo_delete'], true) ? 'albums'
         : ($action === 'settings_save' ? 'settings' : 'apps')));
    back($tab, '', $e instanceof RuntimeException ? $e->getMessage() : 'Ошибка сохранения. Попробуйте ещё раз.');
}
