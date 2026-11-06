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
