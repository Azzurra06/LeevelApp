-- Migration 007 — autorisations données par le parent au compte de l'enfant (idempotente)
ALTER TABLE students
  ADD COLUMN IF NOT EXISTS perm_edit_contacts TINYINT(1) NOT NULL DEFAULT 1 AFTER contact_notes,
  ADD COLUMN IF NOT EXISTS perm_edit_photo TINYINT(1) NOT NULL DEFAULT 1 AFTER perm_edit_contacts,
  ADD COLUMN IF NOT EXISTS perm_help_requests TINYINT(1) NOT NULL DEFAULT 1 AFTER perm_edit_photo;
