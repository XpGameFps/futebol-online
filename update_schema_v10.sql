-- Add SEO settings for the homepage
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('seo_homepage_title', 'Default Homepage Title'),
('seo_homepage_description', 'Default homepage meta description.'),
('seo_homepage_keywords', 'keyword1, keyword2, keyword3')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- It's good practice to also ensure these keys exist if the table might be empty
-- or if a previous script failed. The ON DUPLICATE KEY UPDATE handles this.
-- No further ALTER TABLE needed as site_settings is a key-value store.
