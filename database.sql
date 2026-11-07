-- МГЕР Таганрог: база данных MySQL
-- Выполнить один раз в phpMyAdmin (вкладка SQL) после создания базы на хостинге.
-- Кодировка базы: utf8mb4.

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  age INT NOT NULL,
  contact VARCHAR(120) NOT NULL,
  direction VARCHAR(60) DEFAULT NULL,
  message TEXT,
  processed TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category ENUM('news','event') NOT NULL DEFAULT 'news',
  title VARCHAR(200) NOT NULL,
  body TEXT,
  event_date DATE DEFAULT NULL,
  place VARCHAR(200) DEFAULT NULL,
  cover VARCHAR(255) DEFAULT NULL,
  published TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS albums (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  published TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS album_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  album_id INT NOT NULL,
  file VARCHAR(255) NOT NULL,
  caption VARCHAR(200) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) PRIMARY KEY,
  `value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('about_text', 'Молодая гвардия Единой России объединяет активную молодёжь. В Таганроге мы работаем с жителями от 14 до 35 лет.'),
  ('members_count', ''),
  ('events_count', ''),
  ('address', ''),
  ('phone', ''),
  ('email', ''),
  ('socials', ''),
  ('leader_name', 'Артём Абакумов'),
  ('leader_role', 'Руководитель местного отделения'),
  ('leader_photo', ''),
  ('deputy_name', 'Злата Чурина'),
  ('deputy_role', 'Первый заместитель руководителя'),
  ('deputy_photo', '');

-- Самые активные участники (добавляются через админку)
CREATE TABLE IF NOT EXISTS active_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  photo VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Защита от перебора и частых запросов (ограничение по IP)
CREATE TABLE IF NOT EXISTS rate_limits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_hash CHAR(64) NOT NULL,
  action VARCHAR(32) NOT NULL,
  hits INT NOT NULL DEFAULT 0,
  window_start INT NOT NULL,
  UNIQUE KEY uniq_ip_action (ip_hash, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Контакты: телефон и почта убраны, соцсеть ВК, адрес по умолчанию
UPDATE settings SET `value` = 'г. Таганрог, ул. Фрунзе 59' WHERE `key` = 'address';
UPDATE settings SET `value` = 'https://vk.ru/club234662241' WHERE `key` = 'socials';
UPDATE settings SET `value` = '' WHERE `key` IN ('phone', 'email');
UPDATE settings SET `value` = 'Абакумов Артём Анатольевич' WHERE `key` = 'leader_name';
UPDATE settings SET `value` = '15' WHERE `key` = 'members_count';
UPDATE settings SET `value` = '100+' WHERE `key` = 'events_count';
