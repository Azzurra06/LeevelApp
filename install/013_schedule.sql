-- Migration 013 — rythme des séances convenu avec la famille (idempotente)
ALTER TABLE students ADD COLUMN IF NOT EXISTS schedule_json TEXT NULL AFTER perm_help_requests;
