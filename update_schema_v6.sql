-- update_schema_v6.sql
-- Script to update the database schema for version 9 functionalities (Basic SEO fields).

-- IMPORTANT: Backup your database before applying these changes.

-- --------------------------------------------------------
--
-- Altering table `matches` to add SEO fields
--
ALTER TABLE `matches`
ADD COLUMN `meta_description` TEXT DEFAULT NULL COMMENT 'Meta description for SEO for the match' AFTER `cover_image_filename`,
ADD COLUMN `meta_keywords` VARCHAR(255) DEFAULT NULL COMMENT 'Comma-separated meta keywords for SEO for the match' AFTER `meta_description`;

-- --------------------------------------------------------
--
-- Altering table `tv_channels` to add SEO fields
--
ALTER TABLE `tv_channels`
ADD COLUMN `meta_description` TEXT DEFAULT NULL COMMENT 'Meta description for SEO for the TV channel' AFTER `sort_order`,
ADD COLUMN `meta_keywords` VARCHAR(255) DEFAULT NULL COMMENT 'Comma-separated meta keywords for SEO for the TV channel' AFTER `meta_description`;

-- --------------------------------------------------------
-- End of schema updates for V6 (related to V9 plan step for SEO).
