-- Matières / thèmes abordés pendant une séance (liste JSON)
ALTER TABLE session_reports ADD COLUMN IF NOT EXISTS subjects_json TEXT NULL AFTER session_id;
