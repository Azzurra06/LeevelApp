-- Matières : objectif / appréciation, ressources (liens, fichiers) et commentaires
ALTER TABLE student_subjects ADD COLUMN IF NOT EXISTS focus TEXT NULL;
CREATE TABLE IF NOT EXISTS subject_resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_id INT UNSIGNED NOT NULL,
  chapter_id INT UNSIGNED NULL,
  kind ENUM('link','file') NOT NULL DEFAULT 'link',
  title VARCHAR(160) NOT NULL,
  url VARCHAR(500) NULL,
  file_id INT UNSIGNED NULL,
  body TEXT NULL,
  shared TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY k_sr_subj (subject_id),
  CONSTRAINT fk_sr_subj FOREIGN KEY (subject_id) REFERENCES student_subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_sr_chap FOREIGN KEY (chapter_id) REFERENCES subject_chapters(id) ON DELETE SET NULL,
  CONSTRAINT fk_sr_file FOREIGN KEY (file_id) REFERENCES student_files(id) ON DELETE SET NULL,
  CONSTRAINT fk_sr_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS subject_comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_id INT UNSIGNED NOT NULL,
  chapter_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NULL,
  body TEXT NOT NULL,
  shared TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY k_sc_subj (subject_id, created_at),
  CONSTRAINT fk_sc_subj FOREIGN KEY (subject_id) REFERENCES student_subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_sc_chap FOREIGN KEY (chapter_id) REFERENCES subject_chapters(id) ON DELETE SET NULL,
  CONSTRAINT fk_sc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
