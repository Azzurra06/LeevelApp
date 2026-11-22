-- Migration 012 — retour de séance simplifié : émoji + petite note libre de l'élève (idempotente)
ALTER TABLE session_feedback ADD COLUMN IF NOT EXISTS note VARCHAR(1000) NULL AFTER needs_next;
