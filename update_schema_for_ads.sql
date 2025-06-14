-- SQL Schema updates for Ad Functionality

-- Add new columns for ad code and ad type
ALTER TABLE `banners`
ADD COLUMN `ad_code` TEXT DEFAULT NULL,
ADD COLUMN `ad_type` VARCHAR(20) NOT NULL DEFAULT 'image';

-- Ensure existing rows have ad_type set to 'image'
-- This is a safeguard; the DEFAULT 'image' on ADD COLUMN
-- should handle new rows and often updates existing NULLs,
-- but explicit update is safer.
UPDATE `banners` SET `ad_type` = 'image' WHERE `ad_type` IS NULL OR `ad_type` = '';

-- Modify existing columns to allow NULL values
-- This is necessary for script-based ads that don't have an image or a direct target URL.
ALTER TABLE `banners`
MODIFY COLUMN `image_path` VARCHAR(255) NULL,
MODIFY COLUMN `target_url` VARCHAR(2048) NULL;
