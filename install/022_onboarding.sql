-- Parcours d'accueil : date de fin du parcours (les comptes existants sont considérés comme déjà accueillis)
ALTER TABLE users ADD COLUMN IF NOT EXISTS onboarded_at DATETIME NULL;
UPDATE users SET onboarded_at=COALESCE(last_login_at, created_at, NOW()) WHERE onboarded_at IS NULL;
