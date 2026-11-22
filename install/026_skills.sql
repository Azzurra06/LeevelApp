-- Compétences transversales travaillées (méthodologie, organisation, confiance en soi…)
CREATE TABLE IF NOT EXISTS student_skills (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  skill_key VARCHAR(30) NULL,
  name VARCHAR(80) NOT NULL,
  start_level TINYINT NULL,
  level TINYINT NULL,
  note TEXT NULL,
  position SMALLINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_sk (student_id, name),
  CONSTRAINT fk_sk_st FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS skill_practices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  skill_id INT UNSIGNED NOT NULL,
  label VARCHAR(160) NOT NULL,
  done TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_skp_sk FOREIGN KEY (skill_id) REFERENCES student_skills(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE tutor_observations ADD COLUMN IF NOT EXISTS skill_id INT UNSIGNED NULL;
