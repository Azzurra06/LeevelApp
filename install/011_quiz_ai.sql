-- Migration 011 — origine des questions de quiz (saisie manuelle ou générée par IA) (idempotente)
ALTER TABLE chapter_questions ADD COLUMN IF NOT EXISTS source ENUM('manual','ai') NOT NULL DEFAULT 'manual' AFTER explanation;
