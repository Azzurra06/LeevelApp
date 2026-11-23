-- Fin de séance : notification envoyée une seule fois (tutrice : bilan à rédiger ; élève : donner son retour)
ALTER TABLE sessions ADD COLUMN IF NOT EXISTS ended_notified_at DATETIME NULL;
UPDATE sessions SET ended_notified_at = NOW() WHERE ended_notified_at IS NULL AND starts_at + INTERVAL duration_min MINUTE <= NOW();
