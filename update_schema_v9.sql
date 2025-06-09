-- update_schema_v9.sql
-- Script to update the database schema for version 13 functionalities
-- (Team Management and New Match Card Design).

-- IMPORTANT: Backup your database before applying these changes.
-- This schema version includes breaking changes to the 'matches' table if it contains existing data.
-- Existing 'team_home' and 'team_away' string data will be lost.
-- After this update, existing matches may need to be manually re-associated with teams via the admin panel.

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
-- Altering `matches` table to use Foreign Keys for teams
--
-- Step 1: Add new columns for team IDs
ALTER TABLE `matches`
  ADD COLUMN `home_team_id` INT(11) NULL DEFAULT NULL COMMENT 'Foreign key to teams.id for home team' AFTER `match_time`,
  ADD COLUMN `away_team_id` INT(11) NULL DEFAULT NULL COMMENT 'Foreign key to teams.id for away team' AFTER `home_team_id`;

-- Step 2: (Optional - Data Migration - Requires manual scripting or more complex SQL)
-- At this point, if you have existing data in `team_home` and `team_away`,
-- you would run a script to populate `home_team_id` and `away_team_id` based on team names.
-- For example (conceptual, needs to be adapted and run carefully):
-- UPDATE `matches` m JOIN `teams` th ON m.team_home = th.name SET m.home_team_id = th.id;
-- UPDATE `matches` m JOIN `teams` ta ON m.team_away = ta.name SET m.away_team_id = ta.id;
-- This assumes team names in `matches` exactly match names in `teams`.

-- Step 3: Add Foreign Key constraints
-- It's often better to add constraints after data migration (if any).
ALTER TABLE `matches`
  ADD CONSTRAINT `fk_matches_home_team` FOREIGN KEY (`home_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_away_team` FOREIGN KEY (`away_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Step 4: Drop old VARCHAR columns for team names
-- Ensure data migration (Step 2) is done if you need to preserve associations.
ALTER TABLE `matches`
  DROP COLUMN `team_home`,
  DROP COLUMN `team_away`;

-- Note on ON DELETE SET NULL: If a team is deleted, the corresponding *_team_id in matches will become NULL.
-- This means the match record itself won't be deleted, but it will lose its association with that team.
-- This is generally preferable to deleting match data if a team is accidentally removed and then re-added.
-- --------------------------------------------------------

-- End of schema updates for V9 (related to V13 plan step for Team Management).
