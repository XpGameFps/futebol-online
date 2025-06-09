-- update_schema_v2.sql
-- Script to update the database schema for version 2 functionalities.

-- --------------------------------------------------------
--
-- Table structure for table `leagues`
--
CREATE TABLE `leagues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo_url` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `leagues` (Optional examples)
--
-- INSERT INTO `leagues` (`name`, `logo_url`) VALUES
-- ('Brasileirão Série A', NULL),
-- ('NBA', NULL),
-- ('Champions League', NULL),
-- ('Sem Liga', NULL); -- Example for matches without a specific league

-- --------------------------------------------------------
--
-- Table structure for table `tv_channels`
--
CREATE TABLE `tv_channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo_url` varchar(512) DEFAULT NULL,
  `stream_url` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Altering table `matches`
--
ALTER TABLE `matches`
ADD COLUMN `league_id` INT(11) DEFAULT NULL AFTER `description`,
ADD CONSTRAINT `fk_match_league` FOREIGN KEY (`league_id`) REFERENCES `leagues`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Note on `ON DELETE SET NULL`:
-- If a league is deleted, existing matches associated with it will have their league_id set to NULL.
-- This prevents accidental deletion of matches if a league is removed.
-- An alternative would be `ON DELETE RESTRICT` or `ON DELETE NO ACTION` if matches should not exist without a league,
-- or if a league with matches should not be deletable. `SET NULL` is a common choice for optional associations.
