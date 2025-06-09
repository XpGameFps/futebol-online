-- update_schema_v8.sql
-- Script to update the database schema for version 12 functionalities (Online Users Counter).

-- IMPORTANT: Backup your database before applying these changes.

-- --------------------------------------------------------
--
-- Table structure for table `active_sessions`
--
-- This table will track active user sessions on the frontend
-- to provide an estimate of online users.
--
CREATE TABLE IF NOT EXISTS `active_sessions` (
  `session_id` varchar(255) NOT NULL COMMENT 'PHP Session ID',
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Timestamp of the last recorded activity for this session',
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- End of schema updates for V8 (related to V12 plan step for Online Users Counter).
