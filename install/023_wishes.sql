-- Souhaits de la famille (volume, besoins, matières, modalité), recueillis à l'inscription
ALTER TABLE students ADD COLUMN IF NOT EXISTS wishes_json TEXT NULL;
