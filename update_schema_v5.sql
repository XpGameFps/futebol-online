-- update_schema_v5.sql
-- Script to update the database schema for version 5 functionalities.

-- IMPORTANT: Backup your database before applying these changes.

-- --------------------------------------------------------
--
-- Table structure for table `site_settings`
--
-- This table will store various site-wide settings as key-value pairs.
--
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: Insert a default value for the cookie banner text
-- INSERT INTO `site_settings` (`setting_key`, `setting_value`)
-- VALUES ('cookie_banner_text', 'Este site utiliza cookies para melhorar a sua experiência. Ao continuar navegando, você concorda com o nosso uso de cookies.')
-- ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- --------------------------------------------------------
-- Placeholder for other V5 schema changes if any.
