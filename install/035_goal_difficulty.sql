-- Missions de la semaine : difficulté rencontrée (facile / moyen / difficile)
ALTER TABLE goals ADD COLUMN IF NOT EXISTS difficulty ENUM('easy','medium','hard') NULL;
