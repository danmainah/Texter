-- users table
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  role ENUM('admin', 'moderator', 'user') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- contacts table
CREATE TABLE contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  phone VARCHAR(20),
  email VARCHAR(100),
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- messages table
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contact_id INT,
  type ENUM('sms', 'whatsapp', 'email'),
  subject VARCHAR(255),
  content TEXT,
  status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
  scheduled_at DATETIME,
  sent_at DATETIME NULL,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- To add a default admin user manually:
-- INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@example.com', '<hashed_password>', 'admin');
-- Use PHP's password_hash() to generate <hashed_password>.
