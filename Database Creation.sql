CREATE DATABASE geld CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `geld`.`user` ( `userID` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(30) NOT NULL , `password` VARCHAR(255) NOT NULL , `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`userID`), UNIQUE (`name`)) ENGINE = InnoDB;
CREATE TABLE `geld`.`reisen` (`RID` INT NOT NULL AUTO_INCREMENT , `owner` INT NOT NULL , `name` VARCHAR(255) NOT NULL , `beschreibung` TEXT NOT NULL , `isPublic` BOOLEAN NOT NULL , `abgeschlossen` BOOLEAN NOT NULL , `abgeschlossenTime` TIMESTAMP NULL , `erstelltTime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`RID`), INDEX (`owner`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci; 
CREATE TABLE `geld`.`reiseteilnehmer` (`RID` INT NOT NULL , `userID` INT NOT NULL , `mitwirkend` BOOLEAN NOT NULL , INDEX (`RID`), INDEX (`userID`)) ENGINE = InnoDB; ALTER TABLE reiseteilnehmer ADD CONSTRAINT reiseteilnehmer UNIQUE(RID, userID);
CREATE TABLE `geld`.`rechnungen` (`rechID` INT NOT NULL AUTO_INCREMENT , `RID` INT NOT NULL , `samePP` BOOLEAN NOT NULL , `involved` VARCHAR(500) NOT NULL , `hasPayed` VARCHAR(500) NOT NULL , `geldAn` INT NOT NULL , `kosten` DOUBLE NOT NULL , `kostenpp` DOUBLE NULL , `kostenAufteilung` TEXT NULL , `KID` INT NOT NULL , `notiz` TEXT NOT NULL , `beglichen` BOOLEAN NOT NULL , `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `beglichenAm` TIMESTAMP NULL , PRIMARY KEY (`rechID`, `RID`), INDEX (`geldAn`), INDEX (`beglichen`), INDEX (`time`), INDEX (`beglichenAm`), INDEX (`KID`)) ENGINE = InnoDB; 
CREATE TABLE `geld`.`rechnungenind` (`userID` INT NOT NULL , `RID` INT NOT NULL , `rechnID` INT NOT NULL , `geldAn` INT NOT NULL , `betrag` DOUBLE NOT NULL , `beglichen` BOOLEAN NOT NULL , `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `beglichenAm` TIMESTAMP NULL , UNIQUE (`userID`, `RID`, `rechnID`)) ENGINE = InnoDB; 
CREATE TABLE `geld`.`kategorien` (`KID` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL , PRIMARY KEY (`KID`)) ENGINE = InnoDB; 