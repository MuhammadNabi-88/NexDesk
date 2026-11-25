CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'customer',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(30) NOT NULL,
  priority VARCHAR(20) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  requester_id INT UNSIGNED NOT NULL,
  assignee_id INT UNSIGNED NULL,
  due_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_tickets_status (status),
  INDEX idx_tickets_requester (requester_id),
  CONSTRAINT fk_tickets_requester FOREIGN KEY (requester_id) REFERENCES users(id),
  CONSTRAINT fk_tickets_assignee FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE ticket_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  type VARCHAR(20) NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_events_ticket (ticket_id),
  CONSTRAINT fk_events_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
