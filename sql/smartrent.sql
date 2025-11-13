-- Prosty schemat bazy danych dla samrtrent (MySQL)
CREATE DATABASE IF NOT EXISTS samrtrent CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE samrtrent;

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(200) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS properties;
CREATE TABLE properties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  city VARCHAR(120) NOT NULL,
  image VARCHAR(255),
  owner_id INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS rentals;
CREATE TABLE rentals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  property_id INT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Testowe dane
INSERT INTO users (name,email,password,role) VALUES
('Admin','admin@example.com','" REPLACE_WITH_HASH "','admin'),
('Jan Kowalski','jan@example.com','" REPLACE_WITH_HASH "','user');

-- Pamiętaj: zastąp REPLACE_WITH_HASH prawdziwym hash'em, np:
-- php -r "echo password_hash('haslo123', PASSWORD_DEFAULT);"

INSERT INTO properties (title,description,price,city,owner_id) VALUES
('Przytulne 2-pokojowe w centrum','Umeblowane mieszkanie blisko komunikacji miejskiej.',1500,'Warszawa',1),
('Nowoczesne studio','Świetne warunki, szybkie łącze internetowe.',2200,'Kraków',1);