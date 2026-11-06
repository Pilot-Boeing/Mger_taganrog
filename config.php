<?php
// Заполните данными из панели хостинга (раздел MySQL).
// Этот файл не должен быть доступен посетителям: он защищён .htaccess в папке.

define('DB_HOST', 'localhost');      // обычно localhost; на некоторых хостингах другой адрес
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

define('SITE_NAME', 'МГЕР Таганрог');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024); // 5 МБ на файл
