-- Moyenne de départ d'une matière (au début de l'accompagnement)
ALTER TABLE student_subjects ADD COLUMN IF NOT EXISTS start_avg DECIMAL(4,2) NULL AFTER name;
