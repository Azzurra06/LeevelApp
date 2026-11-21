-- Migration 008 — bilans de séance, retours élève, contrôles à venir, bilans mensuels, objectifs de l'année (idempotente)
CREATE TABLE IF NOT EXISTS session_reports (
  session_id INT UNSIGNED NOT NULL PRIMARY KEY,
  worked_on TEXT NULL,
  tutor_note TEXT NULL,
  parent_summary TEXT NULL,
  next_focus VARCHAR(500) NULL,
  sent_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sr_s FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS session_feedback (
  session_id INT UNSIGNED NOT NULL PRIMARY KEY,
  mood ENUM('good','ok','hard') NULL,
  understood VARCHAR(1000) NULL,
  to_deepen VARCHAR(1000) NULL,
  needs_next VARCHAR(1000) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sf_s FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS upcoming_tests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  test_date DATE NOT NULL,
  subject VARCHAR(80) NOT NULL,
  kind ENUM('controle','devoir','oral','examen_blanc','examen','autre') NOT NULL DEFAULT 'controle',
  title VARCHAR(160) NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY k_ut (student_id, test_date),
  CONSTRAINT fk_ut_st FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_ut_u FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monthly_reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  month CHAR(7) NOT NULL,
  summary TEXT NULL,
  published_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mr (student_id, month),
  CONSTRAINT fk_mr_st FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE goals ADD COLUMN IF NOT EXISTS horizon ENUM('month','year') NOT NULL DEFAULT 'month' AFTER is_mission;
