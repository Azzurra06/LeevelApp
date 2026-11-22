-- Migration 016 — informations sur l'enfant renseignées par le parent (onglet « Mon enfant ») (idempotente)
ALTER TABLE students
  ADD COLUMN IF NOT EXISTS last_name VARCHAR(80) NULL AFTER first_name,
  ADD COLUMN IF NOT EXISTS birth_date DATE NULL AFTER last_name,
  ADD COLUMN IF NOT EXISTS needs_json TEXT NULL AFTER schedule_json,
  ADD COLUMN IF NOT EXISTS accommodations VARCHAR(500) NULL AFTER needs_json,
  ADD COLUMN IF NOT EXISTS parent_notes TEXT NULL AFTER accommodations;
