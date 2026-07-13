-- Migration : ajoute le numéro de téléphone du donateur (repris du profil utilisateur connecté)
ALTER TABLE `donations` ADD COLUMN `donor_phone` VARCHAR(30) DEFAULT NULL AFTER `donor_email`;
