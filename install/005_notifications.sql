-- Migration 005 — notifications e-mail différées des messages non lus (idempotente)
ALTER TABLE messages ADD COLUMN IF NOT EXISTS notified_at DATETIME NULL AFTER read_at;
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS notify_messages TINYINT(1) NOT NULL DEFAULT 1 AFTER avatar,
  ADD COLUMN IF NOT EXISTS last_notified_at DATETIME NULL AFTER notify_messages;
