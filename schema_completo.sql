-- schema_completo.sql
-- FutOnline Complete Database Schema
-- Version Date: 2024-03-15 (Adjust as per actual generation date)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `futonline_db` (Example name, use your actual DB name)
--

-- --------------------------------------------------------
--
-- Table structure for table `admins`
--
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Table structure for table `active_sessions`
--
CREATE TABLE IF NOT EXISTS `active_sessions` (
  `session_id` varchar(255) NOT NULL COMMENT 'PHP Session ID',
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Timestamp of the last recorded activity for this session',
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Table structure for table `leagues`
--
CREATE TABLE IF NOT EXISTS `leagues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo_filename` varchar(255) DEFAULT NULL COMMENT 'Filename of the uploaded league logo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Table structure for table `teams`
--
CREATE TABLE IF NOT EXISTS `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Official name of the team',
  `logo_filename` varchar(255) DEFAULT NULL COMMENT 'Filename of the uploaded team logo',
  `primary_color_hex` varchar(7) DEFAULT NULL COMMENT 'Primary color of the team in HEX format (e.g., #RRGGBB)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_team_name` (`name`) COMMENT 'Ensure team names are unique'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Table structure for table `matches`
--
CREATE TABLE IF NOT EXISTS `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_time` datetime NOT NULL,
  `home_team_id` int(11) DEFAULT NULL COMMENT 'Foreign key to teams.id for home team',
  `away_team_id` int(11) DEFAULT NULL COMMENT 'Foreign key to teams.id for away team',
  `description` text DEFAULT NULL,
  `league_id` int(11) DEFAULT NULL COMMENT 'Foreign key to leagues.id',
  `cover_image_filename` varchar(255) DEFAULT NULL COMMENT 'Filename of the uploaded cover image for the match',
  `meta_description` text DEFAULT NULL COMMENT 'Meta description for SEO for the match',
  `meta_keywords` varchar(255) DEFAULT NULL COMMENT 'Comma-separated meta keywords for SEO for the match',
  PRIMARY KEY (`id`),
  KEY `league_id` (`league_id`),
  KEY `home_team_id` (`home_team_id`),
  KEY `away_team_id` (`away_team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Table structure for table `saved_stream_urls`
--
CREATE TABLE IF NOT EXISTS `saved_stream_urls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_name` varchar(255) NOT NULL COMMENT 'A friendly name for the stream source, e.g., Main HD, Alt SD',
  `stream_url_value` text NOT NULL COMMENT 'The actual URL of the stream',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_stream_name` (`stream_name`) COMMENT 'Ensure stream names are unique for easier management'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Table structure for table `site_settings`
--
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Table structure for table `streams`
--
CREATE TABLE IF NOT EXISTS `streams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `stream_url` text NOT NULL,
  `stream_label` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Table structure for table `tv_channels`
--
CREATE TABLE IF NOT EXISTS `tv_channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo_filename` varchar(255) DEFAULT NULL COMMENT 'Filename of the uploaded TV channel logo',
  `stream_url` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `meta_description` text DEFAULT NULL COMMENT 'Meta description for SEO for the TV channel',
  `meta_keywords` varchar(255) DEFAULT NULL COMMENT 'Comma-separated meta keywords for SEO for the TV channel',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
--
-- Adding Foreign Key Constraints
--
-- Note: Ensure tables are created before adding constraints.
--

-- Constraints for `matches` table
ALTER TABLE `matches`
  ADD CONSTRAINT `fk_matches_league` FOREIGN KEY (`league_id`) REFERENCES `leagues`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_home_team` FOREIGN KEY (`home_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_away_team` FOREIGN KEY (`away_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Constraints for `streams` table
ALTER TABLE `streams`
  ADD CONSTRAINT `streams_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;
