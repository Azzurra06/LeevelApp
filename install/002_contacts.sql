-- Migration 002 — coordonnées élève et parent (idempotente, MariaDB 10.0.2+ / MySQL 8.0.29+)
ALTER TABLE students
  ADD COLUMN IF NOT EXISTS student_phone VARCHAR(30) NULL AFTER school,
  ADD COLUMN IF NOT EXISTS student_email VARCHAR(120) NULL AFTER student_phone,
  ADD COLUMN IF NOT EXISTS parent_phone VARCHAR(30) NULL AFTER student_email,
  ADD COLUMN IF NOT EXISTS parent_email VARCHAR(120) NULL AFTER parent_phone,
  ADD COLUMN IF NOT EXISTS parent_address VARCHAR(255) NULL AFTER parent_email,
  ADD COLUMN IF NOT EXISTS contact_notes VARCHAR(500) NULL AFTER parent_address;
