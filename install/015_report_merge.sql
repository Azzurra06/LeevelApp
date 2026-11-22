-- Migration 015 — report fusionné dans une séance déjà prévue le même jour (idempotente)
ALTER TABLE sessions
  ADD COLUMN IF NOT EXISTS merged_report_from INT UNSIGNED NULL AFTER rescheduled_from,
  ADD COLUMN IF NOT EXISTS merged_report_min SMALLINT UNSIGNED NULL AFTER merged_report_from;
