<?php
require_once __DIR__ . '/includes/bootstrap.php';

$s = setting_map();
$news = db()->query("SELECT * FROM articles WHERE published=1 AND category='news' ORDER BY created_at DESC LIMIT 6")->fetchAll();
$events = db()->query("SELECT * FROM articles WHERE published=1 AND category='event' ORDER BY event_date ASC, created_at DESC LIMIT 6")->fetchAll();
$albums = db()->query("SELECT a.*, (SELECT file FROM album_photos p WHERE p.album_id=a.id ORDER BY sort_order, id LIMIT 1) AS cover, (SELECT COUNT(*) FROM album_photos p WHERE p.album_id=a.id) AS photos_count FROM albums a WHERE a.published=1 ORDER BY a.created_at DESC LIMIT 6")->fetchAll();
$actives = db()->query('SELECT * FROM active_members ORDER BY sort_order, id')->fetchAll();
$sent = isset($_GET['sent']);
$error = isset($_GET['error']) ? $_GET['error'] : '';

function fmt_date(?string $d): string {
    return $d ? date('d.m.Y', strtotime($d)) : '';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= e(SITE_NAME) ?> | Молодая гвардия Единой России</title>
<meta name="description" content="Местное отделение Молодой гвардии Единой России в Таганроге: руководство, новости, мероприятия, альбомы и история города.">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="site-header">
  <div class="wrap header-inner">
    <a href="#top" class="brand">
      <span class="brand-mark">МГЕР</span>
      <span class="brand-text"><b>Таганрог</b><small>Молодая гвардия Единой России</small></span>
    </a>
    <button class="menu-btn" id="menuBtn" aria-expanded="false" aria-controls="nav">Меню</button>
    <nav id="nav" class="nav">
      <a href="#about">О нас</a>
      <a href="#leadership">Руководство</a>
      <a href="#news">Новости</a>
      <a href="#events">События</a>
      <a href="#albums">Альбомы</a>
      <a href="#history">История</a>
      <a href="#apply" class="nav-cta">Вступить</a>
    </nav>
    <a class="theme-btn admin-entry" href="login.php">Вход для администратора</a>
    <button class="theme-btn" id="themeBtn" aria-label="Сменить тему">Тёмная тема</button>
  </div>
</header>

<main id="top">

<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <p class="kicker">Местное отделение · Таганрог</p>
      <h1>Молодёжь <span class="grad-text">Таганрога</span> — вместе делаем город лучше</h1>
      <p class="lead">Волонтёрство, образование, патриотические проекты и общение с единомышленниками. Здесь собраны руководство, новости, мероприятия и фотоальбомы отделения.</p>
      <div class="hero-actions">
        <a href="#apply" class="btn btn-primary">Вступить</a>
        <a href="#about" class="btn btn-ghost">Подробнее</a>
      </div>
    </div>
    <div class="hero-card">
      <div class="stat"><span class="stat-num">61 000</span><span class="stat-label">молодых жителей города от 14 до 35 лет</span></div>
      <div class="stat"><span class="stat-num"><?= e($s['members_count'] ?: '—') ?></span><span class="stat-label">участников отделения</span></div>
      <div class="stat"><span class="stat-num"><?= e($s['events_count'] ?: '—') ?></span><span class="stat-label">проведённых мероприятий</span></div>
    </div>
  </div>
</section>

<section id="about" class="section">
  <div class="wrap">
    <p class="eyebrow">О нас</p>
    <h2>Кто мы</h2>
    <p class="lead-text"><?= nl2br(e($s['about_text'])) ?></p>
    <div class="directions">
      <div class="dir"><span class="dir-ico">🤝</span><h3>Волонтёрство</h3><p>Помощь городу, социальные акции и поддержка жителей.</p></div>
      <div class="dir"><span class="dir-ico">📚</span><h3>Образование</h3><p>Лекции, семинары и подготовка лидеров молодёжного движения.</p></div>
      <div class="dir"><span class="dir-ico">🇷🇺</span><h3>Патриотика</h3><p>Памятные мероприятия, встречи с ветеранами и совместные проекты.</p></div>
      <div class="dir"><span class="dir-ico">⚡</span><h3>Спорт и досуг</h3><p>Турниры, походы и командные события для молодёжи.</p></div>
    </div>
  </div>
</section>

<section id="leadership" class="section alt">
  <div class="wrap">
    <p class="eyebrow">Руководство</p>
    <h2>Руководство отделения</h2>
    <div class="leaders">
      <?php foreach ([['leader', 'Руководитель'], ['deputy', 'Первый заместитель']] as [$key, $label]): ?>
      <article class="leader">
        <div class="leader-photo">
          <?php if (!empty($s[$key.'_photo'])): ?>
            <img src="<?= e(UPLOAD_URL . $s[$key.'_photo']) ?>" alt="<?= e($s[$key.'_name']) ?>">
          <?php else: ?>
            <span><?= e(mb_substr($s[$key.'_name'], 0, 1)) ?></span>
          <?php endif; ?>
        </div>
        <div class="leader-info">
          <p class="role"><?= e($label) ?></p>
          <h3><?= e($s[$key.'_name']) ?></h3>
          <p><?= e($s[$key.'_role']) ?></p>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="actives" class="section">
  <div class="wrap">
    <p class="eyebrow">Самые активные</p>
    <h2>Наши активисты</h2>
    <div class="leaders">
      <?php if (!$actives): ?><p class="empty">Пока здесь никого нет.</p><?php endif; ?>
      <?php foreach ($actives as $m): ?>
        <article class="leader">
          <div class="leader-photo">
            <?php if ($m['photo']): ?><img src="<?= e(UPLOAD_URL . $m['photo']) ?>" alt="<?= e($m['full_name']) ?>"><?php else: ?><span><?= e(mb_substr($m['full_name'], 0, 1)) ?></span><?php endif; ?>
          </div>
          <div class="leader-info"><h3><?= e($m['full_name']) ?></h3></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="news" class="section">
  <div class="wrap">
    <p class="eyebrow">Новости</p>
    <h2>Последние публикации</h2>
    <div class="cards">
      <?php if (!$news): ?><p class="empty">Новостей пока нет.</p><?php endif; ?>
      <?php foreach ($news as $n): ?>
        <article class="card">
          <p class="meta"><?= e(fmt_date($n['created_at'])) ?></p>
          <h3><?= e($n['title']) ?></h3>
          <div class="body"><?= nl2br(e($n['body'])) ?></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="events" class="section alt">
  <div class="wrap">
    <p class="eyebrow">События</p>
    <h2>Ближайшие мероприятия</h2>
    <div class="cards">
      <?php if (!$events): ?><p class="empty">Ближайших мероприятий пока нет.</p><?php endif; ?>
      <?php foreach ($events as $ev): ?>
        <article class="card">
          <p class="meta"><?= e(fmt_date($ev['event_date'])) ?><?= $ev['place'] ? ' · ' . e($ev['place']) : '' ?></p>
          <h3><?= e($ev['title']) ?></h3>
          <div class="body"><?= nl2br(e($ev['body'])) ?></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="albums" class="section">
  <div class="wrap">
    <p class="eyebrow">Фотоальбомы</p>
    <h2>Фотографии с мероприятий</h2>
    <div class="albums">
      <?php if (!$albums): ?><p class="empty">Альбомов пока нет.</p><?php endif; ?>
      <?php foreach ($albums as $al): ?>
        <a class="album" href="album.php?id=<?= (int)$al['id'] ?>">
          <div class="album-cover">
            <?php if ($al['cover']): ?>
              <img src="<?= e(UPLOAD_URL . $al['cover']) ?>" alt="<?= e($al['title']) ?>" loading="lazy">
            <?php else: ?>
              <span>Нет фото</span>
            <?php endif; ?>
          </div>
          <div class="album-info">
            <h3><?= e($al['title']) ?></h3>
            <p><?= (int)$al['photos_count'] ?> фото</p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="history" class="section alt">
  <div class="wrap history-grid">
    <div>
      <p class="eyebrow">История</p>
      <h2>Город, с которого всё началось</h2>
      <p class="lead-text">Таганрог основан в 1698 году по указу Петра I как крепость и база Азовского флота. Мы продолжаем традиции тех, кто строил и защищал этот город.</p>
      <div class="flag">
        <svg viewBox="0 0 200 100" role="img" aria-label="Флаг Таганрога (упрощённое изображение)">
          <rect width="200" height="100" fill="#ffffff"/>
          <rect y="16" width="200" height="14" fill="#4a90d9"/>
          <rect y="46" width="200" height="14" fill="#4a90d9"/>
          <rect y="76" width="200" height="12" fill="#4a90d9"/>
          <g fill="none" stroke="#f5c400" stroke-width="3" stroke-linecap="round">
            <line x1="100" y1="22" x2="100" y2="80"/>
            <path d="M86 26 q14 -6 28 0"/>
            <path d="M92 34 q8 6 16 0 M92 44 q8 -6 16 0"/>
            <path d="M78 50 L122 50"/>
            <path d="M80 42 q-10 -4 -14 6 M120 42 q10 -4 14 6"/>
            <line x1="70" y1="48" x2="130" y2="82"/>
            <line x1="130" y1="48" x2="70" y2="82"/>
          </g>
        </svg>
        <p class="caption">Флаг Таганрога, упрощённое изображение. Для публикации сверьте с официальным.</p>
      </div>
    </div>
    <ol class="timeline">
      <li><b>1698</b> Пётр I основывает крепость на мысе Таган-Рог, которая становится базой Азовского флота.</li>
      <li><b>1712</b> Город разрушен в соответствии с условиями Прутского мирного договора.</li>
      <li><b>1775</b> Таганрог получает статус города в составе Российской империи и становится крупным портом.</li>
      <li><b>1860</b> В Таганроге родился Антон Павлович Чехов.</li>
      <li><b>1941–43</b> Город находился под оккупацией. Молодые таганрожцы создали подпольную организацию.</li>
    </ol>
  </div>
</section>

<section id="apply" class="section">
  <div class="wrap apply-grid">
    <div>
      <p class="eyebrow">Вступить</p>
      <h2>Оставить заявку</h2>
      <p class="lead-text">Заполните форму, и руководство отделения свяжется с вами.</p>
      <ol class="steps">
        <li><b>1</b> Оставьте заявку на сайте</li>
        <li><b>2</b> Мы свяжемся с вами по телефону или почте</li>
        <li><b>3</b> Приходите на первое мероприятие</li>
      </ol>
      <div class="contacts">
        <p><span>Адрес</span> <?= e($s['address'] ?: 'уточняется') ?></p>
        <p><span>Руководитель</span> <?= e($s['leader_name']) ?></p>
        <p><span>Соцсети</span> <?php if (!empty($s['socials'])): ?><a href="<?= e($s['socials']) ?>" target="_blank" rel="noopener noreferrer">ВКонтакте</a><?php else: ?>уточняется<?php endif; ?></p>
      </div>
    </div>
    <form class="form" method="post" action="apply.php">
      <?php if ($sent): ?><div class="notice ok">Спасибо! Заявка отправлена, мы скоро свяжемся с вами.</div><?php endif; ?>
      <?php if ($error): ?><div class="notice err"><?= e($error) ?></div><?php endif; ?>
      <div class="row">
        <label>Имя и фамилия *<input name="name" required maxlength="100"></label>
        <label>Возраст *<input name="age" type="number" required min="14" max="35"></label>
      </div>
      <label>Телефон или почта *<input name="contact" required maxlength="120"></label>
      <label>Направление
        <select name="direction">
          <option value="">Не выбрано</option>
          <option>Волонтёрство</option>
          <option>Образование</option>
          <option>Патриотика</option>
          <option>Спорт и досуг</option>
        </select>
      </label>
      <label>Комментарий<textarea name="message" rows="3" maxlength="1000"></textarea></label>
      <label class="consent"><input type="checkbox" name="consent" required> <span>Я согласен на обработку персональных данных для связи со мной.</span></label>
      <button type="submit" class="btn btn-primary">Отправить заявку</button>
    </form>
  </div>
</section>

</main>

<footer class="site-footer">
  <div class="wrap">
    <span>© <?= date('Y') ?> <?= e(SITE_NAME) ?></span>
    <a href="login.php">Вход для администратора</a>
  </div>
</footer>

<script>
  // Меню на телефоне
  document.getElementById('menuBtn').addEventListener('click', function () {
    var nav = document.getElementById('nav');
    var open = nav.classList.toggle('open');
    this.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  // Тема: светлая по умолчанию, тёмная по кнопке
  (function () {
    var root = document.documentElement, btn = document.getElementById('themeBtn');
    function read() { try { return localStorage.getItem('mger-theme'); } catch (e) { return null; } }
    function save(v) { try { localStorage.setItem('mger-theme', v); } catch (e) {} }
    function apply(t) {
      root.setAttribute('data-theme', t);
      btn.textContent = t === 'dark' ? 'Светлая тема' : 'Тёмная тема';
    }
    apply(read() === 'dark' ? 'dark' : 'light');
    btn.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      apply(next); save(next);
    });
  })();
  // Появление блоков при прокрутке
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
  }, { threshold: 0.1 });
  document.querySelectorAll('.card, .dir, .leader, .album, .stat, .steps li, .timeline li').forEach(function (el) {
    el.classList.add('reveal'); io.observe(el);
  });
</script>
</body>
</html>
