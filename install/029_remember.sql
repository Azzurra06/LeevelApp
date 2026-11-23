-- Connexion persistante en mode app (écran d'accueil) : un jeton par appareil
CREATE TABLE IF NOT EXISTS remember_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  selector CHAR(24) NOT NULL,
  validator_hash CHAR(64) NOT NULL,
  ua VARCHAR(160) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_used_at DATETIME NULL,
  expires_at DATETIME NOT NULL,
  UNIQUE KEY uq_rm_sel (selector),
  KEY k_rm_user (user_id),
  CONSTRAINT fk_rm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
