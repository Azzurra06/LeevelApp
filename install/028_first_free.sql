-- Première séance offerte (par élève)
ALTER TABLE students ADD COLUMN IF NOT EXISTS first_free TINYINT(1) NOT NULL DEFAULT 0;
