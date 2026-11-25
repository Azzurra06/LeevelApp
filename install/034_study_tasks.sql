-- Planning des devoirs et révisions (visible élève, parent, tutrice ; modifiable par l'élève et la tutrice)
CREATE TABLE IF NOT EXISTS study_tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NULL,
  kind ENUM('devoir','revision') NOT NULL DEFAULT 'devoir',
  title VARCHAR(200) NOT NULL,
  details TEXT NULL,
  due_date DATE NOT NULL,
  done_at DATETIME NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY k_st_student (student_id, due_date),
  CONSTRAINT fk_st_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_st_subject FOREIGN KEY (subject_id) REFERENCES student_subjects(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
