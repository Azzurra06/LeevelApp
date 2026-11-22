-- Migration 014 — raison d'un report / d'une annulation, lien vers la séance d'origine (idempotente)
ALTER TABLE sessions
  ADD COLUMN IF NOT EXISTS status_reason VARCHAR(500) NULL AFTER status,
  ADD COLUMN IF NOT EXISTS rescheduled_from INT UNSIGNED NULL AFTER status_reason;
