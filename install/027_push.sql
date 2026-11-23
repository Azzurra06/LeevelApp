-- Notifications push : abonnements des appareils, préférences, rappels de séance envoyés
CREATE TABLE IF NOT EXISTS push_subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  endpoint VARCHAR(700) NOT NULL,
  p256dh VARCHAR(200) NOT NULL,
  auth VARCHAR(100) NOT NULL,
  ua VARCHAR(160) NULL,
  fail_count SMALLINT NOT NULL DEFAULT 0,
  last_ok_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_push_ep (endpoint(255)),
  KEY k_push_user (user_id),
  CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE users ADD COLUMN IF NOT EXISTS push_prefs TEXT NULL;
ALTER TABLE sessions ADD COLUMN IF NOT EXISTS reminded_at DATETIME NULL;
