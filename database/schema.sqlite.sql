CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'customer',
  created_at TEXT NOT NULL
);
CREATE TABLE tickets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  description TEXT NOT NULL,
  category TEXT NOT NULL,
  priority TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'open',
  requester_id INTEGER NOT NULL REFERENCES users(id),
  assignee_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  due_at TEXT NOT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);
CREATE TABLE ticket_events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ticket_id INTEGER NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
  user_id INTEGER NOT NULL REFERENCES users(id),
  type TEXT NOT NULL,
  body TEXT NOT NULL,
  created_at TEXT NOT NULL
);
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_requester ON tickets(requester_id);
CREATE INDEX idx_events_ticket ON ticket_events(ticket_id)
