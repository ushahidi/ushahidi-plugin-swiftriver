-- -----------------------------------------------------
-- Table `swiftriver_clients`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `swiftriver_clients` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `client_name` VARCHAR(100) NOT NULL COMMENT 'Name of the deployment being added',
  `client_url` VARCHAR(200) NOT NULL COMMENT 'Unique URL ' ,
  `client_id` VARCHAR(25) NOT NULL COMMENT 'Used to identify the client and retrieve its client secret' ,
  `client_secret` VARCHAR(40) NULL COMMENT 'Secret key to be used for signing the client requests' ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `ui_client_id` (`client_id`) ,
  UNIQUE INDEX `ui_client_secret` (`client_secret`),
  UNIQUE KEY `uk_user_id_client_url` (`user_id`, `client_url`)
)ENGINE = InnoDB CHARSET=utf8
COMMENT = 'SwiftRiver deployments that can push drops to the deployment';

-- -----------------------------------------------------
-- Table `swiftriver_client_drops`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `swiftriver_client_drops` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `swiftriver_client_id` INT NOT NULL ,
  `drop_hash` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_client_drop_hash` (`swiftriver_client_id`, `drop_hash`)
)ENGINE = InnoDB CHARSET=utf8
COMMENT = 'Tracks the drops for each SwiftRiver client';

-- -----------------------------------------------------
-- Table `swiftriver_drop_incident`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `swiftriver_drop_incident` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `drop_hash` VARCHAR(32) NOT NULL COMMENT 'Unique hash of the drop. Ensures that we only store the drop once',
  `incident_id` BIGINT(20) NOT NULL COMMENT 'Incident created from the drop_id',
  `metadata` TEXT NULL COMMENT 'Drop metadata (links,images,tags,locations etc)' ,
  `veracity` INT NULL DEFAULT 1 COMMENT 'The number of times the drop has been submitted - from separate buckets or swiftriver deployments' ,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_drop_incident` (`drop_hash`, `incident_id`)
)ENGINE = InnoDB CHARSET=utf8
COMMENT = 'Maps a SwiftRiver drop to an incident';

-- Add hash column to the locations table
ALTER TABLE `location` ADD COLUMN `location_hash` VARCHAR(32);

-- Create hash for the existing locations
UPDATE `location` SET `location_hash` = MD5(CONCAT(`location_name`, `longitude`, `latitude`));