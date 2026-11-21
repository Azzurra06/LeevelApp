-- Migration 004 — coordonnées et photo des comptes, messagerie interne (idempotente)
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL AFTER display_name,
  ADD COLUMN IF NOT EXISTS email VARCHAR(120) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS avatar VARCHAR(80) NULL AFTER email;

CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  family_id INT UNSIGNED NOT NULL,
  sender_id INT UNSIGNED NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at DATETIME NULL,
  KEY k_msg_fam (family_id, created_at),
  CONSTRAINT fk_msg_fam FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
