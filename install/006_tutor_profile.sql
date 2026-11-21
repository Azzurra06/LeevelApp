-- Migration 006 — adresse postale, tarifs horaires et remises (idempotente)
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL AFTER email,
  ADD COLUMN IF NOT EXISTS rate_presentiel DECIMAL(7,2) NULL AFTER address,
  ADD COLUMN IF NOT EXISTS rate_visio DECIMAL(7,2) NULL AFTER rate_presentiel,
  ADD COLUMN IF NOT EXISTS discounts_json TEXT NULL AFTER rate_visio;
