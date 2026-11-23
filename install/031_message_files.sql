-- Pièces jointes de la messagerie (photos, PDF, documents)
CREATE TABLE IF NOT EXISTS message_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  message_id INT UNSIGNED NOT NULL,
  stored VARCHAR(40) NOT NULL,
  original VARCHAR(160) NOT NULL,
  mime VARCHAR(120) NOT NULL,
  size INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY k_mf_msg (message_id),
  CONSTRAINT fk_mf_msg FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
