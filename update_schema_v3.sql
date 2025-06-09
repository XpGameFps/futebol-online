-- update_schema_v3.sql
-- Script to update the database schema for version 3 functionalities (logo uploads).

-- IMPORTANT:
-- This script alters existing columns. If you have data in `logo_url` columns,
-- that data will effectively be orphaned as the column is renamed and its purpose changes.
-- You will need to re-upload logos for existing leagues and TV channels after applying this schema change
-- and implementing the upload functionality.
-- Consider backing up your database before applying these changes if you have production data.

-- --------------------------------------------------------
--
-- Altering table `leagues` to change `logo_url` to `logo_filename`
--
ALTER TABLE `leagues`
CHANGE COLUMN `logo_url` `logo_filename` VARCHAR(255) DEFAULT NULL;
-- If `logo_url` was VARCHAR(512), VARCHAR(255) is usually sufficient for filenames.
-- The DEFAULT NULL remains.

-- --------------------------------------------------------
--
-- Altering table `tv_channels` to change `logo_url` to `logo_filename`
--
ALTER TABLE `tv_channels`
CHANGE COLUMN `logo_url` `logo_filename` VARCHAR(255) DEFAULT NULL;
-- If `logo_url` was VARCHAR(512), VARCHAR(255) is usually sufficient for filenames.
-- The DEFAULT NULL remains.

-- --------------------------------------------------------
--
-- Instructions for directory creation (to be done manually on the server):
--
-- For the logo upload functionality to work, you will need to create
-- the following directories in the root of your project installation,
-- and ensure they are writable by your web server:
--
--   uploads/
--   uploads/logos/
--   uploads/logos/leagues/
--   uploads/logos/channels/
--
-- Example permissions (if using Linux):
-- mkdir -p uploads/logos/leagues
-- mkdir -p uploads/logos/channels
-- chmod -R 755 uploads  (or 775 if group write is needed and safe)
-- sudo chown -R www-data:www-data uploads (replace www-data with your web server user/group)
--
-- The exact chmod/chown commands might vary based on your server setup and security policies.
-- The key is that the PHP script needs to be able to write files into these directories.
--
