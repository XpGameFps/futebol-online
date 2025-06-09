-- update_schema_v7.sql
-- Script to update the database schema for version 11 functionalities (Saved Stream URLs Library).

-- IMPORTANT: Backup your database before applying these changes.

-- --------------------------------------------------------
--
-- Table structure for table `saved_stream_urls`
--
-- This table will store a library of reusable stream URLs with friendly names.
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
-- End of schema updates for V7 (related to V11 plan step for Saved Stream URLs).
