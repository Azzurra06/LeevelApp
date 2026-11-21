-- Migration 010 — messagerie élève <-> tutrice (lisible par le parent) : canal des messages (idempotente)
ALTER TABLE messages ADD COLUMN IF NOT EXISTS channel ENUM('family','student') NOT NULL DEFAULT 'family' AFTER family_id;
CREATE INDEX IF NOT EXISTS k_msg_chan ON messages (family_id, channel, created_at);
