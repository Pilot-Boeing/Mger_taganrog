// Статичная версия: выводит данные из data.js, переключает тему, обрабатывает форму
(function () {
  var D = window.SITE_DATA || {};
  var S = D.settings || {};
  var L = D.leaders || {};

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function $(id) { return document.getElementById(id); }
  function fmt(d) {
    if (!d) return '';
    var dt = new Date(d);
    return isNaN(dt) ? esc(d) : dt.toLocaleDateString('ru-RU');
  }

  // Тексты и цифры
  $('about-text').textContent = S.about_text || '';
  $('s-members').textContent = S.members_count || '—';
  $('s-events').textContent = S.events_count || '—';
  $('leader-name').textContent = S.leader_name || '';
  $('leader-role').textContent = S.leader_role || '';
  $('deputy-name').textContent = S.deputy_name || '';
  $('deputy-role').textContent = S.deputy_role || '';
  $('leader-photo').src = L.leader_photo || '';
  $('deputy-photo').src = L.deputy_photo || '';
  $('c-address').textContent = S.address || 'уточняется';
  $('c-leader').textContent = S.leader_name || '';
  var social = $('c-social');
  social.textContent = S.socials_title || 'ВКонтакте';
  social.href = S.socials_url || '#';

  // Самые активные
  $('actives-list').innerHTML = (D.actives || []).map(function (m) {
    return '<div class="act">' + (m.photo ? '<img src="' + esc(m.photo) + '" alt="">' : '') +
      '<h4>' + esc(m.name) + '</h4></div>';
  }).join('');

  // Новости и события
  function posts(list, kind) {
    return (list || []).map(function (p, i) {
      var meta = kind === 'event'
        ? fmt(p.date) + (p.place ? ' · ' + esc(p.place) : '')
        : fmt(p.date);
      return '<article class="post"><div class="meta">' + meta + '</div><h3>' + esc(p.title) +
        '</h3><p>' + esc(p.body) + '</p></article>';
    }).join('');
  }
  $('news-list').innerHTML = posts(D.news, 'news');
  $('events-list').innerHTML = posts(D.events, 'event');

  // Тема: светлая по умолчанию
  var root = document.documentElement;
  var themeBtn = $('theme');
  function setTheme(t) {
    if (t === 'dark') root.setAttribute('data-theme', 'dark');
    else root.removeAttribute('data-theme');
    themeBtn.textContent = t === 'dark' ? 'Светлая тема' : 'Тёмная тема';
  }
  var saved = null;
  try { saved = localStorage.getItem('mger-theme'); } catch (e) {}
  setTheme(saved === 'light' ? 'light' : 'dark');
  themeBtn.addEventListener('click', function () {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    setTheme(next);
    try { localStorage.setItem('mger-theme', next); } catch (e) {}
  });

  // Меню на телефоне
  var burger = $('burger'), menu = $('menu');
  burger.addEventListener('click', function () {
    var open = menu.classList.toggle('open');
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  menu.addEventListener('click', function (e) {
    if (e.target.tagName === 'A') { menu.classList.remove('open'); burger.setAttribute('aria-expanded', 'false'); }
  });

  // Форма заявки без сервера: текст заявки для отправки руководству через ВКонтакте
  $('apply-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = this;
    if (!f.checkValidity()) { f.reportValidity(); return; }
    var text = 'Заявка в МГЕР Таганрог\n' +
      'Имя: ' + f.name.value.trim() + '\n' +
      'Возраст: ' + f.age.value + '\n' +
      'Контакт: ' + f.contact.value.trim() + '\n' +
      'Направление: ' + (f.direction.value || 'не выбрано') + '\n' +
      'Комментарий: ' + (f.message.value.trim() || '-');
    var note = $('apply-note');
    note.innerHTML = 'Чтобы отправить заявку, скопируйте текст и отправьте его руководству в <a href="' +
      esc(S.socials_url || '#') + '" target="_blank" rel="noopener noreferrer">ВКонтакте</a>.';
    var box = document.createElement('textarea');
    box.value = text;
    box.style.position = 'fixed'; box.style.opacity = '0';
    document.body.appendChild(box);
    box.select();
    try { document.execCommand('copy'); note.insertAdjacentText('beforeend', ' Текст заявки скопирован.'); } catch (err) {}
    document.body.removeChild(box);
    f.reset();
  });
})();

// Включаем режим анимаций (без JS все блоки видны сразу)
document.documentElement.classList.add('js');

// Появление блоков при прокрутке
(function () {
  var sel = '.block .wrap > h2, .block .wrap > .lead, .dirs .dir, .leaders .person, .actives .act, .posts .post, .history > div, .history .line li, .stats > div, .faq details, .apply, .contact-list p, .steps li';
  var els = Array.prototype.slice.call(document.querySelectorAll(sel));
  els.forEach(function (el, i) { el.classList.add('rv'); if (i % 3 === 1) el.classList.add('d1'); if (i % 3 === 2) el.classList.add('d2'); });
  function showAll() { els.forEach(function (el) { el.classList.add('in'); }); }
  if (!('IntersectionObserver' in window)) { showAll(); return; }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
    });
  }, { threshold: 0.12 });
  els.forEach(function (el) { io.observe(el); });
  setTimeout(function () { // страховка: если что-то не сработало, всё равно показываем через 3 секунды
    showAll();
  }, 3000);
})();

// Счётчики цифр: считают от нуля, когда цифра появляется на экране
(function () {
  var nums = Array.prototype.slice.call(document.querySelectorAll('[data-count]'));
  if (!nums.length) return;
  function run(el) {
    var target = parseInt(el.getAttribute('data-count'), 10) || 0;
    var suffix = el.getAttribute('data-suffix') || '';
    var start = null, dur = 1600;
    function step(ts) {
      if (!start) start = ts;
      var k = Math.min((ts - start) / dur, 1);
      var eased = 1 - Math.pow(1 - k, 3);
      var val = Math.round(target * eased);
      el.textContent = (target >= 1000 ? val.toLocaleString('ru-RU').replace(/,/g, ' ') : val) + (k === 1 ? suffix : '');
      if (k < 1) requestAnimationFrame(step);
    }
    el.textContent = '0' + (suffix && target ? '' : '');
    requestAnimationFrame(step);
  }
  if (!('IntersectionObserver' in window)) { nums.forEach(run); return; }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (en.isIntersecting) { run(en.target); io.unobserve(en.target); }
    });
  }, { threshold: 0.5 });
  nums.forEach(function (el) { io.observe(el); });
})();


// Меню на телефоне: отдельный надёжный обработчик
(function () {
  var btn = document.getElementById('burger');
  var nav = document.getElementById('menu');
  if (!btn || !nav) return;
  btn.addEventListener('click', function (e) {
    e.preventDefault();
    var open = nav.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  nav.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () {
      nav.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    });
  });
})();
