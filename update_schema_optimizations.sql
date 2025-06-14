-- Add index for match_time in matches table
ALTER TABLE `matches` ADD INDEX `idx_match_time` (`match_time`);

-- Add composite index for sort_order and name in tv_channels table
ALTER TABLE `tv_channels` ADD INDEX `idx_sort_order_name` (`sort_order`, `name`);

-- Add composite index for is_active and display_on_homepage in banners table
ALTER TABLE `banners` ADD INDEX `idx_is_active_display_homepage` (`is_active`, `display_on_homepage`);
