-- Autorisation : l'élève peut relier son adresse e-mail à son compte (notifications, récupération du mot de passe)
ALTER TABLE students ADD COLUMN IF NOT EXISTS perm_link_email TINYINT(1) NOT NULL DEFAULT 1 AFTER perm_edit_photo;
