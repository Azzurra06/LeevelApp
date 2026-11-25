-- Onglet « Outils » de l'élève personnalisable par la tutrice (outils, fiches méthode, sites)
ALTER TABLE students ADD COLUMN IF NOT EXISTS tools_json TEXT NULL;
