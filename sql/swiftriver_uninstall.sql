-- Drop the location_hash column in the location table
ALTER TABLE `location` DROP `location_hash`;

-- Drop the swiftriver_* tables
DROP TABLE IF EXISTS `swiftriver_drop_incident`;
DROP TABLE IF EXISTS `swiftriver_client_drops`;
DROP TABLE IF EXISTS `swiftriver_clients`;