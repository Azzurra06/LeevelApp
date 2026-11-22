-- Messages automatiques (échéances, rythme…) affichés comme notifications système
ALTER TABLE messages ADD COLUMN IF NOT EXISTS kind VARCHAR(20) NULL AFTER body;
UPDATE messages SET kind='system' WHERE kind IS NULL AND (body LIKE 'Voici le rythme des séances%' OR body LIKE 'Nouvelle échéance pour%');
