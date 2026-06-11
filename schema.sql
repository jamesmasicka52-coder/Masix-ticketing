-- MySQL schema for ticket persistence (phpMyAdmin)
-- Database: syst_ticketing

CREATE TABLE IF NOT EXISTS tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  issue_title VARCHAR(255) NOT NULL,
  solution TEXT NOT NULL,
  company VARCHAR(100) NOT NULL,
  department VARCHAR(100) NOT NULL,
  priority ENUM('low','medium','high') NOT NULL,
  status ENUM('open','in progress','closed') NOT NULL,
  assigned_to VARCHAR(255) NOT NULL,
  date_created DATE NOT NULL,
  created_by INT(11) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `created_by` (`created_by`)
);

-- Optional indexes
CREATE INDEX IF NOT EXISTS idx_tickets_created_at ON tickets (created_at DESC);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) DEFAULT NULL UNIQUE,
  phone VARCHAR(25) DEFAULT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  -- admin can manage users and see tickets (authorization is enforced in PHP)
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  -- added-by relationship: non-default admins can only manage users/tickets they created
  added_by_admin_id INT(11) DEFAULT NULL,
  -- used to enforce “admin logged in on one device at a time”
  admin_session_token_hash VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_users_added_by_admin_id` (`added_by_admin_id`)
);


-- Insert default admin user (username: admin, password: admin123)
INSERT IGNORE INTO users (username, password_hash, role, added_by_admin_id, created_at)
VALUES ('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRZlG2', 'admin', NULL, NOW());



