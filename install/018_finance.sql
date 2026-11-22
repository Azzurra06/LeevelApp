-- Finances : tarif horaire par élève, échéances / paiements, réglages
ALTER TABLE students
  ADD COLUMN IF NOT EXISTS hourly_rate DECIMAL(6,2) NULL,
  ADD COLUMN IF NOT EXISTS hourly_rate_visio DECIMAL(6,2) NULL;
CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  period CHAR(7) NULL,
  label VARCHAR(160) NOT NULL,
  minutes INT UNSIGNED NOT NULL DEFAULT 0,
  amount DECIMAL(8,2) NOT NULL,
  due_date DATE NULL,
  status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  paid_at DATE NULL,
  method VARCHAR(30) NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY k_pay (student_id, period),
  CONSTRAINT fk_pay_st FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS app_settings (
  k VARCHAR(60) NOT NULL PRIMARY KEY,
  v TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
